<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

function account(Request $request): ?object
{
    return DB::table('accounts')->where('id', $request->session()->get('account_id'))
        ->where('active', true)->first();
}

function requireRole(Request $request, string $role): object
{
    $account = account($request);
    abort_unless($account?->role === $role, 403);
    return $account;
}

Route::get('/account', function (Request $request) {
    $account = account($request);
    $orders = collect();
    $accounts = collect();
    $variants = collect();
    $reviewable = collect();
    if ($account?->role === 'vendor') {
        $orders = DB::table('shop_orders')->where('shop_id', $account->shop_id)->latest('id')->get();
        $variants = app(\App\Checkout::class)->catalog()->where('products.shop_id', $account->shop_id)->get();
    }
    if ($account?->role === 'admin') {
        $orders = DB::table('shop_orders')->latest('id')->get();
        $accounts = DB::table('accounts')->where('role', '<>', 'admin')->get();
    }
    if ($account?->role === 'buyer') {
        $reviewable = purchasedProducts($account->id)->distinct()->get(['products.id', 'products.name']);
        $orders = DB::table('shop_orders')->join('orders', 'orders.id', '=', 'shop_orders.order_id')
            ->where('orders.account_id', $account->id)->select('shop_orders.*')->latest('shop_orders.id')->get();
    }
    return view('account', compact('account', 'orders', 'accounts', 'variants', 'reviewable'));
});

Route::post('/register', function (Request $request) {
    $data = $request->validate(['email'=>'required|email|max:190|unique:accounts,email','password'=>'required|string|min:8|max:72']);
    $id = DB::table('accounts')->insertGetId([
        'email'=>$data['email'], 'password'=>Hash::make($data['password']), 'role'=>'buyer',
    ]);
    $request->session()->regenerate();
    $request->session()->put('account_id', $id);
    return redirect('/account');
})->middleware('throttle:5,1');

Route::post('/login', function (Request $request) {
    $data = $request->validate(['email'=>'required|email','password'=>'required|string|max:72']);
    $account = DB::table('accounts')->where('email', $data['email'])->where('active', true)->first();
    if (!$account || !Hash::check($data['password'], $account->password)) {
        throw ValidationException::withMessages(['email'=>'Email hoặc mật khẩu không hợp lệ.']);
    }
    $request->session()->regenerate();
    $request->session()->put('account_id', $account->id);
    return redirect('/account');
})->middleware('throttle:8,1');

Route::post('/logout', function (Request $request) {
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
});

Route::post('/vendor/orders/{id}', function (Request $request, int $id) {
    $vendor = requireRole($request, 'vendor');
    $order = DB::table('shop_orders')->where('shop_id', $vendor->shop_id)->where('id', $id)->first();
    abort_unless($order, 404);
    $next = ['placed_demo'=>'packing','packing'=>'shipped','shipped'=>'delivered'][$order->status] ?? null;
    if ($next) {
        DB::table('shop_orders')->where('shop_id', $vendor->shop_id)->where('id', $id)
            ->where('status', $order->status)->update(['status'=>$next]);
    }
    return redirect('/account');
});

Route::post('/vendor/stock/{id}', function (Request $request, int $id) {
    $vendor = requireRole($request, 'vendor');
    $data = $request->validate(['price'=>'required|integer|min:1000|max:10000000']);
    $ownProducts = DB::table('products')->where('shop_id', $vendor->shop_id)->select('id');
    DB::table('variants')->whereIn('product_id', $ownProducts)->where('id', $id)->update($data);
    return redirect('/account');
});

Route::post('/admin/accounts/{id}', function (Request $request, int $id) {
    requireRole($request, 'admin');
    $data = $request->validate(['active'=>'required|boolean']);
    DB::table('accounts')->where('id', $id)->where('role', '<>', 'admin')->update($data);
    return redirect('/account');
});

function purchasedProducts(int $accountId)
{
    return DB::table('order_items')->join('variants', 'variants.id', '=', 'order_items.variant_id')
        ->join('products', 'products.id', '=', 'variants.product_id')
        ->join('shop_orders', 'shop_orders.id', '=', 'order_items.shop_order_id')
        ->join('orders', 'orders.id', '=', 'shop_orders.order_id')
        ->where('orders.account_id', $accountId)->where('shop_orders.status', 'delivered');
}

Route::post('/reviews', function (Request $request) {
    $buyer = requireRole($request, 'buyer');
    $data = $request->validate(['product_id'=>'required|integer','stars'=>'required|integer|min:1|max:5','body'=>'required|string|max:500']);
    abort_unless(purchasedProducts($buyer->id)->where('products.id', $data['product_id'])->exists(), 403);
    DB::table('reviews')->upsert([['account_id'=>$buyer->id] + $data], ['account_id','product_id'], ['stars','body']);
    return redirect('/account')->with('notice', 'Đã lưu đánh giá.');
});

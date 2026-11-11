<?php
use App\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function (Request $request, Checkout $checkout) {
    $catalog = $checkout->catalog();
    $filters = $request->validate(['q'=>'nullable|string|max:80','kind'=>'nullable|in:Giáo trình,Tham khảo,Học cụ']);
    if (!empty($filters['q'])) {
        $catalog->where('products.name', 'like', '%'.$filters['q'].'%');
    }
    if (!empty($filters['kind'])) {
        $catalog->where('products.kind', $filters['kind']);
    }
    $account = account($request);
    $lastOrder = $account ? DB::table('orders')->where('account_id', $account->id)->latest('id')->value('id') : null;
    return view('market', [
        'products'=>$catalog->get()->groupBy('product_id'),
        'cart'=>$checkout->quote($request->session()->get('cart', []), $request->session()->get('region', 'danang')),
        'region'=>$request->session()->get('region', 'danang'),
        'order'=>$lastOrder ? DB::table('orders')->find($lastOrder) : null,
        'suborders'=>$lastOrder ? DB::table('shop_orders')->where('order_id', $lastOrder)->get() : collect(),
        'account'=>$account,
        'posts'=>DB::table('discussions')->latest()->limit(20)->get(),
    ]);
});

Route::post('/cart', function (Request $request) {
    $data = $request->validate(['variant'=>'required|integer|exists:variants,id','quantity'=>'required|integer|min:0|max:20']);
    $cart = $request->session()->get('cart', []);
    if ($data['quantity'] === 0 || (int) $data['quantity'] === 0) {
        unset($cart[$data['variant']]);
    } else {
        $cart[$data['variant']] = (int) $data['quantity'];
    }
    $request->session()->put('cart', $cart);
    return redirect('/#gio');
})->block();

Route::post('/region', function (Request $request) {
    $request->session()->put('region', $request->validate(['region'=>'required|in:danang,other'])['region']);
    return redirect('/#gio');
})->block();

Route::post('/checkout', function (Request $request, Checkout $checkout) {
    $account = requireRole($request, 'buyer');
    $order = $checkout->place(
        $request->session()->get('cart', []),
        $request->session()->get('region', 'danang'),
        (string) Str::uuid(),
        $account->id,
    );
    $request->session()->forget('cart');
    $request->session()->put('order', $order);
    return redirect('/#don');
})->block();

Route::post('/discussions', function (Request $request) {
    $data = $request->validate(['body'=>'required|string|min:5|max:1000']);
    DB::table('discussions')->insert(['body'=>$data['body'],'created_at'=>now(),'updated_at'=>now()]);
    return redirect('/#thaoluan');
})->middleware('throttle:5,1');

require __DIR__.'/accounts.php';

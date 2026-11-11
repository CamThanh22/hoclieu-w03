<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tài khoản · Học Phần</title><link rel="stylesheet" href="/market.css"></head>
<body><header><div class="bar wrap"><a class="brand" href="/">Học Phần<span>.</span></a><a href="/">Về trang học phần</a></div></header>
<main class="wrap">
@if ($errors->any()) <p class="notice" role="alert">{{ $errors->first() }}</p> @endif
@if (session('notice')) <p role="status">{{ session('notice') }}</p> @endif
@if (!$account)
<div class="grid">
@foreach (['login'=>'Đăng nhập','register'=>'Đăng ký người mua'] as $route => $title)
<section class="panel"><h2>{{ $title }}</h2>
<form class="add" method="post" action="/{{ $route }}">@csrf
<label>Email <input name="email" type="email" required></label>
<label>Mật khẩu <input name="password" type="password" minlength="8" maxlength="72" required></label>
<button>{{ $title }}</button></form></section>
@endforeach
</div>
@else
@php($roles = ['buyer'=>'Người mua','vendor'=>'Gian hàng','admin'=>'Quản trị viên'])
@php($statuses = ['placed_demo'=>'Đã đặt thử','packing'=>'Đang đóng gói','shipped'=>'Đã gửi','delivered'=>'Đã giao'])
<section class="panel"><h2>{{ $roles[$account->role] }}</h2><p>{{ $account->email }}</p>
<form method="post" action="/logout">@csrf<button>Đăng xuất</button></form></section>
<section class="panel"><h2>Đơn theo gian hàng</h2>
@if ($account->role !== 'buyer')
<p>{{ $orders->count() }} đơn con · Giá trị tiền hàng đã giao {{ number_format($orders->where('status','delivered')->sum('subtotal'),0,',','.') }} ₫</p>
@endif
@forelse ($orders as $order)
<div class="line"><span>#{{ $order->id }} · {{ $order->shop_name }} · {{ $statuses[$order->status] }} · {{ number_format($order->subtotal + $order->shipping,0,',','.') }} ₫</span>
@if ($account->role === 'vendor' && $order->status !== 'delivered')
<form method="post" action="/vendor/orders/{{ $order->id }}">@csrf
<button>{{ ['placed_demo'=>'Đóng gói','packing'=>'Xác nhận đã gửi','shipped'=>'Xác nhận đã giao'][$order->status] }}</button>
</form>
@endif
</div>
@empty <p>Chưa có đơn.</p> @endforelse
</section>
@if ($account->role === 'vendor')
<section class="panel"><h2>SKU của gian hàng</h2>
@foreach ($variants as $variant)
<form class="line" method="post" action="/vendor/stock/{{ $variant->id }}">@csrf
<span>{{ $variant->sku }} · {{ $variant->name }} · Còn {{ $variant->stock }}</span>
<label>Giá <input style="width:120px" type="number" name="price" value="{{ $variant->price }}" min="1000" max="10000000" required></label>
<button>Lưu giá</button></form>
@endforeach
</section>
@endif
@if ($account->role === 'admin')
<section class="panel"><h2>Quản lý tài khoản</h2>
@foreach ($accounts as $row)
<form method="post" action="/admin/accounts/{{ $row->id }}" class="line">@csrf
<span>{{ $row->email }} · {{ $roles[$row->role] }}</span>
<button name="active" value="{{ $row->active ? 0 : 1 }}">{{ $row->active ? 'Khóa' : 'Mở khóa' }}</button></form>
@endforeach
</section>
@endif
@if ($reviewable->isNotEmpty())
<section class="panel"><h2>Đánh giá sản phẩm đã nhận</h2>
<form class="add" method="post" action="/reviews">@csrf
<label>Sản phẩm <select name="product_id">@foreach ($reviewable as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
<label>Số sao <input type="number" name="stars" min="1" max="5" value="5" required></label>
<label>Nhận xét <textarea name="body" maxlength="500" required></textarea></label>
<button>Lưu đánh giá</button></form>
</section>
@endif
@endif
</main></body></html>

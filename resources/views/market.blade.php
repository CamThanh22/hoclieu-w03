<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Học Phần | Toán rời rạc</title>
<link rel="stylesheet" href="/market.css">
</head>
<body>
<header><div class="wrap bar"><a class="brand" href="/">Học Phần<span>.</span></a><nav><a href="/account">{{ $account ? "Tài khoản" : "Đăng nhập" }}</a><a href="#hoclieu">Học liệu</a><a href="#thaoluan">Thảo luận</a><a href="#gio">Giỏ hàng</a></nav></div></header>
<main class="wrap">
<section class="hero">
<div><p class="eyebrow">KHOA TOÁN – TIN / HỌC PHẦN MẪU</p><h1>Toán rời rạc</h1><p>Tìm giáo trình, sách bài tập và học cụ từ các gian hàng trong một giỏ chung.</p><a class="button" href="#hoclieu">Xem học liệu</a></div>
<div class="notation" aria-hidden="true">A ∩ B<br><small>{ x ∈ A ∣ x ∈ B }</small></div>
</section>
@if ($errors->any())
<div class="notice" role="alert">{{ $errors->first() }}</div>
@endif
<section id="hoclieu">
<div class="section-head"><h2>Học liệu theo môn</h2><span>Danh mục và giá minh họa</span></div>
<form class="filters" method="get" action="/#hoclieu">
<input name="q" aria-label="Tìm học liệu" placeholder="Tìm theo tên học liệu" value="{{ request('q') }}" maxlength="80">
<select name="kind" aria-label="Loại học liệu"><option value="">Tất cả loại</option>
@foreach (['Giáo trình','Tham khảo','Học cụ'] as $kind)
<option @selected(request('kind') === $kind)>{{ $kind }}</option>
@endforeach
</select><button>Tìm kiếm</button><a href="/#hoclieu">Xóa lọc</a>
</form>
<div class="grid">
@forelse ($products as $variants)
@php($product = $variants->first())
<article class="card">
<div class="cover {{ $product->kind === 'Học cụ' ? 'sage' : '' }}"><span>{{ $product->kind }}</span><strong>{{ $product->name }}</strong></div>
<div class="body"><span class="muted">{{ $product->shop }}</span><h3>{{ $product->name }}</h3>
<form method="post" action="/cart" class="add">@csrf
<select name="variant" aria-label="Phiên bản và giá của {{ $product->name }}" required>
@foreach ($variants as $variant)
<option value="{{ $variant->id }}" @disabled(!$variant->stock)>{{ $variant->label }} · {{ number_format($variant->price,0,',','.') }} ₫ · {{ $variant->stock ? 'Còn '.$variant->stock : 'Hết hàng' }}</option>
@endforeach
</select>
<input type="hidden" name="quantity" value="1"><button @disabled(!$variants->sum('stock'))>Thêm vào giỏ</button>
</form></div>
</article>
@empty
<p>Không tìm thấy học liệu phù hợp.</p>
@endforelse
</div>
</section>
<section id="gio" class="panel"><h2>Giỏ hàng</h2>
@if (!$cart)
<p>Giỏ hàng đang trống.</p>
@else
<form method="post" action="/region" class="filters">@csrf<label for="region">Khu vực giao</label><select id="region" name="region"><option value="danang">Đà Nẵng</option><option value="other" @selected($region === 'other')>Ngoài Đà Nẵng</option></select><button>Cập nhật phí giao</button></form>
@foreach ($cart as $group)
<div class="shop"><h3>{{ $group['name'] }}</h3>
@foreach ($group['items'] as ['variant'=>$variant,'quantity'=>$quantity])
<form method="post" action="/cart" class="line">@csrf
<input type="hidden" name="variant" value="{{ $variant->id }}">
<label for="qty{{ $variant->id }}">{{ $variant->name }} · {{ $variant->label }}</label>
<input id="qty{{ $variant->id }}" type="number" name="quantity" value="{{ $quantity }}" min="0" max="20" required>
<button>Cập nhật</button><button name="quantity" value="0" formnovalidate>Xóa</button>
</form>
@endforeach
<p>Tiền hàng {{ number_format($group['subtotal'],0,',','.') }} ₫ · Phí giao {{ number_format($group['shipping'],0,',','.') }} ₫</p>
</div>
@endforeach
<p><strong>Tổng cộng {{ number_format(array_sum(array_map(fn($g) => $g['subtotal'] + $g['shipping'], $cart)),0,',','.') }} ₫</strong></p>
@if ($account?->role === 'buyer')
<form method="post" action="/checkout">@csrf<button>Đặt đơn thử nghiệm</button></form>
@else
<a href="/account">Đăng nhập tài khoản người mua để đặt đơn</a>
@endif
@endif
</section>
@if ($order)
<section class="panel" id="don"><h2>Đơn thử nghiệm #{{ $order->id }}</h2><p>Đã ghi nhận đặt hàng. Tổng {{ number_format($order->total,0,',','.') }} ₫.</p>
@foreach ($suborders as $suborder)
<p><strong>{{ $suborder->shop_name }}</strong> · Đơn con #{{ $suborder->id }} · {{ ['placed_demo'=>'Đã đặt thử','packing'=>'Đang đóng gói','shipped'=>'Đã gửi','delivered'=>'Đã giao'][$suborder->status] }} · {{ number_format($suborder->subtotal + $suborder->shipping,0,',','.') }} ₫</p>
@endforeach
</section>
@endif
<section id="thaoluan" class="panel"><h2>Thảo luận Toán rời rạc</h2>
<form method="post" action="/discussions" class="add">@csrf
<label for="body">Câu hỏi hoặc bài giải của bạn</label>
<textarea id="body" name="body" minlength="5" maxlength="1000" required rows="3">{{ old('body') }}</textarea>
<button>Đăng thảo luận</button>
</form>
@forelse ($posts as $post)
<article class="post"><time>{{ $post->created_at }}</time><p>{{ $post->body }}</p></article>
@empty
<p class="muted">Chưa có bài thảo luận.</p>
@endforelse
</section>
</main>
<footer class="wrap">Học Phần · W03</footer>
</body>
</html>

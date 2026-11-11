# Học Phần · W03

Bản đầu tiên của đồ án sàn giáo trình và học cụ đa người bán. Dữ liệu Toán rời rạc, tên shop, giá và phí giao đều là ví dụ phục vụ demo.

## Chạy trên Windows với XAMPP

Cần PHP 8.2 trở lên có PDO MySQL, Composer và MySQL. Nếu dùng MySQL/MariaDB của XAMPP, bật dịch vụ MySQL, tạo database `hocphan` (utf8mb4) rồi mở PowerShell trong thư mục chứa `artisan`.

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType Directory -Force bootstrap/cache,storage/framework/cache/data,storage/framework/sessions,storage/framework/views,storage/logs
php artisan key:generate
php artisan migrate
php artisan db:seed --class=Database\Seeders\DemoSeeder
php artisan serve
```

Chỉnh tài khoản, cổng và mật khẩu MySQL trong `.env` trước khi migrate nếu cấu hình máy khác mặc định. Mở http://127.0.0.1:8000. Nếu PowerShell không thấy PHP, thêm thư mục PHP của XAMPP vào PATH trước khi chạy Composer.

## Luồng kiểm tra

1. Tìm hoặc lọc học liệu, chọn biến thể của một giáo trình và một học cụ từ hai shop.
2. Mở Giỏ hàng. Chỉnh số lượng và thử đổi khu vực giao để kiểm tra phí theo shop.
3. Đặt đơn thử nghiệm. Kiểm tra màn hình hiện một đơn tổng và hai đơn con. Tải lại trang và kiểm tra tồn đã giảm theo số lượng mua.
4. Đặt số lượng vượt tồn của một biến thể trong giỏ nhiều món. Xác nhận hệ thống báo lỗi và toàn bộ tồn của đơn thất bại được giữ nguyên.
5. Đăng nội dung trong mục Thảo luận rồi tải lại trang để kiểm tra dữ liệu.

## Tài khoản cục bộ và luồng demo

Mật khẩu chung của dữ liệu mẫu là `HocPhan-W03-demo`. Chỉ dùng dữ liệu mẫu trong môi trường cục bộ.

| Email | Quyền |
| --- | --- |
| buyer@hocphan.test | Mua hàng, xem đơn, đánh giá khi đã giao |
| shop1@hocphan.test | Xem đơn, đổi trạng thái và cập nhật giá SKU của shop 1 |
| shop2@hocphan.test | Quyền tương ứng với shop 2 |
| admin@hocphan.test | Xem đơn toàn sàn, khóa/mở tài khoản |

Đăng nhập buyer trước khi đặt đơn. Dùng các cửa sổ trình duyệt riêng nếu cần thử nhiều vai trò cùng lúc. Tài khoản shop chuyển đơn lần lượt qua đóng gói, đã gửi, đã giao. Giá trị tiền hàng đã giao trên bảng quản lý chỉ là dữ liệu mô phỏng. Thảo luận và đặt đơn hiện dùng cho demo cục bộ.

Còn thiếu VNPay Sandbox/IPN, mã giảm giá, quản lý danh mục và sản phẩm đầy đủ, công khai đánh giá trên danh mục, kiểm thử đồng thời và triển khai máy chủ.

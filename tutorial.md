# Hướng Dẫn Khởi Chạy FlexiDiet Trên Localhost (Local Deployment Guide)

> **Dự án:** FlexiDiet – Smart Dynamic Nutrition & Workout Platform  
> **Môi trường:** Local Development (XAMPP / PHP 8.x / MySQL)  
> **Hệ điều hành hỗ trợ:** Windows / macOS / Linux  

---

## 📁 1. Cấu Trúc Thư Mục Dự Án Liên Quan

```text
Midterm/
├── docs/                        # Tài liệu quy trình Unified Process (UP)
├── tutorial.md                  # Tài liệu hướng dẫn này
└── repo/
    └── FlexiDiet/               # Thư mục mã nguồn chính của ứng dụng
        ├── backend/             # Core PHP Backend & REST API
        │   └── config.php       # Cấu hình kết nối MySQL (127.0.0.1:3306)
        ├── database/            # Quản lý CSDL MySQL
        │   ├── schema.sql       # Cấu trúc 18 bảng CSDL
        │   ├── reload_all.sql   # Master script nạp toàn bộ cấu trúc & seeds
        │   └── seeds/           # 7 bộ dữ liệu mẫu (món ăn, nguyên liệu, tài khoản)
        └── public/              # Document Root của Web
            ├── index.html       # Landing page giới thiệu
            ├── app.html         # Workspace chính (Member Dashboard, Food Hub, Workout)
            ├── admin.html       # Studio quản trị dữ liệu (Admin)
            └── api/             # Front controller đón nhận API requests
```

---

## 🛠️ 2. Yêu Cầu Tiên Quyết (Prerequisites)

1. Đã cài đặt **XAMPP** (phiên bản hỗ trợ PHP 8.0 trở lên và MySQL/MariaDB).
2. Git (để quản lý phiên bản mã nguồn).
3. Trình duyệt web hiện đại (Google Chrome, Microsoft Edge, Brave,...).

---

## 🚀 3. Trình Tự Khởi Chạy Chi Tiết Từ Đầu

### Bước 1: Khởi động dịch vụ MySQL trong XAMPP
1. Mở ứng dụng **XAMPP Control Panel**.
2. Tìm hàng **MySQL** và nhấn nút **Start** (cổng mặc định là `3306`).
> *Lưu ý:* Bạn không cần nhấn Start ở Apache nếu sử dụng máy chủ PHP tích hợp (PHP Built-in Server) ở Bước 3.

---

### Bước 2: Khởi tạo và nạp Cơ Sở Dữ Liệu (Database Setup)

Bạn có thể chọn **Cách A (Terminal - Nhanh nhất)** hoặc **Cách B (Giao diện phpMyAdmin)**:

#### 👉 Cách A: Dùng Terminal / PowerShell (Khuyên dùng)

> 💡 **Khắc phục lỗi `'mysql' is not recognized`:**  
> Mặc định Windows chưa nhận lệnh `mysql` và `php` của XAMPP. Bạn có 2 cách cực nhanh sau:
> - **Cách A.1 (Tiện nhất):** Trên **XAMPP Control Panel**, nhấn nút **`Shell`** ở cột bên phải. Cửa sổ dòng lệnh mở ra đã được cấu hình sẵn cả `mysql` và `php`.
> - **Cách A.2 (Trong PowerShell hiện tại):** Chạy lệnh sau để nạp nhanh biến môi trường cho cửa sổ PowerShell hiện tại:
>   ```powershell
>   $env:Path += ";<Đường_dẫn_XAMPP>\mysql\bin;<Đường_dẫn_XAMPP>\php"
>   # Ví dụ máy bạn cài XAMPP ở ổ D:
>   $env:Path += ";D:\xampp\mysql\bin;D:\xampp\php"
>   ```

Sau đó di chuyển vào thư mục `database` của dự án và chạy các lệnh:

```bash
# 1. Di chuyển vào thư mục database của dự án
cd repo/FlexiDiet/database

# 2. Tạo database 'flexidiet' chuẩn mã hóa UTF-8 (nếu chưa có)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Nạp toàn bộ bảng và dữ liệu mẫu thông qua script reload_all.sql
mysql -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"
```

> **Ghi chú về đường dẫn:** File `reload_all.sql` sử dụng đường dẫn tương đối, vì vậy lệnh trên sẽ chạy hoàn hảo trên mọi máy tính khi bạn đang ở trong thư mục `repo/FlexiDiet/database`.

#### 👉 Cách B: Dùng giao diện web phpMyAdmin
1. Trên **XAMPP Control Panel**, nhấn **Start** cả **Apache** và **MySQL**.
2. Mở trình duyệt truy cập: `http://localhost/phpmyadmin`.
3. Bấm **New (Mới)** ở cột trái $\rightarrow$ Đặt tên cơ sở dữ liệu là `flexidiet`, chọn bảng mã `utf8mb4_unicode_ci` $\rightarrow$ Nhấn **Create (Tạo)**.
4. Nhấn chọn CSDL `flexidiet` vừa tạo $\rightarrow$ Chọn tab **Import** (hoặc tab **SQL**):
   - Import lần lượt: file `schema.sql`, tiếp theo là các file trong thư mục `seeds/` (từ `01_dishes.sql` đến `07_demo_accounts.sql`).
   - Hoặc mở file `schema.sql` và các file seed, sao chép nội dung và dán vào tab **SQL** rồi nhấn **Go**.

---

### Bước 3: Khởi chạy Web Server từ Terminal

Từ cửa sổ Terminal, di chuyển về thư mục ứng dụng `repo/FlexiDiet` và khởi động PHP Built-in Server:

```bash
# 1. Di chuyển về thư mục ứng dụng
cd ../

# 2. Khởi động Web Server trỏ Document Root vào thư mục 'public'
php -S localhost:8000 -t public
```

Khi Terminal hiển thị thông báo:
```text
[Date Time] PHP 8.x.x Development Server (http://localhost:8000) started
```
👉 **Hãy giữ nguyên cửa sổ Terminal này** trong suốt quá trình sử dụng để duy trì máy chủ hoạt động.

> **Trường hợp máy tính chưa cài đặt biến môi trường PATH:**
> Nếu gõ `php` hoặc `mysql` báo lỗi *command not found*, bạn chỉ cần gọi trực tiếp file thực thi trong thư mục cài XAMPP trên máy của bạn:
> - **Windows:** `<Đường_dẫn_XAMPP>\php\php.exe -S localhost:8000 -t public`
>   *(Ví dụ: `C:\xampp\php\php.exe` hoặc `D:\xampp\php\php.exe`)*
> - **macOS / Linux:** Sử dụng đường dẫn tương ứng của PHP trong XAMPP (thường là `/opt/lampp/bin/php`).

---

### Bước 4: Mở trình duyệt và sử dụng ứng dụng

Mở trình duyệt web và truy cập địa chỉ:
👉 **`http://localhost:8000`**

Dưới đây là các tài khoản thử nghiệm đã được chuẩn bị sẵn trong cơ sở dữ liệu:

| Vai trò (Role) | Email đăng nhập | Mật khẩu | Trang sau khi đăng nhập | Mô tả trải nghiệm |
| :--- | :--- | :--- | :--- | :--- |
| **Member (Người dùng)** | `demo@flexidiet.vn` | `Demo@123456` | [`app.html`](http://localhost:8000/app.html) | Theo dõi calo động, Kanban bữa ăn, Food Hub, tra cứu nguyên liệu/món, ghi nhận buổi tập. |
| **Admin (Quản trị viên)** | `admin@flexidiet.vn` | `Admin@123456` | [`admin.html`](http://localhost:8000/admin.html) | Studio quản trị cơ sở dữ liệu: nguyên liệu nền, công thức 67 món chuẩn, từ đồng nghĩa 3 miền (`ingredient_aliases`). |

---

## 🛑 4. Cách Dừng Máy Chủ Khi Kết Thúc Phiên Làm Việc

1. **Dừng Web Server:** Tại cửa sổ Terminal đang chạy lệnh `php -S`, nhấn tổ hợp phím **`Ctrl + C`**.
2. **Dừng MySQL:** Mở **XAMPP Control Panel** và nhấn nút **Stop** ở dòng MySQL.

---

## ❓ 5. Xử Lý Sự Cố Thường Gặp (Troubleshooting)

1. **Lỗi `Access denied for user 'root'@'localhost'`:**
   - Mặc định MySQL của XAMPP có user `root` và mật khẩu rỗng (`pass = ''`).
   - Nếu bạn đã đặt mật khẩu cho root trong XAMPP, hãy mở file `repo/FlexiDiet/backend/config.php` và điền mật khẩu của bạn vào mục `'pass' => 'mat_khau_cua_ban'`.
2. **Lỗi cổng `8000` đã bị chiếm dụng:**
   - Bạn có thể đổi sang cổng khác tùy ý, ví dụ: `php -S localhost:8080 -t public` rồi truy cập `http://localhost:8080`.
3. **Món ăn hoặc nguyên liệu hiển thị lỗi font tiếng Việt:**
   - Kiểm tra xem khi nạp database bạn đã bật cờ `--default-character-set=utf8mb4` chưa. Bảng mã chuẩn của hệ thống luôn là `utf8mb4`.

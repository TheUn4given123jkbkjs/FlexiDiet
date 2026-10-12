# Hướng Dẫn Khởi Chạy FlexiDiet Trên Localhost (Zero-Error Local Deployment Guide)

> **Dự án:** FlexiDiet – Smart Dynamic Nutrition & Workout Platform  
> **Môi trường:** Local Development (XAMPP / PHP 8.x / MySQL)  
> **Nguyên tắc:** Thiết kế tự động phát hiện môi trường, **cam kết 100% không bị lỗi "command not found"**.

---

## ⚡ CÁCH 1: KHỞI CHẠY TỰ ĐỘNG BẰNG 1-CLICK SCRIPT (KHUYÊN DÙNG NHẤT)

> Hệ thống đã được tích hợp sẵn 2 file script thông minh **tự động dò tìm đường dẫn XAMPP** (dù bạn cài ở ổ `C:\xampp`, `D:\xampp` hay bất kỳ đâu) mà không cần bạn phải tự thêm biến môi trường `PATH`.

### Bước 1: Bật MySQL trên XAMPP
Mở **XAMPP Control Panel**, nhấn nút **Start** ở dòng **MySQL** (đèn chuyển sang màu xanh lá).

### Bước 2: Thiết lập Database tự động
- Nhấp đúp chuột vào file: **`setup_database.bat`**  
- *(Hoặc trong PowerShell / Terminal, chỉ cần gõ: `.\setup_database.bat`)*
- Script sẽ tự động kết nối MySQL, tạo database `flexidiet` và nạp toàn bộ 18 bảng cùng 7 bộ dữ liệu mẫu (món ăn, nguyên liệu, bí danh, tài khoản).

### Bước 3: Khởi động Web Server
- Nhấp đúp chuột vào file: **`start_server.bat`**  
- *(Hoặc trong PowerShell / Terminal, chỉ cần gõ: `.\start_server.bat`)*
- Máy chủ sẽ tự động chạy tại cổng 8000 và hiển thị thông báo sẵn sàng.

### Bước 4: Mở trình duyệt và sử dụng
Truy cập: **`http://localhost:8000`**
- **Tài khoản Member:** `demo@flexidiet.vn` / `Demo@123456`
- **Tài khoản Admin:** `admin@flexidiet.vn` / `Admin@123456`

---

## 💻 CÁCH 2: DÙNG NÚT "SHELL" CỦA XAMPP CONTROL PANEL (CHUẨN XAMPP)

Nếu bạn muốn tự gõ lệnh nhưng không muốn bị lỗi `'mysql'/'php' is not recognized`:

1. Mở **XAMPP Control Panel**, nhấn **Start** ở dòng **MySQL**.
2. Nhìn sang cột bên phải của bảng XAMPP, nhấn nút **`Shell`**.  
   *(Cửa sổ dòng lệnh mở ra đã được nạp sẵn 100% đường dẫn của `php` và `mysql`)*.
3. Di chuyển đến thư mục đồ án và thực thi:
   ```cmd
   cd /d D:\record_by_me\tdtu\WEB\Midterm\repo\FlexiDiet\database
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"
   
   cd ..
   php -S localhost:8000 -t public
   ```

---

## 🛠️ CÁCH 3: CHẠY TRONG POWERSHELL / TERMINAL THƯỜNG

Nếu bạn mở cửa sổ PowerShell riêng mà chưa cấu hình biến môi trường, hãy thực hiện theo đúng trình tự sau để tránh lỗi:

### 1. Nạp biến môi trường cho phiên làm việc hiện tại:
*(Thay `D:\xampp` bằng đường dẫn thư mục cài đặt XAMPP trên máy của bạn nếu cài ở ổ C)*
```powershell
$env:Path += ";D:\xampp\mysql\bin;D:\xampp\php"
```

### 2. Thiết lập Database:
```powershell
cd repo/FlexiDiet/database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"
```

### 3. Khởi chạy Web Server:
```powershell
cd ..
php -S localhost:8000 -t public
```

---

## 🛑 Cách Dừng Server
- Tại cửa sổ Terminal/CMD đang chạy server, nhấn **`Ctrl + C`**.
- Trên XAMPP Control Panel, nhấn **Stop** ở dòng MySQL.

---

## 📋 Bảng Tài Khoản Kiểm Thử

| Phân quyền | Email | Mật khẩu | Trang truy cập sau đăng nhập |
| :--- | :--- | :--- | :--- |
| **Member (Người dùng)** | `demo@flexidiet.vn` | `Demo@123456` | [`app.html`](http://localhost:8000/app.html) (Kanban bữa ăn, Food Hub, Workout) |
| **Admin (Quản trị viên)** | `admin@flexidiet.vn` | `Admin@123456` | [`admin.html`](http://localhost:8000/admin.html) (Studio quản trị CSDL & món) |

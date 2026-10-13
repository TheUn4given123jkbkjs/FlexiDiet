# Hướng Dẫn Khởi Chạy FlexiDiet Trên Localhost (Zero-Error Local Deployment Guide)

> **Dự án:** FlexiDiet – Smart Dynamic Nutrition & Workout Platform  
> **Môi trường:** Local Development (XAMPP / PHP 8.x / MySQL 8.x + Python 3.10+ cho AI Vision)  
> **Nguyên tắc:** Tự động phát hiện môi trường, **gom toàn bộ tiến trình vào 1 file duy nhất (`run_flexidiet.bat`)**, cam kết 100% không bị lỗi "command not found".

---

## 🏛️ Tổng Quan Kiến Trúc Khởi Chạy

Hệ thống FlexiDiet trên môi trường Localhost bao gồm 3 thành phần chính:

| Dịch vụ | Công nghệ | Cổng (Port) | Vai trò |
| :--- | :--- | :--- | :--- |
| **MySQL Database** | MySQL / MariaDB (XAMPP) | `3306` | Lưu trữ 18 bảng CSDL và dữ liệu chuẩn 67 món ăn |
| **Web Server & Backend API** | PHP 8.x (Built-in Server) | `8000` | Giao diện Web, REST API, Phiên đăng nhập, Tính toán Calorie |
| **AI Vision Microservice** | Python 3.10+ (FastAPI + ONNX) | `8001` | Nhận diện món ăn Việt (YOLOv10m VietFood-67) |

---

## ⚡ CÁCH 1: KHỞI CHẠY BẰNG 1 FILE DUY NHẤT (KHUYÊN DÙNG NHẤT)

> Toàn bộ quá trình kiểm tra môi trường, nạp CSDL, cài đặt thư viện AI, khởi động Web & AI và mở trình duyệt đã được gom trọn vẹn vào **1 file script duy nhất: `run_flexidiet.bat`**.

### Bước 1: Khởi động MySQL trên XAMPP
1. Mở ứng dụng **XAMPP Control Panel**.
2. Nhấn nút **Start** ở dòng **MySQL** (chờ nút chuyển màu xanh lá và hiển thị Port 3306).

---

### Bước 2: Chạy file `run_flexidiet.bat`
* Nhấp đúp chuột vào file: **`run_flexidiet.bat`** tại thư mục đồ án.  
* *(Hoặc trong PowerShell / CMD gõ: `.\run_flexidiet.bat`)*

Script thông minh sẽ tự động thực hiện từ A đến Z:
1. **Dò tìm môi trường:** Tự tìm đường dẫn `php.exe`, `mysql.exe` (trong ổ `C:`, `D:`, `E:\xampp`) và `python.exe` dù bạn chưa cấu hình biến môi trường `PATH`.
2. **Kiểm tra & Khởi tạo CSDL:** Tự động kiểm tra database `flexidiet`. Nếu database chưa có, script sẽ tự động tạo và nạp toàn bộ 18 bảng cùng 7 bộ dữ liệu mẫu (`reload_all.sql`). Nếu đã có, dữ liệu của bạn sẽ được giữ nguyên an toàn.
3. **Kiểm tra thư viện AI:** Tự động kiểm tra các thư viện `fastapi`, `onnxruntime`, `numpy`, `pillow`... Nếu máy bạn chưa cài, script sẽ tự động chạy `pip install` thay bạn.
4. **Khởi động đồng thời cả 2 dịch vụ:** Bật Web Server (Port 8000) và AI Vision Service (Port 8001) trong các cửa sổ dịch vụ riêng.
5. **Mở trình duyệt Web:** Tự động mở trang chủ `http://localhost:8000`.

---

### Bảng Điều Khiển Nhanh trong `run_flexidiet.bat`:
Cửa sổ chính của `run_flexidiet.bat` sẽ trở thành bảng điều khiển trung tâm với các phím tắt tiện lợi:
* **`[O]` (Open Browser):** Mở lại trình duyệt Web bất cứ lúc nào.
* **`[R]` (Reload Database):** Tự động nạp lại CSDL gốc (`reload_all.sql`) khi bạn muốn reset dữ liệu thử nghiệm.
* **`[Q]` (Quit):** Dừng toàn bộ Web Server và AI Service an toàn, giải phóng cổng 8000 & 8001.

---

## 💻 CÁCH 2: DÙNG NÚT "SHELL" CỦA XAMPP CONTROL PANEL (CHUẨN XAMPP)

Nếu bạn muốn tự gõ lệnh thủ công từng bước:

1. Mở **XAMPP Control Panel**, nhấn **Start** ở dòng **MySQL**.
2. Nhấn nút **`Shell`** ở cột bên phải bảng XAMPP.
3. Di chuyển đến thư mục đồ án và thực thi:

```cmd
:: 1. Khởi tạo Database
cd /d D:\record_by_me\tdtu\WEB\Midterm\repo\FlexiDiet\database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"

:: 2. Chạy Web Server PHP (Port 8000)
cd ..
php -S localhost:8000 -t public
```

4. Mở thêm 1 cửa sổ CMD riêng để chạy AI Service (Port 8001):
```cmd
cd /d D:\record_by_me\tdtu\WEB\Midterm\repo\FlexiDiet
pip install -r ai_service\requirements.txt
python ai_service\app.py
```

---

## 🛠️ CÁCH 3: CHẠY TRONG POWERSHELL RIÊNG

Nếu bạn mở cửa sổ PowerShell riêng biệt:

### 1. Nạp biến môi trường cho phiên làm việc hiện tại:
*(Thay `D:\xampp` bằng đường dẫn thư mục XAMPP thực tế trên máy bạn)*
```powershell
$env:Path += ";D:\xampp\mysql\bin;D:\xampp\php"
```

### 2. Thiết lập Database:
```powershell
cd repo/FlexiDiet/database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"
```

### 3. Khởi chạy Web Server (Cửa sổ 1):
```powershell
cd ..
php -S localhost:8000 -t public
```

### 4. Khởi chạy AI Service (Cửa sổ 2):
```powershell
cd repo/FlexiDiet
python ai_service/app.py
```

---

## 📋 Bảng Tài Khoản Kiểm Thử Hệ Thống

| Phân quyền | Email | Mật khẩu | Trang truy cập & Chức năng chính |
| :--- | :--- | :--- | :--- |
| **Member (Người dùng)** | `demo@flexidiet.vn` | `Demo@123456` | [`app.html`](http://localhost:8000/app.html) <br> • **Food Hub**: Chụp ảnh nhận diện món AI, tìm kiếm món, tuỳ biến định lượng Calorie.<br>• **Kanban bữa ăn**: Kéo thả, điều chỉnh Macro (Carbs, Protein, Fat).<br>• **Workout Log**: Tính năng đốt năng lượng theo hệ số chuẩn MET.<br>• **Water Tracker**: Theo dõi lượng nước uống trong ngày. |
| **Admin (Quản trị viên)** | `admin@flexidiet.vn` | `Admin@123456` | [`admin.html`](http://localhost:8000/admin.html) <br> • **#stats (Tổng quan)**: Biểu đồ tăng trưởng tài khoản & bữa ăn.<br>• **#obs (Hiệu năng AI)**: Giám sát Latency, Throughput, Phân bổ HTTP Status 24h qua.<br>• **#learn (Phân tích nhận diện)**: Heatmap tần suất 67 nhãn, Top món nhận diện, Cặp món đoán nhầm, Trích xuất Dataset huấn luyện lại.<br>• **#feedback (Nhật ký phản hồi)**: Xem chi tiết phản hồi đúng/sai của người dùng.<br>• **#dishes (Danh mục món)**: Quản lý 67 món ăn và công thức dinh dưỡng. |

---

## 🩺 Kiểm Tra Trạng Thái Dịch Vụ (Health Checks)

Bạn có thể kiểm tra nhanh tình trạng hoạt động của các dịch vụ bằng các liên kết sau:

* **Backend PHP API:** [`http://localhost:8000/api/index.php?route=/ping`](http://localhost:8000/api/index.php?route=/ping)  
  *(Kỳ vọng: `{"data":{"status":"ok","service":"FlexiDiet PHP API"}}`)*
* **AI Vision Microservice:** [`http://127.0.0.1:8001/health`](http://127.0.0.1:8001/health)  
  *(Kỳ vọng: `{"status":"healthy","model_version":"YOLOv10m-VietFood67"}`)*
* **Tài liệu Tương Tác AI (Swagger UI):** [`http://127.0.0.1:8001/docs`](http://127.0.0.1:8001/docs)

---

## 🛑 Cách Dừng Hệ Thống Khi Không Sử Dụng

* **Nếu chạy bằng `run_flexidiet.bat`:** Đơn giản chỉ cần nhấn phím **`Q`** tại cửa sổ điều khiển. Script sẽ tự động đóng toàn bộ tiến trình Web Server và AI Service.
* **Nếu chạy thủ công:** Nhấn tổ hợp phím **`Ctrl + C`** tại các cửa sổ dòng lệnh tương ứng.
* Trên **XAMPP Control Panel**, nhấn nút **Stop** ở dòng **MySQL**.

---

## ❓ Xử Lý Sự Cố Thường Gặp (Troubleshooting)

1. **Lỗi `Port 8000 is already in use` hoặc `Port 8001 is already in use`:**
   - Nguyên nhân: Có một tiến trình PHP hoặc Python cũ đang chạy ngầm.
   - Khắc phục: Nhấn phím `Q` trong `run_flexidiet.bat` hoặc mở PowerShell gõ:
     ```powershell
     Stop-Process -Name php -Force -ErrorAction SilentlyContinue
     Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }
     ```

2. **MySQL trên XAMPP không khởi động được (báo lỗi Port 3306):**
   - Đảm bảo bạn không bật cùng lúc cả MySQL Service của Windows và XAMPP.
   - Nếu máy tính đã có cài đặt MySQL độc lập từ trước, vào `services.msc`, tìm dịch vụ `MySQL` và chọn **Stop**, sau đó nhấn **Start** trên XAMPP Control Panel.

3. **Chụp ảnh AI báo "Không thể kết nối AI Service":**
   - Đảm bảo cửa sổ AI Service đang chạy và mở cổng `8001`.
   - Kiểm tra xem bạn có thể mở trang [`http://127.0.0.1:8001/health`](http://127.0.0.1:8001/health) trên trình duyệt hay không.

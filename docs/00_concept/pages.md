# Cấu Trúc Các Trang & Luồng Điều Hướng (Pages & Routing Specification)

## 🌐 Tổng Quan Sitemap & Routes

| STT | Trang | URL Route | Loại Trang / Modal | Mô Tả & Chức Năng Chính |
| :---: | :--- | :--- | :--- | :--- |
| 1 | **Trang Chủ (Home)** | `/` | Public Page | Giới thiệu ứng dụng, tính năng nổi bật, nút CTA. |
| - | **Modal Đăng Nhập** | - | Popup Modal (trên Trang Chủ) | Đăng nhập nhanh bằng Email/Password. |
| 2 | **Trang Đăng Ký & Onboarding** | `/register` | Standalone Page (Multi-step) | Đăng ký tài khoản + Khảo sát thể chất & tính TDEE. |
| 3 | **Trang Dashboard** | `/dashboard` | Protected Page | Tổng quan Calo In/Out, dinh dưỡng, nước uống trong ngày. |
| 4 | **Trang Nhật Ký Ăn Uống & AI** | `/food-log` | Protected Page | Ghi món ăn, chụp ảnh AI + Prompt nhận diện Calo, tìm món. |
| 5 | **Trang Nhật Ký Luyện Tập** | `/workout-log` | Protected Page | Ghi nhận bài tập (chạy bộ, gym...) & tính calo tiêu hao. |
| 6 | **Trang Gợi Ý Món Ăn** | `/suggestions` | Protected Page | Đề xuất món ăn phù hợp với calo/protein còn thiếu trong ngày. |
| 7 | **Trang Hồ Sơ & Thống Kê** | `/profile` | Protected Page | Quản lý thông tin cá nhân & xem biểu đồ tiến trình dài hạn. |

---

## 📱 Chi Tiết Chức Năng Từng Trang

### 1. Trang Chủ (Home Page) - `/`
* **Mục đích:** Thu hút người dùng mới & làm cổng vào cho người dùng cũ.
* **Thành phần giao diện:**
  * **Header/Navbar:** Logo, Menu điều hướng (Tính năng, Giới thiệu), Nút **"Đăng nhập"** (mở Modal) và Nút **"Đăng ký"** (chuyển sang `/register`).
  * **Hero Section:** Banner ấn tượng giới thiệu phương pháp *"Ăn uống thoải mái - Quản lý calo động"*.
  * **Feature Highlights:** Giới thiệu tính năng AI Vision chụp ảnh món ăn, Ngân sách calo động, Workout tracker.
  * **Footer:** Thông tin bản quyền, liên hệ.

### 2. Modal Đăng Nhập (Login Popup)
* **Cơ chế:** Nổi (Modal) trực tiếp trên Trang Chủ mà không chuyển trang.
* **Thành phần:**
  * Form nhập Email & Mật khẩu.
  * Nút "Đăng nhập".
  * Link "Quên mật khẩu?" và "Chưa có tài khoản? Đăng ký ngay" (dẫn tới `/register`).

### 3. Trang Đăng Ký & Onboarding (`/register`)
* **Cơ chế:** Trang riêng biệt dạng **Form từng bước (Multi-step Form)**.
* **Các bước (Steps):**
  * **Step 1 - Tài khoản:** Email, Mật khẩu, Tên người dùng.
  * **Step 2 - Chỉ số thể chất:** Giới tính, Tuổi, Chiều cao, Cân nặng hiện tại, Mức độ vận động.
  * **Step 3 - Mục tiêu:** Chọn mục tiêu (Giảm cân / Duy trì / Tăng cân / Tăng cơ) & Cân nặng mong muốn.
  * **Step 4 - Khởi tạo:** Hệ thống tính toán BMR, TDEE và hiển thị Ngân sách Calo/ngày ➔ Chuyển hướng thẳng vào `/dashboard`.

### 4. Trang Dashboard (`/dashboard`)
* **Thành phần:**
  * **Thanh năng lượng ngày:** Hiển thị `Calo Mục Tiêu + Calo Tiêu Hao - Calo Đã Nạp = Calo Còn Lại`.
  * **Biểu đồ Dinh dưỡng (Macronutrients):** Tiến độ Protein, Carb, Fat (g).
  * **Widget Nhanh:** Theo dõi Nước uống (Lít), Bước chân, Lịch tập trong ngày.
  * **Tóm tắt bữa ăn:** Danh sách các món đã ăn trong ngày.

### 5. Trang Nhật Ký Ăn Uống & AI (`/food-log`)
* **Thành phần:**
  * **Khu vực AI Vision:** Khung upload/chụp ảnh món ăn + Ô nhập prompt mô tả (VD: *"1 bát cơm, 2 miếng gà chiên"*). Nút *"Phân tích AI"*.
  * **Kết quả AI & Correction:** Hiển thị món ăn + Calo ước tính cho phép người dùng sửa lại trước khi lưu.
  * **Thư viện món & Tìm kiếm:** Tìm món ăn Việt Nam có sẵn hoặc tự tạo món cá nhân (My Foods).
  * **Timeline Nhật ký:** Hiển thị chi tiết theo Sáng / Trưa / Tối / Phụ.

### 6. Trang Nhật Ký Luyện Tập (`/workout-log`)
* **Thành phần:**
  * Form chọn môn thể thao (Chạy bộ, Đạp xe, Gym, Bơi lội...) + Nhập thời lượng / quãng đường.
  * Bộ tính Calo tiêu hao tự động.
  * Lịch sử các buổi tập trong tuần/tháng.

### 7. Trang Gợi Ý Món Ăn (`/suggestions`)
* **Thành phần:**
  * Thanh hiển thị: *"Bạn còn thiếu 550 kcal & 35g Protein cho hôm nay"*.
  * Danh sách thẻ món ăn (Food Cards) phù hợp với lượng calo còn lại.
  * Bộ lọc: Món nhiều Protein, Món Việt, Dễ làm, Dưới 400 kcal...

### 8. Trang Hồ Sơ & Thống Kê (`/profile`)
* **Thành phần:**
  * Form cập nhật chỉ số cơ thể (khi tăng/giảm cân).
  * Biểu đồ đường (Line chart) theo dõi tiến trình cân nặng theo tuần/tháng.
  * Biểu đồ cột thể hiện mức độ tuân thủ calo mục tiêu.

---


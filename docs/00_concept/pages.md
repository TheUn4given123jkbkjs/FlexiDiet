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

  * **Step 3 - Mục tiêu:** Chọn mục tiêu (Giảm cân / Duy trì / Tăng cân / Tăng cơ) & Cân nặng mong muốn.
  * **Step 4 - Khởi tạo:** Hệ thống tính toán BMR, TDEE và hiển thị Ngân sách Calo/ngày ➔ Chuyển hướng thẳng vào `/dashboard`.

### 4. Trang Dashboard (`/dashboard` - Single Page App Workspace)
* **Thành phần:**
  * **Thanh năng lượng ngày:** Hiển thị `Ngân Sách Năng Lượng` với vòng cung trực quan hóa, Calo mục tiêu, Calo tập được cộng, Calo đã ăn và Calo còn lại.
  * **Phân bổ Dinh dưỡng (Macronutrients):** Tiến độ Protein, Carbohydrate, Chất béo (g).
  * **Widget Nhanh:** Theo dõi Nước uống (+250ml mỗi click), Lịch tập trong ngày.
  * **Thẻ hành động nhanh (Quick Action Tiles - "Bạn muốn làm gì tiếp?"):**
    * *Thẻ 1 - Thêm bữa ăn:* Mở Modal pop-up ghi nhanh món ăn từ danh mục 67 món chuẩn + món đã lưu.
    * *Thẻ 2 - Quét món ăn AI:* Điều hướng trực tiếp sang Food Hub ➔ Subtab 1 (Quét ảnh AI).
    * *Thẻ 3 - Ghi buổi tập:* Mở Modal pop-up ghi nhận bài tập thể thao.
    * *Thẻ 4 - Gợi ý món ăn:* Điều hướng trực tiếp sang Food Hub ➔ Subtab 4 (Gợi ý món ăn).
  * **Nhật ký dinh dưỡng hôm nay:** Tóm tắt bữa ăn theo Sáng / Trưa / Tối / Phụ.

### 5. Trang Ăn Uống & Dinh Dưỡng - Food Hub (`/food` / `#food`)
Được tổ chức thành **4 Subtabs chuyên biệt**:
* **Subtab 1 - Quét ảnh món ăn AI:**
  * Khung tải lên / chụp ảnh món ăn hoặc chọn ảnh mẫu (Phở bò, Cơm tấm, Bún chả...).
  * Laser scan animation & gọi dịch vụ AI Vision (YOLOv10m 67 lớp ẩm thực Việt).
  * Hiển thị món dự đoán, độ tin cậy và gợi ý công thức dinh dưỡng tương ứng.
  * *(Ghi chú: Đã loại bỏ phần mô tả tự nhiên để tập trung chuẩn hóa nhận diện thị giác máy tính Computer Vision).*
* **Subtab 2 - Danh mục món ăn (Catalog):**
  * Danh mục tra cứu 67 món chuẩn Việt Nam (Viện Dinh Dưỡng VDD) và các món do người dùng tự lưu ("Món Của Tôi").
  * Bộ lọc theo nguồn gốc (`Tất cả`, `67 Món Chuẩn`, `Món Của Tôi`) và ô tìm kiếm tức thì.
  * Cho phép chọn món nạp trực tiếp vào nhật ký hoặc tải sang Subtab 3 để tùy chỉnh.
* **Subtab 3 - Tùy chỉnh & Phối món (Composer):**
  * 3 thanh trượt trực quan Kcal, Protein, Carb, Fat biến thiên theo thời gian thực khi gia giảm nguyên liệu.
  * Bảng nguyên liệu chi tiết: chỉnh sửa khối lượng (grams), xóa nguyên liệu, hoặc thêm nguyên liệu mới từ CSDL.
  * Nút lưu món vào "Món Của Tôi" (UC13) hoặc "Xác nhận ghi nhật ký (Lưu Snapshot)".
* **Subtab 4 - Gợi ý món ăn (Suggestions):**
  * Đề xuất các món ăn thông minh dựa trên lượng calo và protein còn thiếu trong ngày.
  * Hỗ trợ các bộ lọc: Nhiều protein, Dễ làm, Dưới 400 kcal...

### 6. Modal Pop-up Ghi Nhanh Hoạt Động (`#createQuickModal`)
Nổi trực tiếp trên mọi màn hình mà không làm gián đoạn luồng làm việc:
* **Tab Ghi Món Ăn (Quick Food Log):**
  * Ô tìm kiếm tức thì hỗ trợ cả 67 món chuẩn và món cá nhân đã lưu (`qfDishSearchInput`).
  * Khử dấu tiếng Việt Unicode toàn diện (gõ `pho` hay `phở`, `bun cha` hay `bún chả` đều tìm thấy).
  * Thẻ xem trước chỉ số Kcal, Protein, Carb, Fat tự động nhân theo hệ số khẩu phần (0.5, 1.0, 1.5, 2.0).
  * Nút liên kết nhanh: *"Muốn gia giảm nguyên liệu chi tiết? Tùy chỉnh trong Food Hub →"*.
  * Nút *"Lưu vào nhật ký"* lưu trực tiếp bản ghi snapshot vào CSDL MySQL.
* **Tab Ghi Bài Tập (Quick Workout Log):**
  * Chọn môn thể thao (Chạy bộ, Đạp xe, Gym, Bơi lội...) + Nhập thời gian tập (phút).
  * Tự động tính calo tiêu hao theo chỉ số METs và cộng vào ngân sách năng lượng ngày.

### 7. Trang Nhật Ký Chi Tiết (`/history` / `#history`)
* **Thành phần:**
  * Bảng lịch sử toàn bộ các bữa ăn và buổi tập đã ghi nhận theo ngày.
  * Hỗ trợ xem chi tiết nguyên liệu snapshot của từng bữa ăn.
  * Chức năng xóa bản ghi và tự động cập nhật lại số liệu năng lượng.

### 8. Trang Luyện Tập (`/workout` / `#workout`)
* **Thành phần:**
  * Form ghi bài tập chi tiết (loại bài tập, thời gian, quãng đường).
  * Heatmap cường độ vận động cả năm (Workout Activity Heatmap).
  * Lịch sử các buổi tập gần đây.

### 9. Trang Hồ Sơ & Thống Kê (`/profile` / `#profile`)
* **Thành phần:**
  * Cập nhật chỉ số cơ thể: Chiều cao, cân nặng, mục tiêu, công thức BMR (Mifflin-St Jeor / Katch-McArdle) và chính sách calo tập.
  * Biểu đồ đường (Line chart) theo dõi tiến trình cân nặng.
  * Lịch sử cân nặng theo từng ngày (`weight_logs`).

---


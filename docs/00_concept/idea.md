# Ý Tưởng Web App: Quản Lý Ăn Uống & Luyện Tập Linh Hoạt (FlexiDiet)

## Ý tưởng cốt lõi
Một Web App giúp người dùng quản lý chế độ ăn uống và luyện tập khoa học theo phương pháp **"Ngân sách Calo Động"**. Người dùng có thể ăn uống thoải mái theo sở thích, ứng dụng sẽ tự động điều chỉnh calo khả dụng dựa trên lượng vận động hằng ngày và tích hợp AI nhận diện món ăn qua hình ảnh.

---

## 6 Module Chức Năng Chính (MVP)

### 1. Quản lý Hồ sơ & Tính toán Calo (User Profile & Energy Engine)
* Nhập chỉ số cá nhân (chiều cao, cân nặng, độ tuổi, mức độ vận động, mục tiêu tăng/giảm cân).
* Tự động tính toán chỉ số BMR, TDEE và đưa ra ngân sách Calo mục tiêu mỗi ngày.

### 2. Ngân sách Calo Động & Chế độ ăn linh hoạt (Dynamic Calorie Budget)
* Không bắt buộc ăn theo thực đơn cố định.
* Tự động tính toán số calo còn lại trong ngày theo công thức:
  `Calo còn lại = Calo mục tiêu + Calo tiêu hao (Tập luyện) - Calo đã nạp (Ăn uống)`

### 3. Nhận diện & Ghi nhận Món ăn bằng AI (AI Food Logging)
* **Chụp/tải ảnh món ăn + Nhập prompt mô tả** (ví dụ: *"1 bát cơm, 2 miếng gà chiên"*).
* AI tự động phân tích và đưa ra ước tính Calo + Protein/Carb/Fat để người dùng xác nhận hoặc chỉnh sửa.
* Hỗ trợ tìm kiếm và ghi nhận món ăn thủ công.

### 4. Quản lý & Theo dõi Luyện tập (Workout Tracker)
* Ghi nhận các bài tập (chạy bộ, gym, đạp xe...) kèm thời gian/quãng đường.
* Tự động tính calo tiêu hao từ bài tập và **cộng trực tiếp vào ngân sách ăn uống trong ngày**.

### 5. Gợi ý Món ăn Thời gian thực (Smart Meal Suggestions)
* Dựa trên số calo và dinh dưỡng còn thiếu trong ngày để gợi ý danh sách món ăn phù hợp cho bữa tiếp theo.

### 6. Dashboard & Thống kê Tổng quan (Dashboard & Analytics)
* Màn hình chính hiển thị trực quan: Calo đã nạp/tiêu hao, chỉ số dinh dưỡng (Protein/Carb/Fat), lượng nước uống.
* Biểu đồ theo dõi tiến trình cân nặng và lịch sử ăn uống/luyện tập theo ngày/tuần.

---

## 🌐 Cấu Trúc Trang & Luồng Xác Thực (Pages & Auth Flow)

1. **Trang Chủ (Home Page):** Landing page giới thiệu ứng dụng.
   * **Đăng Nhập (Login):** Hiển thị dạng **Modal / Popup nổi** ngay trên trang Home (tiện lợi, giữ nguyên ngữ cảnh người dùng).
2. **Trang Đăng Ký & Khảo Sát (`/register`):** 
   * Nhảy vào **Trang riêng** (Multi-step Onboarding Form): Tạo tài khoản ➔ Nhập thể chất (chiều cao, cân nặng) ➔ Chọn mục tiêu ➔ Khởi tạo ngân sách calo & dẫn vào Dashboard.
3. **Trang Dashboard (`/dashboard`):** Màn hình chính theo dõi Calo In/Out & dinh dưỡng trong ngày.
4. **Trang Nhật Ký Ăn Uống & AI (`/food-log`):** Ghi món ăn, chụp ảnh AI, tìm kiếm món.
5. **Trang Nhật Ký Luyện Tập (`/workout-log`):** Theo dõi bài tập & calo tiêu hao.
6. **Trang Gợi Ý Món Ăn (`/suggestions`):** Khám phá món ăn phù hợp với calo còn lại.
7. **Trang Hồ Sơ & Thống Kê (`/profile`):** Chỉnh sửa chỉ số & xem biểu đồ tiến trình.
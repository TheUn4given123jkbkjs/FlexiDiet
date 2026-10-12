# FlexiDiet: Nhật Ký Phát Triển & Quyết Định Kỹ Thuật (Development Log)

> **Pha:** 3 – Construction  
> **Thời gian:** 12/10/2026  
> **Mục tiêu:** Ghi nhận các quyết định thiết kế chi tiết, tối ưu hóa giao diện và nhật ký sửa lỗi trong quá trình lập trình thực tế.

---

## 1. Nhật Ký Tính Năng & Thay Đổi Kiến Trúc

### 1.1. Chuẩn Hóa 4 Subtabs trong Ăn Uống (Food Hub)
- **Bối cảnh:** Trước đây các tính năng quét ảnh, danh mục và phối món nằm rải rác hoặc chuyển đổi chưa nhất quán.
- **Giải pháp:** Tổ chức Food Hub thành 4 Subtabs chuyên biệt:
  1. **Subtab 1 - Quét ảnh món ăn AI:** Giao diện camera / upload ảnh món, animation laser quét ảnh, gọi dịch vụ AI Vision (YOLOv10m 67 lớp ẩm thực Việt Nam). *Quyết định kỹ thuật: Loại bỏ ô nhập mô tả tự nhiên (NLP) ở v1 để tập trung tối đa vào độ chính xác của thị giác máy tính.*
  2. **Subtab 2 - Danh mục món ăn (Catalog):** Bộ lọc 3 chế độ (`Tất cả`, `67 Món Chuẩn`, `Món Của Tôi`). Hỗ trợ tìm kiếm tức thì.
  3. **Subtab 3 - Tùy chỉnh & Phối món (Composer):** 3 thanh trượt Kcal/P/C/F biến thiên trực quan theo thời gian thực khi tinh chỉnh từng gam nguyên liệu; nút lưu món cá nhân (`/dishes` - UC13) và nút lưu snapshot nhật ký (`/meals`).
  4. **Subtab 4 - Gợi ý món ăn (Suggestions):** Đề xuất thực đơn thông minh dựa trên lượng calo và protein còn thiếu trong ngày.

### 1.2. Ánh Xạ 4 Thẻ Hành Động Nhanh Trên Dashboard (Quick Action Tiles)
- **Bối cảnh:** Người dùng cần các lối tắt thao tác nhanh nhất từ màn hình chính.
- **Ánh xạ hành vi:**
  - **Thẻ 1 - Thêm bữa ăn:** Kích hoạt modal pop-up `#createQuickModal` ở tab "Ghi Món Ăn".
  - **Thẻ 2 - Quét món ăn AI:** Chuyển tab sang Food Hub và tự động chọn Subtab 1 (Quét ảnh AI).
  - **Thẻ 3 - Ghi buổi tập:** Kích hoạt modal pop-up `#createQuickModal` ở tab "Ghi Bài Tập".
  - **Thẻ 4 - Gợi ý món ăn:** Chuyển tab sang Food Hub và tự động chọn Subtab 4 (Gợi ý món ăn).

### 1.3. Pop-Up Ghi Nhanh Món Ăn (Quick Food Autocomplete & Snapshot)
- **Quyết định thiết kế:** Thay vì yêu cầu người dùng gõ tên và tự nhập từng chỉ số Kcal/P/C/F, pop-up hoạt động như một công cụ tìm kiếm tức thì từ 2 nguồn dữ liệu có sẵn:
  - 67 món chuẩn của hệ thống (gắn nhãn `67 Món Chuẩn`).
  - Toàn bộ món cá nhân do người dùng đã lưu (gắn nhãn `Món Của Tôi`).
- **Trải nghiệm người dùng:**
  - Tự động gợi ý khi gõ từ khóa.
  - Hiển thị thẻ xem trước dinh dưỡng chuẩn và tự động nhân theo tỷ lệ phần ăn (0.5x, 1x, 1.5x, 2x...).
  - Cung cấp liên kết *"Muốn gia giảm nguyên liệu chi tiết? Tùy chỉnh trong Food Hub →"* khi người dùng có nhu cầu tinh chỉnh sâu.
  - Gửi dữ liệu `{ dish_id, meal_type, serving_factor }` lên API `/meals` để server tự động bóc tách snapshot nguyên liệu và lưu vào MySQL.

---

## 2. Nhật Ký Khắc Phục Lỗi Kỹ Thuật (Bug Fixes)

### 2.1. Lỗi Khử Dấu Tiếng Việt Khi Tìm Món Ăn (Unicode Horn Decomposition)
- **Hiện tượng:** Người dùng gõ `pho` không tìm thấy món "Phở Bò" hay "Phở".
- **Nguyên nhân:** Hàm `removeVietnameseTones` cũ sử dụng chuẩn Unicode NFD kết hợp regex loại bỏ combining diacritics (`\u0300-\u036f`). Ký tự `ở` khi bóc tách dấu hỏi thì phần thân còn lại là `ơ` (U+01A1, chữ O có sừng), không phải là chữ `o` (U+006F). Do đó `"Phở"` bị biến thành `"phơ"`, khiến kiểm tra `.includes("pho")` trả về `false`.
- **Giải pháp:**
  - Xây dựng regex ánh xạ triệt để toàn bộ nguyên âm tiếng Việt có dấu, mũ, sừng (`ơ -> o`, `ư -> u`, `đ -> d`...).
  - Bổ sung từ điển biệt danh món thông minh (`QUICK_FOOD_ALIASES`): gõ `pho`, `phở`, `pho bo`, `phở tái` đều tự động xếp hạng ưu tiên đưa "Phở" lên vị trí đầu tiên.

### 2.2. Lỗi Giới Hạn Truy Vấn Cắt Cụt Danh Sách Món Tại Backend (`LIMIT 30`)
- **Hiện tượng:** Các món có tên bắt đầu từ chữ H/K trở đi (như "Phở", "Thịt kho", "Xôi") không xuất hiện trong danh sách cache client-side.
- **Nguyên nhân:** Endpoint `/foods` trong `Routes.php` trước đó cấu hình cứng `LIMIT 30`. Vì dữ liệu được sắp xếp theo bảng chữ cái A–Z, 37 món phía sau bị cắt bỏ hoàn toàn.
- **Giải pháp:** Tăng limit lên 300 món, chọn thêm cột `name_norm` và bổ sung điều kiện tìm kiếm qua `dish_code`.

### 2.3. Lỗi Bất Đồng Bộ Tham Số Form Trong `backend-sync.js`
- **Hiện tượng:** Bấm nút "+ Lưu vào nhật ký" trong pop-up không phản hồi.
- **Nguyên nhân:** `backend-sync.js` ghi đè hàm `handleQuickFoodSubmit` và cố gắng đọc `document.getElementById('qfName').value`, nhưng các trường form này đã được thay thế bằng `#qfSelectedDishId` và `#qfPortionRatio`.
- **Giải pháp:** Cập nhật hàm đọc `dish_id` và `serving_factor`, gọi `fdSaveMeal()` đồng bộ trực tiếp với MySQL.

### 2.4. Tràn Màn Hình Pop-Up Trên Thiết Bị Có Chiều Cao Giới Hạn
- **Hiện tượng:** Trên màn hình laptop hoặc cửa sổ trình duyệt có chiều cao nhỏ (ví dụ viewport height < 700px), nút submit bị ẩn dưới chân màn hình.
- **Giải pháp:** Thêm `max-height: 92vh; overflow-y: auto;` cho `.modal-dialog` và `overflow-y: auto;` cho `.modal-backdrop` trong `app.css`.

---

## 3. Trạng Thái Xác Thực & Kiểm Thử
- **Browser Automation Verification:** Đã kiểm thử thành công bằng browser subagent:
  - Gõ `pho` và `phở` hiển thị ngay "Phở" (382 kcal).
  - Chọn món và chọn phần ăn 1.0, bấm lưu thành công vào MySQL.
  - Dashboard và thanh năng lượng cập nhật tức thì.
- **Git Commit:** `9237ca6 - feat: enhance quick food search with Vietnamese tone normalization and action tiles mapping`.

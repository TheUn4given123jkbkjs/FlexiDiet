# FlexiDiet: Đặc Tả Yêu Cầu Phần Mềm (SRS) – Bản nháp

> **Pha:** 2 – Elaboration, Iteration E1 (05/10 – 11/10/2026)
> **Trạng thái:** Bản nháp v1.1, chờ nhóm rà soát. Mốc hoàn tất SRS: **07/10/2026**.
> **v1.1:** sửa Baseline (hệ số sinh hoạt cố định, bỏ khảo sát mức vận động), calo tập tính ở cấp ngày, quy tắc sửa mục nhật ký và tính calo ở server. Xem mục 8.
> **Nguồn:** `01_business_modeling.md` (v1.1), `idea.md`, `pages.md`, `SKILL.md`.
> Mục có nhãn **[CẦN CHỐT]** là giá trị chưa có căn cứ, phải chốt bằng tài liệu tham khảo hoặc kết quả PoC trước khi coi là yêu cầu chính thức.

---

## 1. Giới thiệu

### 1.1. Mục đích
Tài liệu đặc tả yêu cầu chức năng (FR) và phi chức năng (NFR) của FlexiDiet, làm cơ sở cho thiết kế (`02_architecture_design.md`, `02_database_design.md`), kịch bản Use Case chi tiết (`02_usecase_specifications.md`) và kiểm thử.

### 1.2. Phạm vi
FlexiDiet là web app tiếng Việt, responsive (mobile-first), giúp người dùng quản lý ăn uống và luyện tập theo "Ngân sách Calo Động", ghi nhận món Việt bằng ảnh + mô tả với sự hỗ trợ của dịch vụ AI.

**Ngoài phạm vi v1:** app native, đồng bộ thiết bị đeo, barcode, mạng xã hội, thanh toán/premium, tư vấn y tế, tính calo theo nhịp tim.

### 1.3. Thuật ngữ

| Thuật ngữ | Giải thích |
|---|---|
| BMR | Năng lượng chuyển hóa cơ bản |
| Baseline | BMR × hệ số sinh hoạt **cố định** (calo duy trì trong ngày, chưa gồm tập luyện có chủ đích) |
| Ngân sách | Baseline ± điều chỉnh mục tiêu + calo tập được cộng của ngày |
| Calo thô | Calo tiêu hao của một buổi tập, chưa áp chính sách cộng. Là thứ duy nhất được lưu trên bản ghi buổi tập |
| Calo tập được cộng | Phần calo tập cộng vào ngân sách, **tính ở cấp ngày** từ Σ calo thô theo chính sách của ngày |
| Số buổi tập mục tiêu | Số buổi người dùng muốn tập mỗi tuần (0–7). Chỉ dùng để nhắc nhở, **không đi vào công thức ngân sách** |
| MET | Đương lượng chuyển hóa của bài tập |
| Active calories | Calo tiêu hao do vận động, không gồm BMR |
| Cân nặng hiện tại | Bản ghi mới nhất trong `weight_logs`. `user_profiles` không lưu cột cân nặng; tránh lệch giữa hai nguồn |
| Danh mục hệ thống | Nguyên liệu, công thức chuẩn, bảng quy đổi khẩu phần do Admin quản lý |
| Danh mục cá nhân | Nguyên liệu/món tự tạo và "Món đã lưu" của từng người dùng |
| Snapshot | Bản ghi bất biến của một bữa ăn tại thời điểm ghi |
| Đường nhận diện / đường nguyên liệu | Hai nhánh xử lý món ăn (xem 01, mục 9.3) |

---

## 2. Mô tả tổng quan

### 2.1. Actors

| Actor | Loại | Vai trò |
|---|---|---|
| Khách (Guest) | Chính | Xem trang chủ, đăng ký, đăng nhập |
| Người dùng (Member) | Chính | Ghi nhận, theo dõi, xem gợi ý |
| Quản trị viên (Admin) | Chính | Quản lý danh mục hệ thống (v1: bằng script seed) |
| Dịch vụ AI nhận diện | Phụ | Nhận diện món, tách mô tả thành nguyên liệu (tùy chọn) |
| CSDL dinh dưỡng | Phụ | Nguồn tra cứu calo/macro |

### 2.2. Kiến trúc tổng quát (ràng buộc thiết kế)
Trình duyệt (HTML/CSS/JS/Bootstrap) → PHP thuần + PDO + MySQL → dịch vụ suy luận Python (FastAPI + ONNX Runtime) gọi qua HTTP (D11, D14, D16, D18).

### 2.3. Ràng buộc
- Nhóm 3 người, hạn nộp **25/10/2026**, đóng băng tính năng từ **22/10** (D27).
- Mô hình AI suy luận phía server, không chạy trên trình duyệt (D14).
- Món đã ghi nhận lưu dạng snapshot; món đã lưu lưu theo nguyên liệu + gram (D23, 01 mục 9.4).
- Không có luồng Admin duyệt món người dùng (D22).
- Cân nặng lưu duy nhất trong `weight_logs` (mỗi ngày một bản ghi); `user_profiles` không giữ cột cân nặng.

### 2.4. Giả định
- A1. Người dùng có điện thoại chụp ảnh và trình duyệt hiện đại.
- A2. Số món có công thức chuẩn ở v1 là 30–50 (mức tối thiểu chấp nhận do nhóm ghi ra ở mốc 07/10, D26).
- A3. Người dùng tự nhập calo tiêu hao từ thiết bị (không đồng bộ tự động).
- A4. Hệ số sinh hoạt là một giá trị cố định cho mọi người dùng. Người có công việc chân tay có thể bị ước tính ngân sách hơi thấp; sai về phía thận trọng nên chấp nhận ở v1. Mọi calo tập luyện chỉ đi vào ngân sách qua các buổi tập đã ghi (UC07).

---

## 3. Yêu cầu chức năng (FR)

Ưu tiên theo MoSCoW (D5): **M** = Must, **S** = Should, **C** = Could.

### FR-01. Tài khoản, hồ sơ và Energy Engine (UC01, UC02, UC03)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-01.1 | Guest đăng ký tài khoản qua form nhiều bước: tài khoản → chỉ số thể chất → mục tiêu → khởi tạo ngân sách | M |
| FR-01.2 | Email là duy nhất; mật khẩu được băm trước khi lưu | M |
| FR-01.3 | Đăng nhập bằng Email/Mật khẩu qua modal trên trang chủ; đăng xuất | M |
| FR-01.4 | Thu thập: giới tính, tuổi, chiều cao, cân nặng, mục tiêu (giảm cân / duy trì / tăng cân / tăng cơ), cân nặng mong muốn, **số buổi tập mục tiêu mỗi tuần (0–7, chỉ dùng cho nhắc nhở, không ảnh hưởng ngân sách)**; % mỡ cơ thể (tùy chọn, chỉ cần khi dùng Katch-McArdle). **Không hỏi mức độ vận động** | M |
| FR-01.5 | Tính BMR theo Mifflin-St Jeor (mặc định) và Katch-McArdle (tùy chọn, cần % mỡ), thiết kế theo Strategy | M |
| FR-01.6 | Baseline = BMR × **hệ số sinh hoạt cố định cho mọi người dùng** (ví dụ 1,2 **[CẦN CHỐT: dẫn nguồn]**). Ngân sách mục tiêu = Baseline ± điều chỉnh theo mục tiêu, lấy từ bảng tham số (ví dụ giảm cân −15%, duy trì 0, tăng cân/tăng cơ +10% Baseline **[CẦN CHỐT: dẫn nguồn]**) | M |
| FR-01.7 | Member sửa hồ sơ; khi cập nhật cân nặng, ghi vào `weight_logs` (mỗi ngày một bản ghi, ghi lại thì thay thế); `user_profiles` không giữ cột cân nặng, "cân nặng hiện tại" luôn lấy từ bản ghi mới nhất. Hệ thống tính lại ngân sách từ thời điểm sửa, không đổi ngân sách các ngày đã qua | M |
| FR-01.8 | Member chọn công thức BMR và chính sách cộng calo tập (xem FR-04.4) | M |
| FR-01.9 | Ngân sách mục tiêu của ngày không được thấp hơn **sàn calo tối thiểu**: mặc định **1.500 kcal (nam) / 1.200 kcal (nữ)**. Khi tính ra thấp hơn thì đặt bằng sàn và hiển thị cảnh báo. Cấu hình được; cần tra nguồn y tế để trích dẫn trước khi đưa vào báo cáo chính thức (xem mục 7) | M |
| FR-01.10 | Hiển thị disclaimer: ứng dụng không thay thế tư vấn y tế | M |
| FR-01.11 | Kiểm tra tuổi khi đăng ký và khi sửa hồ sơ: **từ 18 đến 80 tuổi** (công thức BMR dành cho người trưởng thành). Ngoài khoảng này thì từ chối và giải thích lý do | M |
| FR-01.12 | Quên mật khẩu | **Chưa quyết định** (xem mục 6) |

### FR-02. Ngân sách calo động (UC08)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-02.1 | Với mỗi ngày, hệ thống tính: `Ngân sách = Baseline ± điều chỉnh mục tiêu + Calo tập được cộng` và `Còn lại = Ngân sách − Calo đã nạp`. `Calo tập được cộng` tính **ở cấp ngày** từ tổng calo thô các buổi tập theo chính sách của ngày đó (FR-04.4) | M |
| FR-02.2 | Hiển thị Còn lại cập nhật ngay sau khi ghi, sửa hoặc xóa món/bài tập, trên thanh năng lượng ngày ở đầu Dashboard và đầu trang Nhật ký ăn uống. Thành phần này thuộc Must, không bị cắt khi rút gọn Dashboard (FR-06) | M |
| FR-02.3 | Hiển thị rõ các thành phần: ngân sách gốc, calo tập được cộng, calo đã nạp | M |
| FR-02.4 | Khi Còn lại < 0, hiển thị trạng thái vượt ngân sách, không chặn người dùng | M |
| FR-02.5 | Không cộng trùng calo: calo tập chỉ lấy phần active calories, không gồm BMR | M |

### FR-03. Ghi nhận món ăn (UC04, UC05, UC06, UC12, UC13, UC14)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-03.1 | Member tải/chụp ảnh món ăn và nhập mô tả khẩu phần (ví dụ "1 bát cơm, 2 miếng gà chiên") rồi gửi phân tích | M |
| FR-03.2 | PHP gọi dịch vụ AI qua HTTP; dịch vụ trả danh sách món dự đoán kèm độ tin cậy | M |
| FR-03.3 | Nếu độ tin cậy top-1 ≥ ngưỡng (cấu hình được; giá trị chốt sau PoC, xem O1): lấy công thức chuẩn của món (đường nhận diện); bản nháp được dựng theo FR-03.17 | M |
| FR-03.4 | Nếu độ tin cậy thấp, món tự chế hoặc mô tả theo nguyên liệu: tách nguyên liệu + gram từ mô tả, tra CSDL nguyên liệu (đường nguyên liệu) | M |
| FR-03.5 | Hệ thống có lớp "không nhận ra"; khi đó chuyển sang tìm/nhập thủ công thay vì đoán | M |
| FR-03.6 | Quy đổi khẩu phần Việt (bát, chén, dĩa, miếng...) sang gram; phân biệt khối lượng sống/chín | M |
| FR-03.7 | Hiển thị kết quả gồm món, nguyên liệu, gram, kcal, protein/carb/fat; Member xác nhận hoặc chỉnh sửa trước khi lưu (**không được bỏ**). Gram mỗi nguyên liệu hợp lệ trong khoảng **0,1 – 2.000 g** | M |
| FR-03.8 | Mô tả lời được tách bằng quy tắc + từ điển đồng nghĩa ở v1; LLM chỉ là tùy chọn (D20) | M |
| FR-03.9 | Tìm món thủ công trong danh mục hệ thống và danh mục cá nhân, chọn khẩu phần và ghi vào nhật ký | M |
| FR-03.10 | Ghi bữa ăn vào nhật ký theo buổi (sáng / trưa / tối / phụ) dưới dạng **snapshot**; sửa dữ liệu nguồn không làm đổi lịch sử | M |
| FR-03.11 | Member sửa hoặc xóa mục trong nhật ký | M |
| FR-03.12 | Member tạo nguyên liệu/món tự chế; giá trị dinh dưỡng tự nhập gắn nhãn "tự nhập" với độ tin cậy thấp | M |
| FR-03.13 | Member lưu món đã tính toán vào "Món đã lưu", lưu theo nguyên liệu + gram (nguồn: kết quả AI, tạo thủ công, từ nhật ký) | M |
| FR-03.14 | Ghi nhanh từ "Món đã lưu" với hệ số khẩu phần (×0.5, ×1, ×1.5...) | M |
| FR-03.15 | Khi dịch vụ AI không phản hồi hoặc quá tải, hệ thống báo lỗi dễ hiểu và cho phép chuyển sang ghi thủ công | M |
| FR-03.16 | Khi Member sửa kết quả AI và **đồng ý**, lưu cặp (món dự đoán, món đã sửa) để đánh giá/huấn luyện lại ngoại tuyến; không tự đưa vào CSDL chung | C |
| FR-03.17 | **Ghép công thức chuẩn với mô tả (đường nhận diện):** bản nháp = công thức chuẩn của món AI đoán + điều chỉnh từ mô tả. Nguyên liệu trong mô tả khớp nguyên liệu của công thức thì dùng gram từ mô tả thay cho gram công thức; nguyên liệu của công thức không được nhắc thì giữ gram công thức nhân hệ số khẩu phần món (mặc định ×1); nguyên liệu trong mô tả mà công thức không có thì thêm thành dòng bổ sung gắn nhãn "từ mô tả". Phủ định như "không hành" chưa tách tự động ở v1, Member xóa dòng ở bước xác nhận | M |
| FR-03.18 | **Xử lý ảnh tải lên:** ảnh chỉ dùng tạm để phân tích và bị xóa ngay khi trả bản nháp. Chỉ giữ lại khi Member chủ động chọn lúc tải lên "Lưu ảnh vào nhật ký" hoặc "Cho phép dùng ảnh để cải thiện mô hình" (mặc định tắt cả hai). Ảnh giữ tạm gắn với bản nháp và bị dọn khi quá hạn nếu Member không xác nhận | M |
| FR-03.19 | **Sửa mục nhật ký đã ghi:** dòng nguyên liệu giữ nguyên nguyên liệu và gram thì **giữ giá trị dinh dưỡng đã lưu**; chỉ dòng bị sửa gram hoặc thêm mới dùng giá trị dinh dưỡng hiện hành; dòng bị gỡ thì xóa | M |
| FR-03.20 | **Server luôn tự tính** calo và macro từ nguyên liệu + gram (hoặc món + hệ số khẩu phần) khi ghi và khi sửa. Trình duyệt chỉ gửi mã nguyên liệu/món, gram, buổi và (với dòng đã lưu) mã dòng; không nhận giá trị dinh dưỡng do trình duyệt gửi | M |

### FR-04. Theo dõi luyện tập (UC07)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-04.1 | Member ghi bài tập: loại (chạy bộ, đạp xe, gym, bơi...), ngày, thời lượng, quãng đường (nếu có) | M |
| FR-04.2 | Hai nguồn calo tiêu hao: (a) nhập từ thiết bị, ghi rõ là **active calories**; (b) tính MET × cân nặng × thời gian (mặc định) | M |
| FR-04.3 | Mỗi bản ghi mang nhãn độ tin cậy theo nguồn (thiết bị: cao; MET: trung bình) | M |
| FR-04.4 | Chính sách cộng calo tập vào ngân sách do Member chọn, thiết kế theo Strategy, áp **ở cấp ngày** lên tổng calo thô Σ của các buổi tập trong ngày: **cộng toàn bộ** = Σ; **cộng một phần** = 0,5 × Σ (**mặc định**); **có trần** = min(Σ, **500 kcal**). Chính sách và hệ số của ngày được lưu trên bản ghi ngân sách của ngày đó. Đổi chính sách chỉ áp cho hôm nay trở đi, các ngày đã qua không đổi. Hệ số và trần cấu hình được; đây là lựa chọn thiết kế để hạn chế ăn bù quá mức (R1), chưa có tài liệu tham khảo | M |
| FR-04.5 | Bước chân/quãng đường không là nguồn riêng; dùng để chọn MET theo tốc độ cho buổi đi bộ/chạy | M |
| FR-04.6 | Xem lịch sử buổi tập theo tuần/tháng; sửa, xóa buổi tập | M |
| FR-04.7 | Bản ghi buổi tập chỉ lưu **calo thô**, nguồn, MET, cân nặng đã dùng; **không lưu hệ số cộng**. Khi thêm, sửa, xóa buổi tập hoặc đổi chính sách, hệ thống tính lại calo tập được cộng của cả ngày | M |

### FR-05. Gợi ý món ăn (UC10)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-05.1 | Hiển thị lượng calo và protein còn thiếu trong ngày | C |
| FR-05.2 | Gợi ý danh sách món phù hợp lượng còn lại, lấy từ danh mục hệ thống và danh mục cá nhân | C |
| FR-05.3 | Bộ lọc: nhiều protein, món Việt, dễ làm, dưới 400 kcal | C |

*Chỉ làm nếu còn dư thời gian (11.3 trong 01).*

### FR-06. Dashboard và thống kê (UC09)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-06.1 | Thanh năng lượng ngày: ngân sách, calo tập, calo đã nạp, còn lại | S |
| FR-06.2 | Biểu đồ macro (protein/carb/fat) trong ngày | S |
| FR-06.3 | Tóm tắt các món đã ăn trong ngày | S |
| FR-06.4 | Ghi và hiển thị lượng nước uống trong ngày | S |
| FR-06.5 | Biểu đồ cân nặng theo tuần/tháng; Member cập nhật cân nặng | S |
| FR-06.6 | Biểu đồ mức tuân thủ ngân sách theo ngày/tuần | S |
| FR-06.7 | Hiển thị số buổi tập đã ghi trong tuần so với số buổi mục tiêu; khi tụt so với tiến độ tuần (cách tính **[CẦN CHỐT]**) thì hiện nhắc nhở trung tính trong ứng dụng, tắt được. Không ảnh hưởng ngân sách. Nằm trong phần bị cắt đầu tiên của Dashboard nếu trễ tiến độ | S |

*Nếu trễ tiến độ, rút gọn xuống biểu đồ calo và macro trong ngày (01, 11.3).*

### FR-07. Quản lý danh mục hệ thống (UC11)

| ID | Yêu cầu | Ưu tiên |
|---|---|---|
| FR-07.1 | Nạp và chỉnh nguyên liệu, công thức chuẩn, bảng quy đổi khẩu phần bằng **script seed** (D25) | M |
| FR-07.2 | Sửa một nguyên liệu trong danh mục hệ thống làm các lần dùng sau tính đúng, **không** thay đổi snapshot cũ | M |
| FR-07.3 | Giao diện quản trị web cho Admin (CRUD danh mục) | S |
| FR-07.4 | Phân quyền: chỉ Admin truy cập chức năng quản trị (đi cùng giao diện quản trị ở FR-07.3) | S |

> Lưu ý: README Pha 2 ghi FR-01 đến FR-06. SRS này thêm FR-07 để tách riêng danh mục hệ thống (UC11), vì mức ưu tiên Must/Should của nó khác các nhóm còn lại.

---

## 4. Yêu cầu phi chức năng (NFR)

| ID | Nhóm | Yêu cầu | Cách kiểm chứng |
|---|---|---|---|
| NFR-01 | Hiệu năng | Ghi một bữa từ lúc mở form đến khi lưu: mục tiêu dưới 30 giây (chỉ số thành công, 01 mục 4) | Đo thủ công khi thử nghiệm |
| NFR-02 | Hiệu năng | Thời gian phản hồi dịch vụ AI: **mục tiêu SLA tổng (end-to-end) ≤ 3 giây** trên CPU; **timeout cứng (circuit breaker) = 10.0 giây** (vượt thì chuyển sang ghi thủ công UC06). Mục tiêu suy luận ONNX ≤ 500 ms. Cần đo thực tế trên model thật | Đo ở PoC và load test C2 |
| NFR-03 | Hiệu năng | Dịch vụ AI dùng hàng đợi và giới hạn tốc độ. Mặc định: **10 yêu cầu phân tích/phút/người dùng**, **hàng đợi tối đa 20 yêu cầu** (đầy thì trả 429). Cấu hình được, điều chỉnh sau load test nhẹ ở C2 | Load test nhẹ |
| NFR-04 | Độ chính xác | Độ đúng top-1 và tỉ lệ chuyển đúng sang đường nguyên liệu đo trên ảnh tự chụp bằng điện thoại **[CẦN CHỐT: ngưỡng, ghi ở mốc 07/10]** | Cổng go/no-go 11/10 |
| NFR-05 | Minh bạch | Mọi con số calo hiển thị kèm nhãn nguồn/độ tin cậy (AI, công thức chuẩn, tự nhập, thiết bị, MET) | Kiểm tra giao diện |
| NFR-06 | Bảo mật | Mật khẩu băm bằng `password_hash`; truy vấn dùng PDO prepared statements; chống CSRF cho form; kiểm tra và giới hạn loại/kích thước ảnh tải lên | Review mã, kiểm thử |
| NFR-07 | Bảo mật | Trang protected chỉ truy cập sau đăng nhập; phiên có thời hạn | Kiểm thử hệ thống |
| NFR-08 | Quyền riêng tư | Lưu trữ tối thiểu: ảnh bị xóa ngay sau phân tích trừ khi Member chọn giữ (FR-03.18); Member có thể xóa toàn bộ dữ liệu của mình | Kiểm thử chức năng xóa |
| NFR-09 | An toàn sức khỏe | Có sàn calo tối thiểu và disclaimer y tế; không cắt các cảnh báo này khi cắt phạm vi (R2) | Kiểm tra giao diện |
| NFR-10 | Tính toàn vẹn dữ liệu | Nhật ký là snapshot bất biến theo nguồn, **trừ các dòng bị chính Member sửa (FR-03.19)**; hoạt động ghi nhiều bước dùng transaction | Kiểm thử hồi quy |
| NFR-11 | Khả dụng | Responsive, mobile-first; tiếng Việt; thao tác chính (chụp, mô tả, xác nhận) làm được bằng một tay trên điện thoại | Kiểm thử trên điện thoại |
| NFR-12 | Độ tin cậy | Khi dịch vụ AI lỗi, các chức năng còn lại vẫn dùng được (fallback nhập thủ công) | Tắt dịch vụ AI và kiểm thử |
| NFR-13 | Bảo trì | Tách dịch vụ AI khỏi PHP qua hợp đồng API cố định; Energy Engine dùng Strategy cho công thức BMR và chính sách calo tập | Review thiết kế |
| NFR-14 | Giấy phép | Kiểm tra điều khoản của từng bộ dữ liệu huấn luyện (R5) | Ghi vào `02_ai_service_poc.md` |
| NFR-15 | Triển khai | Chạy được ở localhost hoặc hosting thuê thử để demo (D21) | Demo ở T1 |

---

## 5. Truy vết Use Case → Yêu cầu

| UC | Tên | Ưu tiên | Yêu cầu liên quan |
|---|---|---|---|
| UC01 | Đăng ký và khảo sát | Must | FR-01.1, 01.2, 01.4 – 01.6, 01.10, 01.11, NFR-06, FR-01.9 |
| UC02 | Đăng nhập (modal) | Must | FR-01.3, NFR-06, NFR-07 |
| UC03 | Thiết lập hồ sơ, công thức BMR, chính sách calo | Must | FR-01.4 – 01.11, FR-04.4 |
| UC04 | Ghi món bằng ảnh + mô tả | Must | FR-03.1 – 03.6, 03.8, 03.15, 03.17, 03.18, NFR-02 – 04 |
| UC05 | Xác nhận/chỉnh sửa kết quả AI | Must | FR-03.7, 03.10, 03.16, 03.18, 03.20 |
| UC06 | Tìm và ghi món thủ công | Must | FR-03.6, 03.9 – 03.11, 03.19, 03.20 |
| UC07 | Ghi bài tập và quy đổi calo | Must | FR-04.1–04.7 |
| UC08 | Xem ngân sách còn lại | Must | FR-02.1–02.5 |
| UC09 | Dashboard và tiến trình (gồm ghi nước, cập nhật cân nặng) | Should | FR-06.1 – 06.7 |
| UC10 | Gợi ý món bữa tiếp theo | Could | FR-05.1 – 05.3 |
| UC11 | Quản lý danh mục hệ thống | Must (seed) / Should (UI) | FR-07.1–07.4 |
| UC12 | Tạo món/nguyên liệu tự chế | Must | FR-03.12 |
| UC13 | Lưu món vào danh mục cá nhân | Must | FR-03.13 |
| UC14 | Ghi nhanh từ "Món đã lưu" | Must | FR-03.10, 03.13, 03.14, 03.20 |

---

## 6. Vấn đề còn mở

| # | Vấn đề | Cần chốt trước |
|---|---|---|
| O1 | Ngưỡng độ tin cậy để chuyển giữa đường nhận diện và đường nguyên liệu (FR-03.3), ngưỡng đạt (top-1, tỉ lệ chuyển đường) và số món tối thiểu chấp nhận ở cổng 11/10 | Ghi ở mốc 07/10, trước khi có kết quả PoC |
| O2 | README Pha 2 nhắc "Gemini Vision" như một lựa chọn cho PoC, trong khi D14 chốt suy luận phía server tự host. Cần xác nhận dùng Gemini chỉ để so sánh hay là phương án thật | 07/10 |
| O3 | Có làm "Quên mật khẩu" (FR-01.12) không. Cần dịch vụ gửi email, nằm ngoài phạm vi 01; ảnh hưởng link "Quên mật khẩu?" trong modal đăng nhập | 07/10 |
| O4 | Mô tả khẩu phần ở FR-03.1 là bắt buộc hay tùy chọn, và khi không có mô tả thì dùng khẩu phần mặc định nào | 07/10 |
| O5 | Nếu làm UC10 (Could): cần định nghĩa mục tiêu protein hằng ngày (FR-05.1) và thuộc tính "món Việt", "dễ làm" trên món (FR-05.3); nếu không thì chỉ lọc theo protein và calo | Khi quyết làm UC10 |

---

## 7. Việc cần làm

| # | Việc | Hạn |
|---|---|---|
| T1 | Tra nguồn để trích dẫn sàn calo (1.500/1.200 kcal), hệ số 0,5 và trần 500 kcal, **hệ số sinh hoạt cố định và bảng điều chỉnh theo mục tiêu** | Trước khi viết báo cáo chính thức |
| T2 | Cập nhật `pages.md` (và `idea.md`): bỏ "bước chân" và "lịch tập trong ngày" ở Dashboard theo 01 v1.1; **bỏ "mức độ vận động" khỏi form khảo sát (idea.md Module 1)**, thay bằng số buổi tập mục tiêu | 07/10 |

---

## 8. Lịch sử thay đổi (v1.0 → v1.1)

| Nhóm | Thay đổi |
|---|---|
| Baseline | Bỏ khảo sát "mức độ vận động"; Baseline = BMR × hệ số sinh hoạt cố định (FR-01.4, FR-01.6, A4). Thêm bảng điều chỉnh theo mục tiêu (giá trị ví dụ, chờ chốt) |
| Calo tập | Buổi tập chỉ lưu calo thô; calo được cộng tính ở cấp ngày theo chính sách của ngày (FR-02.1, FR-04.4, FR-04.7) |
| Nhắc nhở | Số buổi tập mục tiêu chỉ dùng để nhắc nhở (FR-06.7), không đi vào công thức |
| Nhật ký | Quy tắc sửa mục đã ghi (FR-03.19); server tự tính calo, không nhận giá trị dinh dưỡng từ trình duyệt (FR-03.20); NFR-10 chỉnh lại |
| Cân nặng | Bỏ cột cân nặng khỏi `user_profiles`; "cân nặng hiện tại" = bản ghi mới nhất trong `weight_logs` (FR-01.7). Một nguồn duy nhất, tránh lệch |
| Chưa áp dụng | Chặn mục tiêu cân nặng nguy hiểm (nhóm quyết định bỏ qua); các lỗ hổng nhỏ và gợi ý O1–O5 trong nhận xét trước chưa đưa vào |

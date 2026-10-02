# FlexiDiet: Inception, Business Modelling & Iteration Plan (v1.1 - Đã chốt)

> Trạng thái tài liệu: **Đã chốt (Baseline v1.1)** - Chính thức kết thúc Pha 1 (INCEPTION). Bản v1.1 sửa các điểm chưa nhất quán của v1.0, chấm lại rủi ro theo thời hạn thực tế và điều chỉnh kế hoạch iteration (xem mục 13).
> Phương pháp: Unified Process (Sommerville, 9th ed.), bản rút gọn cho dự án học tập nhóm 3 người.
> Thời gian thực hiện dự án: **02/10/2026 – 25/10/2026** (Tổng cộng: ~3.5 tuần / 23 ngày).

---

## 0. Tiến độ hiện tại

**Vị trí:** Pha 1 INCEPTION – **100% Hoàn thành**. Chuẩn bị chuyển sang Pha 2 ELABORATION.

| Artifact của Inception | Trạng thái |
|---|---|
| Problem Statement & Pain Points | Xong (mục 2) |
| Benchmark đối thủ | Xong, **chưa kiểm chứng số liệu hiện hành** (mục 3) |
| UVP & Lean Canvas | Xong (mục 4) |
| Decision Log (D1 – D27) | Xong, không còn quyết định treo (mục 5) |
| Vision & Scope v1.1 | **Đã chốt v1.1** (mục 6) |
| Actors | Xong 5 Actors (mục 7) |
| Use Case sơ bộ | Xong 14 Use Cases (mục 8) |
| Quyết định thiết kế sớm & Công thức calo | Xong (mục 9) |
| Initial Risk Assessment | Xong, **chấm lại theo thời hạn 23 ngày**, R1 – R11 (mục 10) |
| **Kế hoạch Iteration chi tiết (đến 25/10)** | **Xong, đã điều chỉnh v1.1 (mục 11)** |

**Kết luận Inception:** Dự án đủ điều kiện bước sang Pha 2 (Elaboration).

---

## 1. Thông tin dự án

- **Tên:** FlexiDiet, web app quản lý ăn uống và luyện tập theo "Ngân sách Calo Động" kết hợp AI nhận diện món ăn.
- **Bản chất:** dự án học tập nhóm 3 người, sản phẩm portfolio. Public là tùy chọn, hiện chưa tính đến.
- **Stack (do nhóm chọn):** HTML, CSS, JS, Bootstrap, PHP thuần + PDO + MySQL, kèm dịch vụ suy luận Python (FastAPI + ONNX Runtime).
- **Ngôn ngữ và nền tảng:** tiếng Việt, web responsive (mobile-first vì chụp ảnh bằng điện thoại).
- **Người dùng mục tiêu:** sinh viên và người đi làm trẻ, muốn kiểm soát ăn uống và tập luyện (gồm gym).
- **Nguồn nền tảng:** `idea.md` (6 module MVP, 7 trang) và `SKILL.md` (khung UP).

---

## 2. Problem Statement & User Pain Points

> Người muốn kiểm soát cân nặng thường bỏ cuộc sau vài tuần, không phải vì thiếu kiến thức dinh dưỡng mà vì **chi phí ghi chép quá cao** và **kế hoạch quá cứng nhắc** so với cuộc sống thực.

| # | Pain Point | Biểu hiện |
|---|---|---|
| P1 | Ghi nhận thủ công tốn công | Tìm món, chọn khẩu phần, nhập từng nguyên liệu mỗi bữa |
| P2 | CSDL món ăn kém tin cậy | Dữ liệu crowdsourced trùng lặp, sai, nhất là món địa phương |
| P3 | Món và khẩu phần Việt bị bỏ quên | "1 bát", "1 chén", "1 dĩa" khó quy đổi |
| P4 | Mục tiêu calo tĩnh | Ngày chạy 10km và ngày nằm nhà có cùng một con số |
| P5 | Kế hoạch ăn cứng nhắc | Khó theo khi ăn ngoài, ăn tiệc. Một lần "phá lệ" dễ dẫn đến bỏ cuộc |
| P6 | AI ảnh ước tính sai khẩu phần | Ảnh không cho biết dầu mỡ, nước sốt, độ sâu của bát. Khó chỉnh nhanh |
| P7 | Ăn uống và tập luyện tách rời | Phải tự ghép hai nguồn để biết "hôm nay còn được ăn bao nhiêu" |
| P8 | Không biết ăn gì tiếp theo | App báo "còn 480 kcal" nhưng không gợi ý món cụ thể |

---

## 3. Benchmark thị trường

> **Lưu ý:** bảng dưới là nhận định sơ bộ, chưa tra cứu bản hiện hành. Giá và tính năng của các app này thay đổi thường xuyên. Cần kiểm chứng trước khi đưa vào báo cáo chính thức.

| Tiêu chí | MyFitnessPal | MacroFactor | Cal AI | Cronometer |
|---|---|---|---|---|
| Định vị | Log ăn uống đại trà | Công cụ cho người nghiêm túc về số liệu | Chụp ảnh ra calo | Dinh dưỡng chính xác, vi chất |
| Điểm mạnh | CSDL lớn, barcode, cộng đồng | TDEE thích ứng theo xu hướng cân nặng | Log cực nhanh, UI gọn | CSDL kiểm duyệt, vi chất sâu |
| Điểm yếu | Dữ liệu có chỗ sai, nhiều tính năng sau paywall | Chi phí, hơi "khô" | Khẩu phần kém chính xác, ít khả năng chỉnh | Learning curve cao |
| Ngân sách calo | Tĩnh, có thể cộng calo tập | Điều chỉnh theo tuần | Cơ bản | Cơ bản |
| Món Việt | Có, không đồng đều | Hạn chế | Phụ thuộc mô hình | Hạn chế |
| Gợi ý theo calo còn lại | Yếu | Yếu | Gần như không có | Yếu |

**Khoảng trống:**
1. Chưa app nào kết hợp tốt: log nhanh bằng AI + ngân sách calo từng ngày + gợi ý bữa tiếp theo.
2. Món và khẩu phần Việt là lợi thế sân nhà chưa được khai thác tốt.
3. Luồng **ảnh + mô tả bằng lời** giúp giảm sai số khẩu phần so với ảnh thuần túy.

---

## 4. UVP & Lean Canvas sơ bộ

> **FlexiDiet: ăn thoải mái, app tự cân bằng.** Chụp ảnh và mô tả vài chữ để ghi món Việt trong vài giây. Ngân sách calo tự điều chỉnh theo vận động của chính ngày hôm đó. Còn bao nhiêu calo thì gợi ý món cho bữa tiếp theo.

- **Khách hàng:** sinh viên, người đi làm trẻ ở Việt Nam, muốn kiểm soát ăn uống, có tập luyện.
- **Giải pháp:** nhận diện món Việt (ảnh + mô tả) → ngân sách động → gợi ý bữa kế tiếp.
- **Chỉ số thành công (đề xuất):** thời gian log một bữa dưới 30 giây, tỉ lệ còn dùng sau 30 ngày, sai số calo chấp nhận được (ngưỡng cụ thể chốt sau PoC).
- **Lợi thế:** CSDL món Việt và khẩu phần quen thuộc, cơ chế tính calo tập luyện đa phương pháp, luồng ảnh + mô tả.

---

## 5. Decision Log

| # | Quyết định | Trạng thái |
|---|---|---|
| D1 | Người dùng mục tiêu: sinh viên và người đi làm trẻ, gym | Đã chốt |
| D2 | Dự án học tập, nhóm 3 người, portfolio. Public là tùy chọn | Đã chốt |
| D3 | Cơ chế tính calo tập luyện: nhiều phương pháp, người dùng chọn (Strategy Pattern) | Đã chốt |
| D4 | Món Việt là UVP. Dữ liệu hiện có (VietFood67, 30VNFoods, VinaFood21, VNFood103) chỉ giải quyết nhận diện tên món | Đã chốt, kèm rủi ro kỹ thuật |
| D5 | MoSCoW: **Must** (Profile, Budget, AI Logging, Workout), **Should** (Dashboard), **Could** (Suggestions) | Đã chốt |
| D6 | Tiếng Việt, web, responsive | Đã chốt |
| D7 | Calo tiêu hao có 2 nguồn: nhập từ thiết bị, hoặc MET-based. Bỏ nhịp tim | Đã chốt |
| D8 | Luồng món: nhận diện → bảng thành phần (Viện Dinh dưỡng) → tính lại theo khẩu phần mô tả (tam suất) | Đã chốt hướng |
| D9 | Món/nguyên liệu ngoại lai xử lý qua CSDL nguyên liệu | Đã chốt |
| D10 | Có Admin kiểm duyệt CSDL món ăn | **Đã bị thay thế bởi D22** (không còn duyệt món người dùng) |
| D11 | Stack: HTML, CSS, JS, Bootstrap, PHP. Mô hình AI tách khỏi PHP | Đã chốt |
| D12 | Nhãn độ tin cậy cho từng nguồn calo, cảnh báo "active calories", gộp bước chân vào buổi đi bộ/chạy | Đã chốt |
| D13 | Kiến trúc hai đường (món nhận diện / nguyên liệu) + bảng công thức chuẩn, MVP 30-50 món | Đã chốt |
| D14 | Hướng triển khai AI: **suy luận phía server (O1)**, không chạy trên trình duyệt. Loại O2 | Đã chốt |
| D15 | Tự host mô hình trên domain + server riêng, coi việc quản lý tải nhiều người dùng là mục tiêu học tập và điểm nhấn | Đã chốt |
| D16 | PHP thuần + PDO + MySQL | Đã chốt |
| D17 | Nhóm có người có kinh nghiệm thị giác máy tính, có thể tự huấn luyện (H1) | Đã chốt |
| D18 | Kiến trúc dự kiến: PHP (web + dữ liệu + tính toán) gọi dịch vụ suy luận riêng qua HTTP (hàng đợi, giới hạn tốc độ, đo tải) | Đã chốt |
| D19 | Đề tài do sinh viên tự chọn theo giảng viên. File đề môn học chỉ dùng tham khảo thời hạn. **Nên xin xác nhận đề tài bằng văn bản/email** | Đã chốt |
| D20 | LLM chỉ là tùy chọn. Tách mô tả bằng quy tắc + từ điển ở v1 | Đã chốt |
| D21 | Phân vai nhóm do nhóm tự quyết, ngoài phạm vi tài liệu. Hosting sẽ tìm sau (có thể thuê server dùng thử để demo) | Đã chốt |
| D22 | **Bỏ luồng Admin duyệt món người dùng.** Món/nguyên liệu tự tạo và món đã tính toán chỉ lưu vào **danh mục cá nhân**, không có kênh đưa vào CSDL chung | Đã chốt |
| D23 | Mọi món đã ghi nhận (từ AI hoặc tạo tay) có thể **lưu lại để dùng lại**, không cần chụp và mô tả lại | Đã chốt |
| D24 | Admin giữ vai trò quản lý **danh mục hệ thống** (nguyên liệu, công thức chuẩn, bảng quy đổi khẩu phần) | Đã chốt, phạm vi theo D25 |
| D25 | **Admin ở v1: nạp và chỉnh dữ liệu bằng script seed.** Giao diện quản trị chuyển thành Should | Đã chốt |
| D26 | Kế hoạch có **cổng go/no-go ngày 11/10** cho PoC nhận diện món, kèm phương án dự phòng định sẵn (mục 11.2). 30-50 món là mục tiêu, mức tối thiểu do nhóm ghi ra ở mốc 07/10 | Đã chốt |
| D27 | **Đóng băng tính năng từ 22/10.** Kiểm thử tích hợp chạy từ C1, không dồn vào T1 | Đã chốt |

---

## 6. Vision & Scope (v1.1 - Đã chốt)

**Vision statement:**
> Dành cho sinh viên và người đi làm trẻ tại Việt Nam muốn kiểm soát ăn uống và luyện tập, **FlexiDiet** là web app giúp ghi nhận bữa ăn món Việt chỉ bằng ảnh và vài dòng mô tả, đồng thời tự điều chỉnh ngân sách calo theo vận động từng ngày. Khác với các app hiện có, FlexiDiet tối ưu cho món và khẩu phần Việt, và cho người dùng chọn phương pháp tính calo phù hợp.

| Phạm vi | Nội dung |
|---|---|
| In-scope (Must) | Hồ sơ và Energy Engine, Ngân sách động, AI Food Logging (ảnh + prompt + xác nhận/chỉnh sửa + tìm thủ công), Workout Tracker, danh mục cá nhân (món đã lưu, tạo món/nguyên liệu tùy chỉnh), quản lý danh mục hệ thống **bằng script seed** |
| In-scope (Should) | Dashboard (calo, macro, nước, cân nặng theo thời gian), giao diện quản trị danh mục hệ thống |
| In-scope (Could) | Smart Meal Suggestions (chỉ làm nếu còn dư thời gian), phản hồi AI để huấn luyện lại (tùy chọn, cần người dùng đồng ý) |
| Out-of-scope (v1) | App native, đồng bộ thiết bị đeo, barcode, mạng xã hội, thanh toán/premium, tư vấn y tế, tính calo theo nhịp tim |
| Ràng buộc | Web responsive, tiếng Việt, nhóm 3 người, thời hạn môn học: **25/10/2026**, stack HTML/CSS/JS/Bootstrap/PHP (PDO) + MySQL + Python AI Microservice |

---

## 7. Actors

| Actor | Loại | Vai trò |
|---|---|---|
| Khách (Guest) | Chính | Xem landing page, đăng ký, đăng nhập |
| Người dùng (Member) | Chính | Ghi nhận, theo dõi, gợi ý |
| Quản trị viên (Admin) | Chính | Quản lý danh mục hệ thống (nguyên liệu, công thức chuẩn, bảng quy đổi). Không duyệt món người dùng |
| Dịch vụ AI nhận diện | Phụ | Nhận diện món, và (tùy chọn) tách mô tả thành nguyên liệu |
| CSDL dinh dưỡng | Phụ | Nguồn tra cứu calo/macro |

---

## 8. Use Case sơ bộ

| ID | Use Case | Actor | Ưu tiên | Ghi chú kiến trúc |
|---|---|---|---|---|
| UC01 | Đăng ký và khảo sát ban đầu | Guest | Must | Khởi tạo hồ sơ cho Energy Engine |
| UC02 | Đăng nhập (modal) | Guest | Must | Xác thực |
| UC03 | Thiết lập hồ sơ, chọn công thức BMR và chính sách calo tập | Member | Must | **Cao**: Strategy |
| UC04 | Ghi món ăn bằng ảnh + mô tả (AI) | Member, Dịch vụ AI | Must | **Rất cao**: rủi ro kỹ thuật lớn nhất |
| UC05 | Xác nhận/chỉnh sửa kết quả AI | Member | Must | **Cao** |
| UC06 | Tìm và ghi món thủ công | Member | Must | Nền cho CSDL |
| UC07 | Ghi nhận bài tập và quy đổi calo | Member | Must | **Cao**: nhiều phương pháp |
| UC08 | Xem ngân sách calo còn lại | Member | Must | Cốt lõi nghiệp vụ |
| UC09 | Xem dashboard và tiến trình | Member | Should | Truy vấn tổng hợp |
| UC10 | Xem gợi ý món cho bữa tiếp theo | Member | Could | Phụ thuộc UC04, UC06. Chỉ làm nếu còn dư |
| UC11 | Quản lý danh mục hệ thống (nguyên liệu, công thức chuẩn, bảng quy đổi) | Admin | **Must (script seed) / Should (giao diện)** | Theo D25 |
| UC12 | Tạo món/nguyên liệu tự chế (danh mục cá nhân) | Member | Must | Xử lý ngoại lai |
| UC13 | Lưu món đã tính toán vào danh mục cá nhân | Member | Must | Thay cho luồng duyệt món |
| UC14 | Ghi nhanh từ "Món đã lưu" (chỉnh khẩu phần) | Member | Must | Giảm chi phí ghi nhận (P1) |

**Architecturally significant:** UC03, UC04, UC05, UC07. UC13/UC14 ảnh hưởng mô hình dữ liệu (công thức lưu theo nguyên liệu, nhật ký dạng snapshot).

---

## 9. Các quyết định thiết kế quan trọng đã nảy sinh

### 9.1. Công thức ngân sách (sửa lỗi tính trùng trong `idea.md`)

Công thức gốc dùng TDEE (đã gồm hệ số vận động) rồi cộng thêm calo tập luyện, dẫn đến **tính trùng**. Công thức đề xuất:

```
Ngân sách = BMR × hệ số sinh hoạt (baseline) ± điều chỉnh mục tiêu + Σ (calo tập × hệ số chính sách)
Còn lại   = Ngân sách − Calo đã nạp
```

- BMR: Mifflin-St Jeor mặc định, Katch-McArdle tùy chọn (Strategy).
- Hệ số chính sách (cộng 100%, một phần, có trần) có thể phụ thuộc độ tin cậy của nguồn. Giá trị cụ thể sẽ chốt bằng tài liệu tham khảo ở Elaboration.

### 9.2. Ước tính calo tiêu hao (hai tầng)

| Nguồn | Độ tin cậy | Ghi chú |
|---|---|---|
| Nhập từ thiết bị | Cao | Phải ghi rõ nhập **active calories**, tránh trùng với BMR |
| MET × cân nặng × thời gian | Trung bình | Mặc định khi không có thiết bị |
| Quãng đường/bước chân | Thấp | Gộp vào buổi đi bộ/chạy có chủ đích để chọn MET theo tốc độ, không làm nguồn riêng |

### 9.3. Luồng ghi nhận món ăn (hai đường)

```
Ảnh + mô tả
   │
   ├─► Bộ nhận diện món (độ tin cậy ≥ ngưỡng)
   │        └─► Bảng công thức chuẩn ──► nhân tỉ lệ theo khẩu phần ──┐
   │                                                                  ├─► Người dùng xác nhận/chỉnh sửa
   └─► (độ tin cậy thấp / món tự chế / mô tả nguyên liệu)             │
            └─► Tách nguyên liệu + gram ──► CSDL nguyên liệu ─────────┘
```

- Bảng của Viện Dinh dưỡng chủ yếu là nguyên liệu, nên nhóm cần tự lập **bảng công thức chuẩn** cho món hoàn chỉnh. Đây là phần công sức thủ công lớn nhất.
- Cần bảng quy đổi khẩu phần Việt (bát, chén, dĩa, miếng...) sang gram, và phân biệt khối lượng sống/chín.
- Tách mô tả thành nguyên liệu: quy tắc + từ điển đồng nghĩa, hoặc LLM. Kiến trúc cho phép đổi giữa hai cách.

### 9.4. Danh mục hệ thống và danh mục cá nhân (thay cho vòng đời duyệt món, theo D22/D23)

**Hai tầng dữ liệu:**

| Tầng | Chủ sở hữu | Nội dung | Ai sửa |
|---|---|---|---|
| Danh mục hệ thống | Hệ thống | Nguyên liệu (Viện Dinh dưỡng, USDA), công thức chuẩn 30-50 món, bảng quy đổi khẩu phần | Admin |
| Danh mục cá nhân | Từng người dùng | Nguyên liệu và món tự tạo, **món đã lưu** | Chính người dùng |

**Luồng chính, kèm lưu để dùng lại:**

```
Ảnh + mô tả → nhận diện → tra CSDL → chỉnh khẩu phần theo mô tả → người dùng xác nhận
                                                                      │
                                       ┌──────────────────────────────┴───────┐
                                       ▼                                      ▼
                               Ghi vào nhật ký                    (tùy chọn) Lưu vào "Món đã lưu"

Lần sau: Món đã lưu → chọn → chỉnh nhanh khẩu phần (×0.5, ×1, ×1.5...) → ghi vào nhật ký
```

**Nguồn tạo ra mục trong danh mục cá nhân:** (1) lưu kết quả sau khi xác nhận ở luồng AI, (2) tạo thủ công từ nguyên liệu, (3) lưu lại từ một bữa đã có trong nhật ký.

**Nguyên tắc thiết kế:**
- Mục đã lưu lưu dưới dạng **nguyên liệu + gram** (công thức), không chỉ một con số kcal. Nhờ đó chỉnh khẩu phần vẫn đúng.
- **Nhật ký là ảnh chụp tại thời điểm ghi (snapshot)**: lịch sử calo không đổi khi dữ liệu nguồn được sửa. Món đã lưu thì tham chiếu nguyên liệu, nên khi Admin sửa một nguyên liệu sai, lần dùng sau sẽ tính đúng.
- Nguyên liệu do người dùng tự nhập giá trị dinh dưỡng được gắn nhãn **"tự nhập"** với độ tin cậy thấp hơn, cùng tinh thần D12.
- Dữ liệu cá nhân nên không cần kiểm duyệt: sai số chỉ ảnh hưởng chính người nhập.
- **Phản hồi để cải thiện mô hình (tùy chọn):** khi người dùng sửa kết quả AI, hệ thống có thể ghi lại (món dự đoán, món đã sửa) để nhóm dùng ngoại tuyến cho đánh giá và huấn luyện lại. Chỉ lưu khi người dùng đồng ý, không đưa tự động vào CSDL chung.

### 9.5. Hướng triển khai AI (đã chốt O1)

| Phương án | Mô tả | Ưu | Nhược |
|---|---|---|---|
| O1. Microservice Python | PHP gọi API nhỏ (FastAPI + ONNX Runtime) | Kiểm soát tốt | Cần server Python riêng |
| O2. Suy luận trên trình duyệt | TensorFlow.js / ONNX Runtime Web | Không tốn chi phí suy luận ở server, hợp stack JS | Tải mô hình lần đầu, phụ thuộc điện thoại |
| O3. API ngoài | Dịch vụ vision/LLM | Nhanh nhất | Tốn phí, phụ thuộc bên ngoài |

**Đã chốt O1 (D14).** O2 bị loại vì lo RAM phía client. Lưu ý: mô hình phân loại món nhẹ, chạy được trên CPU. LLM chỉ là tùy chọn cho việc phân tích mô tả.

---

## 10. Initial Risk Assessment (chấm lại theo thời hạn 23 ngày)

Thang điểm: Xác suất (X) và Tác động (T) từ 1 đến 5, Điểm = X × T. Từ 15 trở lên là rủi ro cần xử lý sớm. Các dòng đánh dấu ↑ đã được nâng điểm so với v1.0 vì hạn nộp đã biết (25/10/2026).

| # | Rủi ro | X | T | Điểm | Hướng giảm thiểu |
|---|---|---|---|---|---|
| R3 | Độ chính xác nhận diện món và khẩu phần | 4 | 5 | **20** | Luồng xác nhận/chỉnh sửa, lớp "không nhận ra", đường nguyên liệu |
| R7 ↑ | Phạm vi rộng so với nhóm 3 người trong 23 ngày | 5 | 4 | **20** | MoSCoW, quy tắc cắt phạm vi (mục 11.3), Admin bằng script seed |
| R10 ↑ | Thời hạn ngắn (25/10/2026), PoC và dữ liệu có thể vượt kế hoạch | 4 | 5 | **20** | Cổng go/no-go 11/10, đóng băng tính năng 22/10 |
| R6 ↑ | Soạn công thức chuẩn và quy đổi khẩu phần (30-50 món, có cân đo mẫu) | 5 | 4 | **20** | Bắt đầu từ E1 song song với tài liệu, soạn theo nhóm món, mức tối thiểu định trước |
| R4 | Domain gap: ảnh web khác ảnh chụp điện thoại | 4 | 4 | **16** | PoC với ảnh tự chụp, tập kiểm thử riêng, thu thêm dữ liệu |
| R11 (mới) | Tích hợp PHP và dịch vụ AI bị phát hiện lỗi quá muộn | 3 | 4 | 12 | Chốt hợp đồng API và dựng stub ngày 07/10, kiểm thử tích hợp từ C1 |
| R2 | An toàn sức khỏe, khuyến khích ăn kiêng cực đoan | 2 | 5 | 10 | Sàn calo tối thiểu, disclaimer y tế |
| R1 | Cộng calo tập luyện gây ăn bù quá mức | 3 | 3 | 9 | Hệ số chính sách, nhãn độ tin cậy |
| R5 | Giấy phép dữ liệu huấn luyện chưa kiểm chứng | 3 | 3 | 9 | Kiểm tra điều khoản từng bộ, quan trọng hơn nếu public |
| R8 | Vận hành dịch vụ suy luận khi nhiều người dùng, chi phí server | 3 | 3 | 9 | Hàng đợi, rate limit, load test nhẹ ở C2, fallback sang nhập thủ công |
| R9 | Quyền riêng tư (ảnh món ăn, cân nặng) | 2 | 4 | 8 | Lưu trữ tối thiểu, cho phép xóa dữ liệu |

**Nhận xét:** R3, R4, R6 đều xoay quanh luồng AI Food Logging và dữ liệu món Việt, nên cả hai việc này phải bắt đầu ngay từ E1. R7 và R10 buộc nhóm phải có quy tắc cắt phạm vi ghi sẵn từ đầu (mục 11.3).

---

## 11. Kế Hoạch Lần Lặp (Iteration Plan v1.1: 02/10 – 25/10/2026)

Tổng thời gian: **23 ngày (~3.5 tuần)**, phân bổ theo 4 Pha của Unified Process.

```
02/10     05/10      07/10     11/10      12/10                  21/10  22/10     25/10
  │─ INCEPTION ─│────────── ELABORATION ───────│──────── CONSTRUCTION ────────│ TRANSITION │
  │   (I1)      │   E1    ▲ mốc API   ▲ GO/NO-GO│    C1       │     C2         │ T1 (freeze)│
```

### 11.1. Chi tiết theo Iteration

| Pha | Iteration | Thời gian | Mục tiêu trọng tâm | Deliverables |
|---|---|---|---|---|
| **INCEPTION** | **I1** | 02/10 – 04/10 | Chốt Business Case, Vision & Scope, 14 Use Case, Risk Assessment, kế hoạch | `01_business_modeling.md` (v1.1) |
| **ELABORATION** | **E1** | 05/10 – 11/10 (Tuần 1) | Ba nhánh chạy song song:<br>**(1) Tài liệu gọn:** SRS (FR/NFR); đặc tả chi tiết cho UC03, UC04, UC05, UC07, các UC còn lại mô tả ngắn; ERD, Sequence Diagram cho UC04/05/07.<br>**(2) AI PoC:** mốc **07/10** chốt hợp đồng API và dựng *stub* trả kết quả giả; mốc **11/10** là **cổng go/no-go**.<br>**(3) Dữ liệu:** bắt đầu soạn công thức chuẩn và bảng quy đổi khẩu phần (mục tiêu đề xuất cuối E1: ít nhất 15 món có công thức) | `02_requirements_specification.md`<br>`03_analysis_and_design.md`<br>`schema.sql`<br>PoC AI Service + số liệu đánh giá trên ảnh tự chụp<br>Bản nháp bảng công thức chuẩn |
| **CONSTRUCTION** | **C1** | 12/10 – 16/10 (Tuần 2) | Auth và Onboarding; Energy Engine (BMR, ngân sách động); Workout Tracker (MET + nhập tay); nạp dữ liệu dinh dưỡng qua **script seed (Admin, D25)**; thay stub bằng dịch vụ AI thật; **bắt đầu kiểm thử tích hợp** | Module User/Auth; Energy Engine ổn định; CSDL dinh dưỡng nạp sẵn; PHP gọi AI thật |
| | **C2** | 17/10 – 21/10 | AI Food Logging hoàn chỉnh (ảnh + prompt + xác nhận/chỉnh khẩu phần + snapshot nhật ký); danh mục cá nhân (Món đã lưu, tạo món tùy chỉnh, ghi nhanh); Dashboard rút gọn; **rate limit và một lượt load test nhẹ** cho dịch vụ AI; kiểm thử hồi quy. *Smart Suggestions chỉ làm nếu còn dư* | Các module Must chạy trơn tru trên PHP + MySQL + AI Service |
| **TRANSITION** | **T1** | 22/10 – 25/10 | **Đóng băng tính năng từ 22/10, chỉ sửa lỗi.** Kiểm thử hệ thống và responsive; tài liệu kỹ thuật, hướng dẫn sử dụng, slide; đóng gói; demo (hosting hoặc localhost) | Báo cáo tổng kết; video/slide demo; **nộp bài 25/10/2026** |

### 11.2. Cổng go/no-go ngày 11/10 (PoC nhận diện món)

- **Tiêu chí:** độ đúng top-1 và tỉ lệ chuyển đúng sang đường nguyên liệu, đo trên **ảnh tự chụp bằng điện thoại** (tách khỏi tập kiểm thử lấy từ web). Ngưỡng cụ thể và **mức số món tối thiểu chấp nhận được** do nhóm ghi ra ở mốc 07/10, trước khi có kết quả.
- **Nếu đạt:** tiếp tục theo kế hoạch, mở rộng số món trong C1/C2 khi còn thời gian.
- **Nếu chưa đạt, phương án dự phòng theo thứ tự:** (1) giảm xuống nhóm khoảng 15-20 món có dữ liệu tốt nhất; (2) nâng ngưỡng tin cậy để nhiều ảnh hơn đi sang đường nguyên liệu; (3) giữ AI ở vai trò gợi ý, người dùng xác nhận là chính.

### 11.3. Quy tắc cắt phạm vi nếu trễ tiến độ

Cắt theo thứ tự, mỗi bước chỉ thực hiện khi cần:
1. Phản hồi AI để huấn luyện lại (tùy chọn)
2. Smart Meal Suggestions (Could)
3. Giao diện quản trị (Should), giữ script seed
4. Dashboard: giảm xuống biểu đồ calo và macro trong ngày
5. Giảm số món có công thức chuẩn xuống mức tối thiểu đã ghi ở mốc 07/10

**Không cắt:** luồng xác nhận/chỉnh sửa kết quả AI (UC05), nhật ký dạng snapshot, và các cảnh báo an toàn (R2).

---

## 12. Kết Luận & Chuyển Pha

* **Pha 1 (INCEPTION):** ĐÃ CHÍNH THỨC HOÀN TẤT VÀ ĐÓNG LẠI.
* **Bước tiếp theo:** Khởi động **Pha 2 (ELABORATION) - Iteration E1**, bắt đầu với **Workflow 2: Requirements Specification (FR/NFR Matrix & Chi tiết Use Cases)** trong file `02_requirements_specification.md`.

---

## 13. Lịch sử thay đổi (v1.0 → v1.1)

| Nhóm | Thay đổi |
|---|---|
| Nhất quán | D10 đánh dấu bị thay thế bởi D22; D18 và D24 chốt; mục 1 sửa stack (do nhóm chọn, MySQL đã chốt); mục 9.5 đổi tiêu đề; sắp lại thứ tự D18; bỏ `SKILL.md` khỏi deliverables I1; mục 3 và mục 0 ghi rõ benchmark chưa kiểm chứng |
| Quyết định mới | D25 (Admin bằng script seed), D26 (cổng go/no-go 11/10), D27 (đóng băng tính năng 22/10) |
| Phạm vi | Giao diện Admin chuyển sang Should; UC10 ghi rõ "chỉ làm nếu còn dư"; phản hồi AI là tùy chọn |
| Rủi ro | Chấm lại R6, R7, R10 lên 20; thêm R11 (tích hợp muộn) |
| Kế hoạch | E1 chia ba nhánh (tài liệu gọn, AI PoC hai mốc, dữ liệu); dữ liệu bắt đầu từ E1; kiểm thử tích hợp từ C1; thêm rate limit và load test nhẹ ở C2; đóng băng tính năng từ 22/10; thêm mục 11.2 và 11.3 |
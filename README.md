ĐỀ CƯƠNG TỔNG QUAN ĐỀ TÀI
MÔN LẬP TRÌNH WEB VÀ ỨNG DỤNG

FLEXIDIET
Web App quản lý ăn uống và luyện tập theo
“Ngân sách Calo Động”

FlexiDiet – Dynamic Calorie Budget & Fitness Web App

I. TỔNG QUAN
Đề tài xây dựng một Web App hỗ trợ người dùng quản lý chế độ ăn uống và luyện tập theo hướng linh hoạt, thay vì yêu cầu tuân thủ một thực đơn cố định. Điểm cốt lõi của hệ thống là mô hình “Ngân sách Calo Động” (Dynamic Calorie Budget): ứng dụng xác định mức năng lượng mục tiêu dựa trên thông tin cá nhân, sau đó cập nhật lượng calo khả dụng theo hoạt động vận động thực tế trong ngày.
Người dùng có thể ghi nhận món ăn, lượng nước và các hoạt động luyện tập hằng ngày. Hệ thống tổng hợp lượng calo nạp vào (Calories In), lượng calo tiêu hao từ hoạt động (Calories Out) và ngân sách calo còn lại để người dùng chủ động lựa chọn món ăn phù hợp với mục tiêu. Cách tiếp cận này hướng tới việc giúp người dùng duy trì chế độ ăn uống dễ thực hiện hơn trong đời sống thực tế.
Một thành phần nổi bật của đề tài là chức năng AI hỗ trợ nhận diện món ăn từ hình ảnh. Người dùng có thể tải ảnh/chụp ảnh món ăn, hệ thống gửi dữ liệu đến mô hình hoặc dịch vụ AI để nhận diện món ăn và trả về thông tin ước lượng liên quan như tên món, khẩu phần và calo. Kết quả AI được đưa vào nhật ký sau khi người dùng kiểm tra và xác nhận.
Hệ thống được tổ chức thành các khu vực chính gồm: trang giới thiệu, đăng ký/onboarding, dashboard, nhật ký ăn uống, nhật ký luyện tập, lịch sử và thống kê, hồ sơ cá nhân và khu vực quản trị dữ liệu. Giao diện hướng đến thiết kế hiện đại, trực quan và responsive trên desktop, tablet và smartphone.
1.1. Tên đề tài
Tên ngắn gọn đề xuất: “FlexiDiet – Quản lý ăn uống & luyện tập theo Ngân sách Calo Động”.
Tên đầy đủ đề xuất: “Xây dựng Web App quản lý chế độ ăn uống và luyện tập khoa học theo phương pháp Ngân sách Calo Động tích hợp AI nhận diện món ăn”.
1.2. Ý tưởng và vấn đề cần giải quyết
Người dùng thường khó duy trì thực đơn cố định vì lịch học, lịch làm việc và sở thích ăn uống thay đổi.
Việc theo dõi calo thủ công gây mất thời gian và dễ bỏ sót lượng vận động thực tế.
Người dùng cần một cách trực quan để biết hôm nay đã ăn bao nhiêu, đã vận động bao nhiêu và còn bao nhiêu calo có thể sử dụng.
Việc nhập món ăn thủ công có thể gây khó khăn; AI nhận diện món ăn từ ảnh giúp giảm thao tác nhập dữ liệu.
Dữ liệu ăn uống và luyện tập cần được lưu theo ngày để người dùng xem lại tiến độ và xu hướng.
1.3. Mục tiêu của đề tài
Xây dựng một Web App có luồng sử dụng hoàn chỉnh từ đăng ký, thiết lập mục tiêu, ghi nhận ăn uống/luyện tập đến theo dõi tiến độ.
Tự động tính BMR, TDEE và ngân sách calo mục tiêu dựa trên thông tin cá nhân và mục tiêu tăng/giảm/duy trì cân nặng.
Xây dựng cơ chế Ngân sách Calo Động, trong đó ngân sách còn lại được cập nhật theo dữ liệu vận động trong ngày.
Cho phép người dùng ghi nhận món ăn, khẩu phần, thời điểm ăn và lượng calo ước tính.
Tích hợp AI nhận diện món ăn từ hình ảnh và cho phép người dùng kiểm tra/chỉnh sửa kết quả trước khi lưu.
Cho phép ghi nhận các hoạt động luyện tập và ước lượng năng lượng tiêu hao.
Hiển thị dashboard trực quan với Calories In, Calories Out, ngân sách còn lại, macro và nước uống.
Lưu lịch sử và cung cấp thống kê theo ngày/tuần/tháng.
Thiết kế giao diện responsive, dễ sử dụng và phù hợp với phạm vi môn Lập trình Web và Ứng dụng.
1.4. Đối tượng sử dụng
Đối tượng
Vai trò
Nhu cầu chính
Người dùng
Người quản lý chế độ ăn và luyện tập
Thiết lập mục tiêu, theo dõi calo, ghi món ăn, ghi luyện tập, xem tiến độ.
Quản trị viên
Quản lý hệ thống
Quản lý tài khoản, món ăn, dữ liệu dinh dưỡng, bài tập và theo dõi dữ liệu hệ thống.


1.5. Phạm vi MVP
Phiên bản MVP tập trung vào các chức năng cốt lõi để có thể hoàn thiện, kiểm thử và trình diễn trong phạm vi môn học. Các tính năng tích hợp thiết bị đeo, đồng bộ Apple Health/Google Fit hoặc mô hình AI tự huấn luyện có thể xem là hướng phát triển mở rộng, không bắt buộc trong MVP.
II. MÔ TẢ CHI TIẾT
2.1. Quản lý tài khoản và hồ sơ cá nhân
Hệ thống cho phép người dùng đăng ký, đăng nhập, đăng xuất và quản lý thông tin cá nhân. Thông tin hồ sơ là cơ sở để hệ thống tính toán nhu cầu năng lượng.
Đăng ký bằng họ tên, email và mật khẩu.
Kiểm tra dữ liệu đầu vào và không cho phép email đã tồn tại.
Đăng nhập/đăng xuất và duy trì phiên đăng nhập.
Quên và đặt lại mật khẩu (có thể triển khai ở mức mô phỏng trong MVP).
Cập nhật chiều cao, cân nặng, tuổi, giới tính, mức độ vận động.
Thiết lập mục tiêu: giảm cân, duy trì cân nặng hoặc tăng cân.
Cập nhật thông tin theo thời gian để hệ thống tính lại chỉ số năng lượng.
2.2. Onboarding và tính toán năng lượng
Sau khi đăng ký, người dùng đi qua quy trình onboarding nhiều bước. Hệ thống thu thập dữ liệu cơ bản và đưa ra mức năng lượng mục tiêu ban đầu.
Bước 1: thông tin cơ bản.
Bước 2: chiều cao, cân nặng, tuổi và giới tính.
Bước 3: mức độ vận động thường ngày.
Bước 4: mục tiêu cân nặng và tốc độ thay đổi mong muốn.
Bước 5: hệ thống tính BMR/TDEE và đề xuất ngân sách calo ban đầu.
Bước 6: người dùng xác nhận để bắt đầu sử dụng dashboard.
Công thức và quy tắc tính toán cần được triển khai thành các hàm/module riêng để dễ kiểm thử và thay đổi.
2.3. Ngân sách Calo Động
Đây là chức năng trung tâm của đề tài. Thay vì coi mức calo mục tiêu là một con số cố định không thay đổi, hệ thống theo dõi vận động trong ngày và cập nhật phần ngân sách có thể sử dụng.
Xác định ngân sách calo cơ sở từ mục tiêu năng lượng của người dùng.
Ghi nhận lượng calo nạp vào từ các bữa ăn.
Ghi nhận calo tiêu hao ước tính từ các hoạt động luyện tập.
Tính lượng calo còn lại trong ngày.
Hiển thị trạng thái ngân sách: còn nhiều, gần giới hạn hoặc đã vượt mục tiêu.
Khi người dùng thêm/sửa/xóa bữa ăn hoặc bài tập, dashboard và ngân sách phải được cập nhật.
Lưu lịch sử thay đổi để dữ liệu theo ngày nhất quán.
Mô hình logic ở mức ứng dụng có thể biểu diễn: Ngân sách còn lại = Ngân sách cơ sở + Calo vận động được ghi nhận − Calo đã ăn. Đây là mô hình nghiệp vụ của đề tài; các hệ số và cách ước lượng calo cần được trình bày rõ trong báo cáo và không nên được hiểu là tư vấn y khoa.
2.4. Quản lý nhật ký ăn uống
Thêm bữa ăn theo thời điểm: sáng, trưa, tối hoặc bữa phụ.
Tìm kiếm/chọn món ăn từ cơ sở dữ liệu.
Nhập khẩu phần hoặc số lượng.
Tự động tính tổng calo theo khẩu phần.
Hiển thị protein, carbohydrate và chất béo nếu dữ liệu món ăn có sẵn.
Cho phép sửa/xóa bản ghi.
Tổng hợp dữ liệu theo ngày.
2.5. AI nhận diện món ăn qua hình ảnh
Người dùng có thể tải ảnh hoặc chụp ảnh món ăn. Ảnh được gửi đến lớp dịch vụ AI để nhận diện. Hệ thống nhận kết quả, hiển thị cho người dùng kiểm tra và chỉ lưu vào nhật ký sau khi xác nhận.
Chọn/tải ảnh món ăn.
Kiểm tra định dạng và kích thước ảnh.
Gửi ảnh đến AI service/model.
Nhận tên món ăn hoặc nhóm món ăn dự đoán.
Hiển thị độ tin cậy nếu dịch vụ AI cung cấp.
Đề xuất khẩu phần và mức calo tham khảo nếu có dữ liệu.
Cho phép người dùng chỉnh sửa tên món, khẩu phần hoặc calo.
Xác nhận để tạo bản ghi trong nhật ký ăn uống.
Xử lý trường hợp AI không nhận diện được món ăn bằng cách cho phép nhập thủ công.
Trong phạm vi môn học, AI có thể được tích hợp thông qua một API/model có sẵn thay vì tự huấn luyện mô hình từ đầu. Phần tích hợp phải được thiết kế như một module độc lập để Web App không phụ thuộc cứng vào một nhà cung cấp duy nhất.
2.6. Quản lý nhật ký luyện tập
Chọn loại hoạt động: đi bộ, chạy, đạp xe, gym hoặc hoạt động khác.
Nhập thời lượng, cường độ hoặc các thông số phù hợp.
Ước lượng calo tiêu hao dựa trên dữ liệu người dùng và bài tập.
Cho phép sửa/xóa bản ghi.
Cập nhật Calories Out và ngân sách calo động.
Xem lịch sử luyện tập theo ngày.
2.7. Quản lý nước uống
Thiết lập mục tiêu lượng nước trong ngày.
Thêm nhanh lượng nước đã uống.
Hiển thị tiến độ dưới dạng thanh hoặc vòng tròn.
Cho phép chỉnh sửa/xóa dữ liệu ghi nhận.
2.8. Dashboard
Dashboard là màn hình trung tâm sau khi đăng nhập, tổng hợp các dữ liệu quan trọng của ngày hiện tại.
Ngân sách calo mục tiêu.
Calories In.
Calories Out từ hoạt động.
Calo còn lại.
Tỷ lệ protein/carbohydrate/chất béo nếu có dữ liệu.
Tiến độ nước uống.
Danh sách bữa ăn trong ngày.
Danh sách bài tập trong ngày.
Thông báo/trạng thái ngân sách.
Tóm tắt tiến độ cân nặng.
2.9. Lịch sử và thống kê
Xem lịch sử ăn uống theo ngày.
Xem lịch sử luyện tập.
Xem biểu đồ Calories In/Out.
Xem xu hướng cân nặng.
Xem mức độ hoàn thành mục tiêu theo tuần/tháng.
Lọc dữ liệu theo khoảng thời gian.
2.10. Quản lý dữ liệu món ăn
Hệ thống cần một cơ sở dữ liệu món ăn để hỗ trợ tìm kiếm, tính toán và đối chiếu kết quả AI.
Tên món ăn.
Nhóm món ăn.
Khẩu phần tham chiếu.
Calo.
Protein, carbohydrate, chất béo nếu có.
Hình ảnh minh họa nếu cần.
Trạng thái sử dụng.
2.11. Khu vực quản trị
Dashboard quản trị.
Quản lý tài khoản người dùng.
Quản lý món ăn và thông tin dinh dưỡng.
Quản lý loại bài tập.
Quản lý nội dung/hướng dẫn sử dụng.
Xem thống kê sử dụng ở mức phù hợp với phạm vi môn học.
Phân quyền tài khoản quản trị và người dùng.
2.12. Phân quyền và bảo mật
Phân biệt USER và ADMIN.
Kiểm tra quyền ở phía máy chủ, không chỉ ẩn nút trên giao diện.
Hash mật khẩu trước khi lưu.
Kiểm tra và làm sạch dữ liệu đầu vào.
Sử dụng prepared statement/ORM phù hợp để hạn chế SQL Injection.
Xử lý dữ liệu hiển thị để hạn chế XSS.
Kiểm tra loại/kích thước ảnh trước khi lưu hoặc gửi sang dịch vụ AI.
Không lưu API key của dịch vụ AI trực tiếp trong mã nguồn phía client.
III. YÊU CẦU BỔ SUNG
3.1. UI và UX
Giao diện thống nhất giữa các trang.
Dashboard ưu tiên thông tin calo và tiến độ.
Có trạng thái loading, empty state, success, error và confirmation.
Biểu mẫu có thông báo lỗi rõ ràng.
Thao tác thêm món ăn/bài tập phải ít bước.
Các thông tin quan trọng như calo còn lại phải dễ nhận biết.
3.2. Responsive Design
Hỗ trợ smartphone, tablet và desktop.
Dashboard chuyển từ bố cục nhiều cột sang một cột trên màn hình nhỏ.
Bảng dữ liệu có cách hiển thị phù hợp trên mobile.
Form và modal không gây tràn ngang.
3.3. Cơ sở dữ liệu
Dự kiến sử dụng MySQL. Các nhóm bảng chính:
Nhóm
Bảng dự kiến
Mục đích
Tài khoản
users, user_profiles
Tài khoản, thông tin thể chất và mục tiêu.
Năng lượng
daily_budgets, weight_logs
Ngân sách calo theo ngày và lịch sử cân nặng.
Ăn uống
foods, meals, meal_items
Món ăn, bữa ăn và thành phần.
AI
food_ai_logs
Lưu yêu cầu/kết quả nhận diện và trạng thái xác nhận.
Luyện tập
exercises, workouts
Danh mục bài tập và nhật ký vận động.
Nước
water_logs
Lượng nước uống trong ngày.
Quản trị
admin_logs
Theo dõi thao tác quản trị nếu cần.


3.4. Công nghệ dự kiến
Thành phần
Công nghệ dự kiến
Mục đích
Frontend
HTML5, CSS3, Bootstrap, JavaScript/jQuery
Giao diện, responsive và tương tác phía client.
Backend
PHP
Xử lý nghiệp vụ, authentication, API và kết nối CSDL.
Database
MySQL
Lưu tài khoản, món ăn, nhật ký và thống kê.
Web server
Apache/XAMPP
Môi trường phát triển và chạy ứng dụng.
AI
AI API/model nhận diện ảnh
Nhận diện món ăn từ hình ảnh.
Biểu đồ
Chart.js hoặc thư viện tương đương
Hiển thị thống kê calo/cân nặng/luyện tập.


3.5. API và luồng dữ liệu
Frontend gửi request đến backend bằng form hoặc AJAX/fetch.
Backend kiểm tra session, dữ liệu đầu vào và quyền truy cập.
Backend thực hiện nghiệp vụ và truy vấn MySQL.
Với chức năng AI, backend tiếp nhận ảnh và gọi AI service; API key được giữ ở phía server.
Kết quả AI được chuẩn hóa thành dữ liệu mà frontend có thể hiển thị.
Sau khi người dùng xác nhận, backend lưu kết quả vào meal/meal_items và cập nhật ngân sách ngày.
3.6. Responsive và khả năng triển khai
Ứng dụng phải có khả năng chạy trong môi trường XAMPP ở giai đoạn phát triển và có cấu trúc đủ rõ ràng để triển khai trên máy chủ hỗ trợ PHP/MySQL. Thông tin cấu hình như kết nối CSDL và khóa API cần được quản lý tập trung, không hard-code rải rác trong mã nguồn.
IV. CÁC MÀN HÌNH CHỨC NĂNG DỰ KIẾN
STT
Màn hình
Route
Chức năng chính
1
Trang chủ
/
Giới thiệu FlexiDiet, cơ chế Ngân sách Calo Động, tính năng AI và CTA đăng ký.
2
Đăng nhập
/login
Đăng nhập bằng email/mật khẩu.
3
Đăng ký
/register
Tạo tài khoản và kiểm tra dữ liệu.
4
Onboarding
/onboarding
Nhập thông tin cá nhân, mục tiêu và mức vận động.
5
Dashboard
/dashboard
Tổng quan Calories In/Out, ngân sách còn lại, nước và tiến độ.
6
Nhật ký ăn uống
/food-log
Danh sách bữa ăn trong ngày và thêm/sửa/xóa món.
7
AI nhận diện món ăn
/food-log/ai
Tải/chụp ảnh, nhận diện món và xác nhận kết quả.
8
Tìm món ăn
/foods
Tìm kiếm món trong cơ sở dữ liệu.
9
Chi tiết món ăn
/foods/:id
Thông tin dinh dưỡng và khẩu phần tham chiếu.
10
Nhật ký luyện tập
/workout-log
Ghi nhận hoạt động và calo tiêu hao.
11
Thêm bài tập
/workout-log/add
Nhập bài tập, thời lượng/cường độ.
12
Nước uống
/water
Theo dõi mục tiêu và lượng nước.
13
Lịch sử
/history
Xem các bản ghi theo ngày.
14
Thống kê
/statistics
Biểu đồ Calories In/Out, cân nặng và tiến độ.
15
Hồ sơ cá nhân
/profile
Cập nhật thông tin và mục tiêu.
16
Cài đặt
/settings
Thiết lập tài khoản và tùy chọn.
17
Quản trị Dashboard
/admin
Tổng quan dữ liệu hệ thống.
18
Quản lý người dùng
/admin/users
Xem, tìm kiếm, khóa/mở tài khoản.
19
Quản lý món ăn
/admin/foods
CRUD dữ liệu món ăn và dinh dưỡng.
20
Quản lý bài tập
/admin/exercises
CRUD danh mục bài tập.
21
Quản lý nội dung
/admin/content
Quản lý hướng dẫn/nội dung hỗ trợ.
22
Nhật ký AI
/admin/ai-logs
Theo dõi các lượt nhận diện ở mức cần thiết.
23
Báo cáo thống kê
/admin/reports
Thống kê sử dụng hệ thống.


Các thao tác nhỏ như modal xác nhận, chọn khẩu phần, chỉnh sửa nhanh, bộ lọc ngày, thông báo thành công/lỗi và các thành phần biểu đồ có thể được triển khai bên trong các màn hình trên thay vì tách thành trang độc lập.
V. LUỒNG NGHIỆP VỤ CHÍNH
5.1. Luồng đăng ký → thiết lập mục tiêu
Người dùng mở Trang chủ và chọn Đăng ký.
Nhập thông tin tài khoản.
Hệ thống kiểm tra dữ liệu và tạo tài khoản.
Người dùng thực hiện onboarding.
Hệ thống tính BMR/TDEE và ngân sách calo cơ sở.
Người dùng xác nhận mục tiêu.
Hệ thống tạo cấu hình cá nhân và chuyển đến Dashboard.
5.2. Luồng ghi món ăn bằng AI
Người dùng mở Nhật ký ăn uống.
Chọn chức năng Nhận diện món ăn bằng AI.
Tải/chụp ảnh món ăn.
Frontend kiểm tra file và gửi ảnh đến backend.
Backend gọi dịch vụ AI.
AI trả về kết quả nhận diện.
Hệ thống hiển thị món ăn và thông tin ước lượng.
Người dùng chỉnh sửa nếu cần.
Người dùng xác nhận.
Backend lưu meal và cập nhật Calories In/ngân sách còn lại.
5.3. Luồng ghi luyện tập → cập nhật ngân sách
Người dùng chọn Nhật ký luyện tập.
Chọn loại hoạt động và nhập thông số.
Hệ thống ước lượng calo tiêu hao.
Lưu bản ghi workout.
Tính lại Calories Out trong ngày.
Tính lại ngân sách calo còn lại.
Dashboard cập nhật số liệu mới.
VI. LOGIC NGHIỆP VỤ VÀ DỮ LIỆU CỐT LÕI
Đối tượng
Dữ liệu chính
Quan hệ/nghiệp vụ
User
id, email, password, role
Một user có profile, meal logs, workout logs, water logs.
User Profile
height, weight, age, sex, activity, goal
Dùng để tính BMR/TDEE và mục tiêu.
Daily Budget
date, base_budget, exercise_bonus, consumed, remaining
Mỗi ngày có một ngân sách; thay đổi theo bản ghi ăn/uống và vận động.
Food
name, serving, calories, macros
Dữ liệu món ăn dùng để tìm kiếm và tính calo.
Meal
user, date, meal_type, total_calories
Một ngày có nhiều bữa ăn.
Meal Item
meal, food, quantity, calories
Chi tiết món trong bữa.
Workout
user, date, exercise, duration, calories_out
Ghi nhận vận động.
AI Log
image, prediction, confidence, status
Lưu quá trình AI để kiểm tra và xác nhận.
Water Log
user, date, amount
Theo dõi lượng nước.


VII. KẾ HOẠCH THỰC HIỆN ĐỀ TÀI
Giai đoạn
Công việc
Sản phẩm đầu ra
1. Phân tích
Chốt phạm vi, actor, use case, sitemap, yêu cầu.
Đề cương + use case + sitemap.
2. Thiết kế
Thiết kế UI, database, luồng nghiệp vụ.
Wireframe + ERD + kiến trúc thư mục.
3. Backend nền tảng
DB, đăng ký, đăng nhập, session, phân quyền.
Module authentication hoàn chỉnh.
4. Energy Engine
BMR/TDEE, mục tiêu và ngân sách calo động.
Module tính toán + test dữ liệu.
5. Food Log
CRUD bữa ăn, món ăn, khẩu phần.
Nhật ký ăn uống.
6. Workout/Water
CRUD luyện tập và nước uống.
Nhật ký luyện tập + nước.
7. AI
Upload ảnh, gọi AI API, xử lý kết quả, xác nhận.
Chức năng nhận diện món ăn.
8. Dashboard/Statistics
Biểu đồ và tổng hợp theo ngày/tuần/tháng.
Dashboard + trang thống kê.
9. Admin
Quản lý user, food, exercise và báo cáo.
Khu vực quản trị.
10. Kiểm thử
Functional, validation, responsive, security cơ bản.
Test cases + sửa lỗi.
11. Hoàn thiện
Tối ưu UI, tài liệu, demo và báo cáo.
Phiên bản trình diễn cuối.


VIII. KẾT QUẢ DỰ KIẾN
Một Web App chạy được trên môi trường XAMPP/PHP/MySQL.
Người dùng có thể đăng ký, thiết lập hồ sơ và mục tiêu.
Hệ thống tính toán BMR/TDEE và tạo ngân sách calo.
Ngân sách được cập nhật theo lượng ăn vào và vận động ghi nhận.
Có nhật ký ăn uống, luyện tập và nước uống.
Có AI nhận diện món ăn từ hình ảnh ở mức tích hợp API/model.
Có Dashboard và biểu đồ thống kê.
Có khu vực quản trị dữ liệu.
Giao diện responsive.
Có tài liệu mô tả hệ thống, cơ sở dữ liệu, use case và hướng dẫn sử dụng.
IX. GIỚI HẠN VÀ HƯỚNG PHÁT TRIỂN
Giới hạn MVP:
Calo và dinh dưỡng là dữ liệu ước lượng, phụ thuộc dữ liệu món ăn và kết quả AI.
Chưa bắt buộc đồng bộ trực tiếp với smartwatch/fitness tracker.
Không tự huấn luyện mô hình AI từ đầu trong phạm vi môn học.
Chưa thay thế tư vấn của chuyên gia dinh dưỡng hoặc bác sĩ.
Có thể sử dụng dữ liệu mẫu cho một số món ăn/bài tập để bảo đảm tiến độ.
Hướng phát triển:
Đồng bộ Apple Health/Google Fit hoặc thiết bị đeo.
Cá nhân hóa đề xuất món ăn theo mục tiêu và lịch sử.
Nhận diện nhiều món trong cùng một ảnh.
Ước lượng khẩu phần bằng computer vision nâng cao.
Chatbot AI hỗ trợ giải thích dữ liệu và đề xuất kế hoạch.
PWA/mobile app.
Thông báo nhắc ăn, uống nước và luyện tập.
Phân tích xu hướng dài hạn và phát hiện thay đổi bất thường.
X. TIÊU CHÍ HOÀN THÀNH ĐỀ TÀI
Nhóm
Tiêu chí kiểm tra
Chức năng
Các luồng đăng ký → onboarding → dashboard → ăn uống → luyện tập → thống kê hoạt động đúng.
Dynamic Budget
Thêm/sửa/xóa meal hoặc workout làm thay đổi ngân sách đúng theo logic đã định.
AI
Ảnh được gửi đến AI, nhận kết quả, người dùng có thể chỉnh sửa và xác nhận.
Database
Quan hệ bảng rõ ràng, khóa chính/ngoại và dữ liệu nhất quán.
Security
Hash password, validation, prepared statement, phân quyền server-side.
UI/UX
Giao diện thống nhất, responsive và có trạng thái lỗi/loading/empty.
Kiểm thử
Có test case cho chức năng chính và các trường hợp dữ liệu không hợp lệ.
Demo
Có kịch bản demo hoàn chỉnh và dữ liệu mẫu đủ để trình diễn.


XI. TÓM TẮT ĐỀ TÀI
FlexiDiet là Web App quản lý ăn uống và luyện tập tập trung vào ý tưởng Ngân sách Calo Động. Thay vì ép người dùng theo một thực đơn cố định, hệ thống cung cấp một ngân sách năng lượng có thể được điều chỉnh theo hoạt động trong ngày. Người dùng ghi nhận món ăn và bài tập, trong khi Dashboard tổng hợp Calories In, Calories Out và lượng calo còn lại. Chức năng AI nhận diện món ăn qua hình ảnh giúp giảm thao tác nhập dữ liệu. Về mặt kỹ thuật, đề tài bao phủ nhiều nội dung phù hợp với môn Lập trình Web và Ứng dụng: thiết kế giao diện responsive, JavaScript/AJAX, backend PHP, MySQL, authentication, CRUD, API integration, upload ảnh, phân quyền, biểu đồ và triển khai ứng dụng web.

— HẾT —

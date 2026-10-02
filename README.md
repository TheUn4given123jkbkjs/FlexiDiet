# FlexiDiet – Smart Dynamic Nutrition & Workout Platform

> **Đồ án môn học:** Lập trình Web và Ứng dụng & Công Nghệ Phần Mềm  
> **Nhóm thực hiện:** 3 thành viên  
> **Thời hạn nộp bài:** 25/10/2026  
> **Phương pháp luận:** Unified Process (Sommerville, 9th ed.)

---

## 📌 Tổng Quan (Abstract)

**FlexiDiet** là ứng dụng web quản lý dinh dưỡng và luyện tập thể chất theo phương pháp **"Ngân sách Calo Động" (Dynamic Calorie Budget)** kết hợp **AI Vision nhận diện món ăn**.

Thay vì áp đặt một thực đơn cố định gây áp lực cho người dùng, FlexiDiet tự động điều chỉnh lượng calo khả dụng trong ngày dựa trên mức độ vận động thực tế:
$$\text{Calo còn lại} = \text{BMR} \times \text{Hệ số sinh hoạt} \pm \text{Mục tiêu} + \sum (\text{Calo tập} \times \text{Hệ số}) - \text{Calo đã nạp}$$

Điểm đột phá của dự án là việc tối ưu hóa cho **ẩm thực Việt Nam**: người dùng chỉ cần chụp ảnh món ăn và nhập mô tả ngắn gọn (ví dụ: *"1 tô bún bò Huế ít bún nhiều thịt"*), hệ thống sẽ tự động bóc tách năng lượng (Calories) và các chất dinh dưỡng đa lượng (Protein, Carb, Fat) vào nhật ký.

---

## 🧩 6 Module Chức Năng Cốt Lõi (MVP)

1. **User Profile & Energy Engine:** Tính BMR (Mifflin-St Jeor) và TDEE, thiết lập mục tiêu thể hình.
2. **Dynamic Calorie Budget:** Tự động cân đối calo nạp/tiêu hao theo thời gian thực.
3. **AI Food Logging:** Nhận diện món ăn qua ảnh chụp + Prompt mô tả, kèm thanh trượt tinh chỉnh khẩu phần.
4. **Workout Tracker:** Ghi nhận bài tập và tự động tính calo đốt cháy theo công thức MET.
5. **Smart Meal Suggestions:** Đề xuất món ăn phù hợp với lượng calo & protein còn thiếu trong ngày.
6. **Dashboard & Analytics:** Bảng Kanban bữa ăn, trực quan hóa cân bằng năng lượng và biểu đồ tiến trình.

---

## 🛠️ Công Nghệ Sử Dụng (Tech Stack)

* **Frontend:** HTML5, CSS3 (Custom Design System + Dark/Light Mode), Bootstrap, JavaScript.
* **Backend:** PHP thuần (PDO Architecture, RESTful JSON APIs).
* **Database:** MySQL (Relational Schema + Cơ chế Snapshot nhật ký).
* **AI Service:** Python Microservice (FastAPI + ONNX Runtime / Computer Vision Model).

---

## 📁 Cấu Trúc Thư Mục (Project Structure)

```
Midterm/
├── README.md                     # Tài liệu tổng quan (Abstract & Roadmap)
├── docs/                         # Toàn bộ tài liệu quy trình Unified Process (UP)
│   ├── 00_concept/               # Đề cương chi tiết (outline.md), ý tưởng & sitemap
│   ├── 01_inception/             # Tài liệu Business Modeling, Lean Canvas & Quyết định (v1.1)
│   ├── 02_elaboration/           # Đặc tả yêu cầu SRS (FR/NFR), Kiến trúc & DB Schema
│   ├── 03_construction/          # Nhật ký phát triển & Test cases
│   └── 04_transition/            # Báo cáo tổng kết, hướng dẫn & tài liệu bàn giao
├── skills/                       # Chuẩn phương pháp luận UP (Ian Sommerville)
└── repo/                         # MÃ NGUỒN PHÁT TRIỂN (MVC / 3-Tier)
    └── FlexiDiet/
        ├── public/               # Web Root (Apache/XAMPP)
        │   ├── index.html        # Landing page & Onboarding Multi-step
        │   ├── app.html          # Web App Workspace
        │   └── assets/           # CSS & JS đã phân loại
        ├── backend/              # Core PHP Backend (PDO, Controllers, Models, APIs)
        │   ├── config/           # Database.php (PDO Singleton)
        │   ├── controllers/      # Business Controllers
        │   ├── models/           # Data Models
        │   ├── services/         # EnergyEngine, AIServiceCaller
        │   └── api/              # RESTful API Endpoints
        ├── database/             # Quản lý Database MySQL
        │   ├── schema.sql        # CSDL MySQL (Snapshot + 2 Tầng)
        │   └── seeds/            # Script nạp 30-50 món chuẩn & nguyên liệu (D25)
        └── ai_service/           # Microservice AI Python (FastAPI + ONNX)
            ├── app.py            # FastAPI Inference Server
            └── requirements.txt  # Thư viện Python
```

---

## 📅 Lộ Trình 4 Pha (02/10 – 25/10/2026)

| Pha (Phase) | Thời gian | Trọng tâm chính | Trạng thái |
| :--- | :--- | :--- | :---: |
| **1. Inception** | 02/10 – 04/10 | Business Case, Lean Canvas, 27 Quyết định, Risk Assessment. | **Hoàn tất 100% (v1.1)** ✅ |
| **2. Elaboration** | 05/10 – 11/10 | SRS (FR/NFR), Use Case chi tiết, ERD, PoC dịch vụ AI (Mốc 07/10 & 11/10). | **Đang thực hiện** 🎯 |
| **3. Construction** | 12/10 – 21/10 | Lập trình PHP/MySQL: Auth, Energy Engine, AI Food Log, Workout. | Chuẩn bị |
| **4. Transition** | 22/10 – 25/10 | Đóng băng tính năng, kiểm thử hệ thống, viết báo cáo & nộp bài (25/10). | Chuẩn bị |

---

> 📖 **Xem thêm tài liệu chi tiết:**
> * [Đề cương chi tiết môn học](docs/00_concept/outline.md)
> * [Tài liệu Pha 1: Business Modeling & Iteration Plan (v1.1)](docs/01_inception/01_business_modeling.md)
> * [Hướng dẫn phương pháp luận Unified Process](skills/unified-process-se/SKILL.md)

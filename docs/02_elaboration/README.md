# Pha 2: Elaboration (Mô tả chi tiết & Thiết kế Kiến trúc)

> **Giai đoạn:** 05/10/2026 – 11/10/2026  
> **Mục tiêu:** Xây dựng khung kiến trúc nền tảng (Architectural Baseline), đặc tả chi tiết 80%+ Use Cases (SRS), thiết kế CSDL (ERD/Schema), và PoC dịch vụ AI Microservice.

---

## 🎯 Mục Tiêu & Trọng Tâm Pha Elaboration

Theo chuẩn **Unified Process (Ian Sommerville, Chapter 2.4)**, pha Elaboration giải quyết các rủi ro kỹ thuật trọng yếu (*Architecturally Significant Risks*) và hoàn thiện đặc tả trước khi bước vào xây dựng quy mô lớn (*Construction*).

1. **Requirements Workflow (Kỹ nghệ Yêu cầu):**
   * Hoàn thiện đặc tả yêu cầu phần mềm **SRS** (Functional & Non-Functional Requirements).
   * Viết chi tiết kịch bản Use Case (Main flow, Alternate flows, Exception flows, Pre/Post-conditions).
2. **Analysis & Design Workflow (Phân tích & Thiết kế):**
   * Thiết kế kiến trúc tổng thể hệ thống (MVC / 3-Tier + Python AI Microservice).
   * Lược đồ dữ liệu CSDL quan hệ (ERD, Relational Schema với cơ chế Snapshot).
   * Lược đồ tương tác (Sequence Diagrams & Component Diagrams).
3. **Implementation & Testing (Thực nghiệm nền tảng / PoC):**
   * Xây dựng khung mã nguồn nền tảng (*Architectural Prototype / Skeleton*).
   * PoC thành công pipeline gọi AI Vision Model (FastAPI + ONNX / Gemini Vision) cho ẩm thực Việt Nam.

---

## 📁 Danh Mục Tài Liệu Cần Có Trong Thư Mục Này

| Tên tài liệu | Nội dung chính | Trạng thái |
| :--- | :--- | :---: |
| `02_srs_requirements_v1.1.md` | Đặc tả yêu cầu chức năng (FR-01 đến FR-06) & phi chức năng (NFR) | Hoàn thành |
| `02_usecase_specifications_v1.1.md` | Kịch bản chi tiết Use Cases & Sequence Diagrams | Hoàn thành |
| `02_architecture_design.md` | SAD (Software Architecture Document): Cấu trúc 3-Tier, REST API specs | Đang thực hiện |
| `02_database_design.md` | Lược đồ CSDL quan hệ ERD, Data Dictionary, Chiến lược Snapshot nhật ký | Chuẩn bị |
| `02_ai_service_poc.md` | Tài liệu nghiên cứu & PoC AI Model nhận diện món ăn Việt | Chuẩn bị |

---

## 📌 Các Mốc Quan Trọng (Milestones)

* **Mốc 1 (07/10/2026):** Hoàn tất toàn bộ SRS & sơ đồ thiết kế CSDL (Schema & ERD v1.0).
* **Mốc 2 (11/10/2026):** Hoàn tất PoC kết nối Frontend ↔ Backend PHP ↔ Python AI Service; sẵn sàng đóng gói Baseline cho Pha Construction.

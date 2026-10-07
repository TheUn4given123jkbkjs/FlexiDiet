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
   * PoC thành công pipeline gọi AI Vision Model (FastAPI + ONNX) cho ẩm thực Việt Nam.

---

## 📁 Danh Mục Tài Liệu Cần Có Trong Thư Mục Này

| Tên tài liệu | Nội dung chính | Trạng thái |
| :--- | :--- | :---: |
| `02_srs_requirements_v1.1.md` | Đặc tả yêu cầu chức năng (FR-01 đến FR-07) & phi chức năng (NFR) | Hoàn thành |
| `02_usecase_specifications_v1.1.md` | Kịch bản chi tiết Use Cases & Sequence Diagrams | Hoàn thành |
| `02_architecture_design.md` | SAD (Software Architecture Document): Cấu trúc 3-Tier, REST API specs | Hoàn thành (chờ bổ sung kết quả PoC) |
| `02_database_design.md` | Lược đồ CSDL quan hệ ERD, Data Dictionary, Chiến lược Snapshot nhật ký | Hoàn thành |
| `02_ai_service_poc.md` | Thiết kế kiến trúc & Kế hoạch đo kiểm dịch vụ AI | Thiết kế hoàn thành; đo kiểm thực tế ở C1/C2 |

---

## 📌 Các Mốc Quan Trọng (Milestones)

* **Mốc 1 (07/10/2026):** Hoàn tất toàn bộ SRS & sơ đồ thiết kế CSDL (Schema & ERD v1.0). Đã chốt chỉ số kỹ thuật NFR-02, NFR-04 và tích hợp mô hình YOLOv10m ONNX cho 67 món Việt.
* **Mốc 2 (11/10/2026):** Executable Baseline: `schema.sql` chạy sạch trên MySQL thật, nạp seed dữ liệu Viện Dinh Dưỡng, AI Microservice (FastAPI + ONNX) chạy thực tế và kết nối thông suốt với PHP Backend, sẵn sàng đóng gói Baseline cho Pha Construction.

> **Ghi chú:** Đã tích hợp sẵn mô hình YOLOv10m ONNX thực tế ($mAP_{50} = 0.92$) cho 67 món ăn Việt Nam vào `ai_service/models/`. Tính năng AI được đưa trực tiếp vào pipeline sản phẩm; trọng tâm mốc 11/10 là kiểm chứng luồng ánh xạ end-to-end từ nhận diện ảnh $\rightarrow$ truy vấn công thức CSDL $\rightarrow$ tính Calo/Macro hiển thị trên Web.

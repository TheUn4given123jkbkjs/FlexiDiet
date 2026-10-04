# Pha 3: Construction (Hiện thực hóa & Kiểm thử Thành phần)

> **Giai đoạn:** 12/10/2026 – 21/10/2026  
> **Mục tiêu:** Lập trình hoàn chỉnh toàn bộ 6 module cốt lõi, tích hợp CSDL MySQL, hoàn thiện REST API PHP thuần PDO và dịch vụ AI, song song kiểm thử tích hợp (Integration & Unit Testing).

---

## 🎯 Mục Tiêu & Trọng Tâm Pha Construction

Theo chuẩn **Unified Process (Ian Sommerville, Chapter 2.4)**, pha Construction chuyển đổi toàn bộ thiết kế kiến trúc từ Elaboration thành mã nguồn thực tế, chia thành các Iteration ngắn (Lặp và tăng dần).

1. **Implementation Workflow (Hiện thực hóa Mã Nguồn):**
   * **Iteration C1 (12/10 – 15/10):**
     * Module 1: Auth & User Profile (BMR / TDEE Calculation).
     * Module 2: Dynamic Calorie Budget Engine.
     * Database Migrations & Seeds dữ liệu món ăn mẫu (30–50 món Việt).
   * **Iteration C2 (16/10 – 19/10):**
     * Module 3: AI Food Logging & Macro Fine-tuning.
     * Module 4: Workout Tracker & METs Energy Burn.
   * **Iteration C3 (20/10 – 21/10):**
     * Module 5: Smart Meal Suggestions.
     * Module 6: Dashboard, Kanban bữa ăn & Analytics Progress Charts.
2. **Testing Workflow (Kiểm thử):**
   * Kiểm thử tính toán công thức năng lượng và Snapshot nhật ký.
   * Kiểm thử tích hợp REST API (Postman Collections & Automated Test Scripts).
   * Kiểm thử độ trễ và độ chính xác của AI Food Logging Microservice.

---

## 📁 Danh Mục Tài Liệu Cần Có Trong Thư Mục Này

| Tên tài liệu | Nội dung chính | Trạng thái |
| :--- | :--- | :---: |
| `03_iteration_plan.md` | Kế hoạch chi tiết theo từng Iteration (C1, C2, C3) và phân công nhóm | Chuẩn bị |
| `03_api_implementation.md` | Tài liệu hướng dẫn sử dụng và kiểm thử toàn bộ REST API Endpoints | Chuẩn bị |
| `03_test_cases.md` | Ma trận kiểm thử (Test Matrix), Unit Tests & Integration Test Cases | Chuẩn bị |
| `03_dev_log.md` | Nhật ký ghi nhận tiến độ phát triển, bug fixes và technical decisions | Chuẩn bị |

---
name: unified-process-se
description: Methodology and framework for Software Engineering following the Unified Process (UP/RUP) based on Ian Sommerville's Software Engineering (9th Edition). Covers the 2D matrix of 4 Dynamic Phases (Inception, Elaboration, Construction, Transition) and 6 Core Engineering Workflows + 3 Supporting Workflows.
---

# Hướng Dẫn Phương Pháp Luận Unified Process (UP / RUP)
*Dựa trên giáo trình Software Engineering (9th Edition) - Tác giả: Ian Sommerville (Chapter 2.4)*

---

## 1. Cấu Trúc Tổng Quan Của Unified Process (UP)

Unified Process (UP / RUP) là một mô hình tiến trình phát triển phần mềm hiện đại, lặp và tăng dần (*Iterative & Incremental*), lấy trường hợp sử dụng làm trung tâm (*Use-Case Driven*) và lấy kiến trúc làm trọng tâm (*Architecture-Centric*).

Tiến trình được phân tích qua **hai chiều không gian độc lập**:
* **Trục hoành (Chiều động - Dynamic Perspective):** Thể hiện chu kỳ sống của dự án theo thời gian, chia thành các **Pha (Phases)** và các **Lần lặp (Iterations)**.
* **Trục tung (Chiều tĩnh - Static Perspective):** Thể hiện các hoạt động kỹ thuật chuyên môn diễn ra trong dự án, gọi là các **Luồng công việc (Workflows)**.

```
Workflows (Chiều tĩnh)           INCEPTION   ELABORATION   CONSTRUCTION   TRANSITION
├── Business Modelling           ████████░░░░ ░░░░░░░░░░░░ ░░░░░░░░░░░░ ░░░░░░░░░░░░
├── Requirements                 ████████████ ████████░░░░ ░░░░░░░░░░░░ ░░░░░░░░░░░░
├── Analysis & Design            ░░░░████████ ████████████ ████░░░░░░░░ ░░░░░░░░░░░░
├── Implementation               ░░░░░░░░░░░░ ░░░░████████ ████████████ ░░░░░░░░░░░░
├── Testing                      ░░░░░░░░░░░░ ░░░░░░░░████ ████████████ ████████░░░░
└── Deployment                   ░░░░░░░░░░░░ ░░░░░░░░░░░░ ░░░░░░░░████ ████████████
                                 └───────────┴───────────┴───────────┴───────────┘
                                                  Trục thời gian (Phases)
```

---

## 2. Chiều Động (Dynamic Perspective) – 4 Pha (Phases)

Theo Sommerville (Hình 2.12), mọi dự án phát triển theo UP đều đi qua 4 pha chính:

### 1. Inception (Khởi tạo / Khởi đầu)
* **Mục tiêu:** Thiết lập cơ sở nghiệp vụ (*Business Case*), định hình tầm nhìn sản phẩm (*Vision*), xác định phạm vi hệ thống (*Scope*) và nhận diện tất cả các tác nhân bên ngoài (*Actors*) tương tác với hệ thống.
* **Đầu ra chính (Key Deliverables):**
  * Tài liệu Tầm nhìn & Mô hình Nghiệp vụ (*Business Model / Lean Canvas*).
  * Danh sách Actors và Mô hình Use Case sơ bộ (xác định ~10–20% Use Case cốt lõi nhất).
  * Đánh giá rủi ro sơ bộ (*Initial Risk Assessment*) và kế hoạch sơ khởi.

### 2. Elaboration (Mô tả chi tiết / Tinh chế)
* **Mục tiêu:** Nắm vững miền bài toán (*Problem Domain*), xây dựng **Khung kiến trúc nền tảng (Architectural Baseline)**, xử lý các rủi ro kỹ thuật trọng yếu (*Architecturally Significant Risks*), và đặc tả chi tiết hầu hết các Use Case (~80%).
* **Đầu ra chính (Key Deliverables):**
  * Mô hình yêu cầu chi tiết (Use Case Descriptions đầy đủ, Sequence Diagrams cho luồng chính).
  * Mô hình miền dữ liệu (Domain Class Model / ERD).
  * Tài liệu mô tả kiến trúc phần mềm (*Software Architecture Document - SAD*).
  * Bản dựng kiến trúc khung chạy thử nghiệm (*Architectural Prototype/Executable Baseline*).

### 3. Construction (Xây dựng / Hiện thực hóa)
* **Mục tiêu:** Thiết kế chi tiết, lập trình toàn bộ các module và kiểm thử tích hợp song song các phân hệ. Chuyển hóa toàn bộ thiết kế thành mã nguồn hoàn chỉnh.
* **Đầu ra chính (Key Deliverables):**
  * Phần mềm hoàn chỉnh hoạt động ổn định (*Working Software System*).
  * Bộ tài liệu hướng dẫn sử dụng và tài liệu vận hành kỹ thuật.
  * Bộ kịch bản kiểm thử (*Test Cases & Test Reports*).

### 4. Transition (Chuyển giao)
* **Mục tiêu:** Đưa hệ thống từ môi trường phát triển sang môi trường vận hành thực tế của người dùng (*Operational Environment*), chạy thử nghiệm (Beta test), khắc phục lỗi phát sinh, huấn luyện người dùng và bàn giao.
* **Đầu ra chính (Key Deliverables):**
  * Bản phát hành chính thức (*Production Release / Release Candidate*).
  * Báo cáo đánh giá chất lượng cuối cùng và kế hoạch bảo trì/tiến hóa (*Software Evolution*).

---

## 3. Chiều Tĩnh (Static Perspective) – Các Luồng Công Việc Kỹ Thuật (Workflows)

Theo Sommerville (Hình 2.13), có 6 luồng kỹ nghệ cốt lõi (*Core Engineering Workflows*) và 3 luồng hỗ trợ (*Supporting Workflows*):

### 3.1. 6 Luồng Kỹ Nghệ Cốt Lõi (Core Engineering Workflows)
1. **Business Modelling (Mô hình hóa nghiệp vụ):**
   * Phân tích bài toán thực tế, nỗi đau khách hàng, đối thủ cạnh tranh.
   * Xây dựng mô hình Business Use Cases và Lean Canvas để làm rõ giá trị cốt lõi.
2. **Requirements (Kỹ nghệ yêu cầu):**
   * Nhận diện Actors, phân loại Yêu cầu Chức năng (FR - Functional Requirements) và Yêu cầu Phi chức năng (NFR - Non-Functional Requirements).
   * Viết đặc tả Use Case chi tiết (Tiền điều kiện, Luồng chính, Luồng phụ/ngoại lệ, Hậu điều kiện).
3. **Analysis & Design (Phân tích & Thiết kế):**
   * Tạo mô hình kiến trúc (*Architectural Models* - MVC, 3-Tier, Microservices).
   * Tạo mô hình tương tác (*Interaction Models* - Sequence Diagrams).
   * Thiết kế cơ sở dữ liệu và mô hình lớp (*Class Diagrams, Component Models, ERD*).
4. **Implementation (Hiện thực hóa / Cài đặt):**
   * Viết mã nguồn theo các thành phần kiến trúc đã định nghĩa.
   * Xây dựng các API, giao diện người dùng (UI) và dịch vụ xử lý nghiệp vụ.
5. **Testing (Kiểm thử):**
   * Thực hiện kiểm thử theo từng lần lặp (Unit Testing, Integration Testing, System Testing, Acceptance Testing).
   * Xác minh độ tin cậy, bảo mật và hiệu năng.
6. **Deployment (Triển khai):**
   * Đóng gói phần mềm, cấu hình server/database/cloud và bàn giao phiên bản phát hành cho người dùng.

### 3.2. 3 Luồng Hỗ Trợ (Supporting Workflows)
1. **Configuration & Change Management (Quản lý cấu hình & thay đổi):** Quản lý mã nguồn (Git), phiên bản và kiểm soát sự thay đổi yêu cầu.
2. **Project Management (Quản lý dự án):** Lập kế hoạch theo từng Iteration, theo dõi tiến độ, quản lý rủi ro và phân bổ nguồn lực.
3. **Environment (Môi trường phát triển):** Cung cấp công cụ lập trình, IDE, CI/CD và môi trường kiểm thử cho nhóm phát triển.

---

## 4. 6 Thực Tiễn Tốt Nhất Trong UP (Best Practices)
1. **Phát triển phần mềm theo mô hình lặp (Develop software iteratively).**
2. **Quản lý yêu cầu chặt chẽ (Manage requirements).**
3. **Sử dụng kiến trúc dựa trên thành phần (Use component-based architectures).**
4. **Mô hình hóa trực quan bằng UML (Visually model software).**
5. **Liên tục kiểm chứng chất lượng phần mềm (Verify software quality).**
6. **Kiểm soát và quản lý thay đổi (Control changes to software).**

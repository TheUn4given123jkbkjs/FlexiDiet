# FlexiDiet: Bảng Tra Cứu & Công Thức Quy Đổi Dinh Dưỡng

> **Quy ước:** Dữ liệu quy đổi lưu với `source = 'vdd'`, mã tham chiếu hậu tố `_convert`.
> Đơn vị dinh dưỡng viết tắt: **(Kcal / P / F / C)** tương ứng với *(Kcal / Protein / Fat / Carb)* trên 100g.

| Tên nguyên liệu (`name_norm`) | Mã tham chiếu (`source_ref`) | Chỉ số gốc VDD (Kcal / P / F / C) | Công thức quy đổi | Chỉ số sau quy đổi (Kcal / P / F / C) | Trạng thái |
|---|---|---|---|---|:---:|
| `com trang (te may)` | `VDD_1004_convert` | 1004: (346.0 / 7.9 / 1.0 / 76.3) | Gốc $\div K (2.45)$ | **141.22 / 3.22 / 0.41 / 31.14** | `cooked` |
| `chao trang (te may)` | `VDD_1004_chao_convert` | 1004: (346.0 / 7.9 / 1.0 / 76.3) | Gốc $\div K (8.50)$ (nấu cháo nhừ nở nước, độ ẩm $\approx 88.2\%$) | **40.71 / 0.93 / 0.12 / 8.98** | `cooked` |
| `com trang (te gia tay)` | `VDD_1003_convert` | 1003: (347.0 / 8.1 / 1.3 / 75.7) | Gốc $\div K (2.45)$ | **141.63 / 3.31 / 0.53 / 30.90** | `cooked` |
| `com gao lut` | `VDD_1005_convert` | 1005: (359.0 / 7.5 / 2.6 / 76.1) | Gốc $\div K (2.40)$ | **149.58 / 3.13 / 1.08 / 31.71** | `cooked` |
| `xoi nep cai hoa vang` | `VDD_1001_convert` | 1001: (348.0 / 8.6 / 1.5 / 75.1) | Gốc $\div K (2.00)$ | **174.00 / 4.30 / 0.75 / 37.55** | `cooked` |
| `xoi nep thuong` | `VDD_1002_convert` | 1002: (350.0 / 8.4 / 1.6 / 75.4) | Gốc $\div K (2.00)$ | **175.00 / 4.20 / 0.80 / 37.70** | `cooked` |
| `banh trang (kho)` | `VDD_1017_13046_convert` | 1017: (360.0 / 6.6 / 0.4 / 82.6)<br>13046: (343.0 / 0.7 / 0.0 / 85.1) | $(85\% \times 1017 + 15\% \times 13046) \times K_{\text{moisture}} (0.82)$ (độ ẩm thương phẩm $\sim 26\%$) | **293.11 / 4.69 / 0.28 / 68.04** | `cooked` |
| `thit heo quay` | `VDD_7018_convert` | 7018: (260.0 / 16.5 / 21.5 / 0.0) | Gốc $\times 1.38$ + $1.5\text{g}$ Carb gia vị | **358.80 / 22.77 / 29.67 / 1.50** | `cooked` |
| `ca qua (luoc / hap)` | `VDD_8022_luoc_convert` | 8022: (97.0 / 18.2 / 2.7 / 0.0) | Gốc $\times 1.20$ (co cơ luộc/hấp) | **116.40 / 21.84 / 3.24 / 0.00** | `cooked` |
| `ca qua (chien gion)` | `VDD_8022_chien_convert` | 8022: (97.0 / 18.2 / 2.7 / 0.0) | Gốc $\times 1.25$ + $3.0\text{g}$ dầu rán | **148.42 / 22.75 / 6.38 / 0.00** | `cooked` |
| `ca hoi (nuong / ap chao)` | `VDD_8011_nuong_convert` | 8011: (136.0 / 22.0 / 5.3 / 0.0) | Gốc $\times 1.25$ + $1.0\text{g}$ bơ/dầu áp chảo | **178.63 / 27.50 / 7.63 / 0.00** | `cooked` |
| `ca hoi (hap)` | `VDD_8011_hap_convert` | 8011: (136.0 / 22.0 / 5.3 / 0.0) | Gốc $\times 1.20$ (co cơ hấp) | **163.20 / 26.40 / 6.36 / 0.00** | `cooked` |
| `ca dieu hong (hap)` | `VDD_8062_hap_convert` | 8062: (111.0 / 17.1 / 3.3 / 3.1) | Gốc $\times 1.20$ (co cơ hấp) | **133.20 / 20.52 / 3.96 / 3.72** | `cooked` |
| `ca dieu hong (chien gion)` | `VDD_8062_chien_convert` | 8062: (111.0 / 17.1 / 3.3 / 3.1) | Gốc $\times 1.25$ + $3.0\text{g}$ dầu rán | **165.13 / 21.38 / 7.13 / 3.88** | `cooked` |
| `ca chep (hap)` | `VDD_8003_hap_convert` | 8003: (96.0 / 16.0 / 3.6 / 0.0) | Gốc $\times 1.20$ (co cơ hấp) | **115.20 / 19.20 / 4.32 / 0.00** | `cooked` |
| `ca chep (ran)` | `VDD_8003_ran_convert` | 8003: (96.0 / 16.0 / 3.6 / 0.0) | Gốc $\times 1.25$ + $3.0\text{g}$ dầu rán | **147.50 / 20.00 / 7.50 / 0.00** | `cooked` |
| `ca thu (ran)` | `VDD_8026_ran_convert` | 8026: (166.0 / 18.2 / 10.3 / 0.0) | Gốc $\times 1.25$ + $2.5\text{g}$ dầu rán | **229.38 / 22.75 / 15.38 / 0.00** | `cooked` |
| `ca thu (nuong)` | `VDD_8026_nuong_convert` | 8026: (166.0 / 18.2 / 10.3 / 0.0) | Gốc $\times 1.25$ (co cơ nướng) | **207.50 / 22.75 / 12.88 / 0.00** | `cooked` |
| `ca basa (kho / hap)` | `VDD_8063_hap_convert` | 8063: (67.0 / 9.7 / 0.8 / 5.3) | Gốc $\times 1.20$ (co cơ kho/hấp) | **80.40 / 11.64 / 0.96 / 6.36** | `cooked` |
| `ca basa (chien gion)` | `VDD_8063_chien_convert` | 8063: (67.0 / 9.7 / 0.8 / 5.3) | Gốc $\times 1.25$ + $3.0\text{g}$ dầu rán | **111.00 / 12.13 / 4.00 / 6.63** | `cooked` |
| `ca tre (nuong)` | `VDD_8030_nuong_convert` | 8030: (173.0 / 16.5 / 11.9 / 0.0) | Gốc $\times 1.25$ (co cơ nướng) | **216.25 / 20.63 / 14.88 / 0.00** | `cooked` |
| `ca tre (ran)` | `VDD_8030_ran_convert` | 8030: (173.0 / 16.5 / 11.9 / 0.0) | Gốc $\times 1.25$ + $2.5\text{g}$ dầu rán | **238.88 / 20.63 / 17.38 / 0.00** | `cooked` |
| `tom bien (chien gion)` | `VDD_8051_chien_convert` | 8051: (82.0 / 17.6 / 0.9 / 0.9) | Gốc $\times 1.25$ + $4.0\text{g}$ dầu rán | **138.69 / 22.00 / 5.13 / 1.13** | `cooked` |
| `tom dong (chien gion)` | `VDD_8052_chien_convert` | 8052: (90.0 / 18.4 / 1.8 / 0.0) | Gốc $\times 1.25$ + $4.0\text{g}$ dầu rán | **148.25 / 23.00 / 6.25 / 0.00** | `cooked` |
| `muc tuoi (luoc)` | `VDD_8040_luoc_convert` | 8040: (73.0 / 16.3 / 0.9 / 0.0) | Tương đương hấp (VDD 8040003) | **115.00 / 25.40 / 1.40 / 0.00** | `cooked` |
| `muc tuoi (chien gion)` | `VDD_8040_chien_convert` | 8040: (73.0 / 16.3 / 0.9 / 0.0) | Đạm hấp chín + $4.5\text{g}$ dầu rán | **154.70 / 25.40 / 5.90 / 0.00** | `cooked` |
| `cua be (luoc / hap)` | `VDD_8033_luoc_convert` | 8033: (103.0 / 17.5 / 0.6 / 7.0) | Gốc $\times 1.20$ (co cơ luộc/hấp) | **123.60 / 21.00 / 0.72 / 8.40** | `cooked` |
| `cua dong (luoc / hap)` | `VDD_8034_luoc_convert` | 8034: (87.0 / 12.3 / 3.3 / 2.0) | Gốc $\times 1.20$ (riêu cua luộc/hấp) | **104.40 / 14.76 / 3.96 / 2.40** | `cooked` |
| `cua ghe (luoc / hap)` | `VDD_8035_luoc_convert` | 8035: (54.0 / 11.9 / 0.7 / 0.0) | Gốc $\times 1.20$ (ghẹ hấp/luộc) | **64.80 / 14.28 / 0.84 / 0.00** | `cooked` |
| `oc buou (nuong)` | `VDD_8041_nuong_convert` | 8041002: (336.0 / 44.4 / 2.8 / 33.2) | Co cơ $\times 1.05$ + $2.0\text{g}$ mỡ hành/tiêu | **370.38 / 46.62 / 4.94 / 34.86** | `cooked` |
| `oc nhoi (nuong)` | `VDD_8043_nuong_convert` | 8043003: (337.0 / 47.6 / 2.8 / 30.4) | Co cơ $\times 1.05$ + $2.0\text{g}$ mỡ hành/tiêu | **372.06 / 49.98 / 4.94 / 31.92** | `cooked` |
| `oc huong (nuong)` | `VDD_8075_nuong_convert` | 8075003: (94.0 / 22.4 / 2.7 / 2.4) | Co cơ $\times 1.15$ + $1.0\text{g}$ dầu/muối ớt | **151.07 / 25.76 / 4.11 / 2.76** | `cooked` |
| `oc mong tay (nuong)` | `VDD_8074_nuong_convert` | 8074002: (123.0 / 15.8 / 5.6 / 2.0) | Co cơ $\times 1.15$ + $3.0\text{g}$ mỡ hành | **166.84 / 18.17 / 9.44 / 2.30** | `cooked` |
| `trung ga ta (luoc)` | `VDD_9001_luoc_convert` | 9001: (150.0 / 14.8 / 11.6 / 0.5) | Giữ vỏ, thoát vi hơi nước $\times 1.025$ | **153.75 / 15.17 / 11.89 / 0.51** | `cooked` |
| `trung ga cong nghiep (luoc)` | `VDD_9012_luoc_convert` | 9012: (132.0 / 13.0 / 9.8 / 0.6) | Giữ vỏ, thoát vi hơi nước $\times 1.025$ | **135.30 / 13.33 / 10.05 / 0.62** | `cooked` |
| `trung vit (luoc)` | `VDD_9004_luoc_convert` | 9004: (172.0 / 13.0 / 12.0 / 2.9) | Giữ vỏ, thoát vi hơi nước $\times 1.025$ | **176.30 / 13.33 / 12.30 / 2.97** | `cooked` |
| `trung chim cut (luoc)` | `VDD_9007_luoc_convert` | 9007: (157.0 / 12.7 / 10.4 / 2.8) | Giữ vỏ, thoát vi hơi nước $\times 1.025$ | **160.93 / 13.02 / 10.66 / 2.87** | `cooked` |
| `trung ga (chien / ran)` | `VDD_9001_ran_convert` | 9001: (150.0 / 14.8 / 11.6 / 0.5) | Bay hơi nước $\times 1.10$ + $5.5\text{g}$ dầu rán | **231.66 / 16.28 / 18.26 / 0.55** | `cooked` |
| `trung vit (chien / ran)` | `VDD_9004_ran_convert` | 9004: (172.0 / 13.0 / 12.0 / 2.9) | Bay hơi nước $\times 1.10$ + $5.5\text{g}$ dầu rán | **238.26 / 14.30 / 18.70 / 3.19** | `cooked` |
| `trung chim cut (chien / ran)` | `VDD_9007_ran_convert` | 9007: (157.0 / 12.7 / 10.4 / 2.8) | Bay hơi nước $\times 1.10$ + $5.5\text{g}$ dầu rán | **220.66 / 13.97 / 16.94 / 3.08** | `cooked` |
| `nuoc dung bo (nuoc ham xuong)` | `MANUAL_BROTH_BO` | Nước hầm xương bò thanh | Hầm xương/tủy bò + gia vị thảo mộc | **16.00 / 1.30 / 1.10 / 0.20** | `cooked` |
| `nuoc dung ga (nuoc ham xuong)` | `MANUAL_BROTH_GA` | Nước luộc/hầm xương gà | Hầm xương gà + gừng hành nướng | **15.00 / 1.40 / 0.95 / 0.20** | `cooked` |
| `nuoc dung heo (nuoc ham xuong)` | `MANUAL_BROTH_HEO` | Nước hầm xương ống heo | Hầm xương heo lấy nước dùng bún/bánh canh | **18.00 / 1.20 / 1.40 / 0.15** | `cooked` |
| `soi banh canh (tuoi)` | `MANUAL_1021_BANH_CANH` | Bột gạo tẻ pha bột năng | Ép sợi luộc chín (độ ẩm ~68%) | **110.00 / 1.80 / 0.40 / 24.80** | `cooked` |
| `dau xanh (hap / luoc)` | `VDD_3010_hap_convert` | 3010: (346.0 / 23.4 / 2.4 / 57.8) | Hạt khô ngâm hấp nở $\div K (2.35)$ | **147.23 / 9.96 / 1.02 / 24.60** | `cooked` |
| `hanh phi (hanh cu chien gion)` | `VDD_4037_phi_convert` | 4037: (29.0 / 1.3 / 0.2 / 5.2) | Chiên ngập dầu giòn: bốc hơi nước + ngấm $35\text{g}$ dầu | **440.00 / 3.50 / 35.00 / 28.00** | `cooked` |
| `bot gao chien gion` | `VDD_1017_chien_convert` | 1017: (360.0 / 6.6 / 0.4 / 82.6) | Pha nước đổ khuôn/chảo: tinh bột chín + ngấm $9\text{g}$ dầu | **205.00 / 3.20 / 9.20 / 27.50** | `cooked` |
| `sot bo trung (bo banh mi)` | `MANUAL_SOT_BO_TRUNG` | Lòng đỏ trứng + dầu thực vật | Đánh nhũ tương bơ trứng kiểu Việt: 68% lipid, 25% lòng đỏ | **635.00 / 2.60 / 68.00 / 2.50** | `cooked` |
















# Danh sách Công thức & Dinh dưỡng 18 Món ăn còn lại (VietFood-67)

> **Tài liệu đặc tả công thức khẩu phần chuẩn (Standard Servings) cho 18 món ăn còn lại trong VietFood-67.**  
> Nguồn dữ liệu dinh dưỡng: **100% Viện Dinh Dưỡng Quốc Gia (VDD) & USDA**.  
> Tất cả các thành phần trùng lặp trong từng công thức đều đã được **gộp khối lượng (SUM)** để tuân thủ ràng buộc toàn vẹn cơ sở dữ liệu `UNIQUE(dish_id, ingredient_id)`.

---

## Bảng tổng hợp Dinh dưỡng 18 Món ăn

| STT | Mã món | Tên món ăn | Đơn vị khẩu phần | Khối lượng (g) | Năng lượng (Kcal) | Protein (g) | Fat (g) | Carb (g) |
| :---: | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| 1 | `goi_cuon` | Gỏi cuốn | 1 phần (3 cuốn) | 241.0g | **341.15** | 19.98g | 11.70g | 39.54g |
| 2 | `hamburger` | Hamburger bò | 1 cái | 225.0g | **439.85** | 41.94g | 10.70g | 43.94g |
| 3 | `hu_tieu` | Hủ tiếu Nam Vang | 1 tô | 550.0g | **417.16** | 32.86g | 12.12g | 43.89g |
| 4 | `kho_qua_thit` | Khổ qua dồi thịt | 1 bát | 205.5g | **170.59** | 14.51g | 6.37g | 13.64g |
| 5 | `lau` | Lẩu thập cẩm | 1 nồi cá nhân | 647.0g | **364.11** | 47.88g | 8.49g | 23.90g |
| 6 | `salad` | Salad rau củ quả | 1 đĩa | 211.0g | **189.24** | 6.01g | 11.61g | 15.96g |
| 7 | `thit_kho` | Thịt kho tàu trứng | 1 phần | 217.5g | **397.24** | 24.70g | 28.36g | 10.63g |
| 8 | `thit_nuong` | Thịt nướng sả mè | 1 đĩa | 150.5g | **254.85** | 25.66g | 13.73g | 7.19g |
| 9 | `banh_beo` | Bánh bèo tôm cháy | 1 đĩa (6 chén) | 161.0g | **416.58** | 16.21g | 8.10g | 69.73g |
| 10 | `cao_lau` | Cao lầu Hội An | 1 tô | 330.0g | **372.66** | 28.02g | 9.06g | 44.74g |
| 11 | `mi_quang` | Mì Quảng tôm thịt | 1 tô | 347.0g | **564.80** | 25.83g | 23.72g | 62.94g |
| 12 | `com_chien_duong_chau` | Cơm chiên Dương Châu | 1 đĩa | 323.5g | **520.97** | 22.55g | 20.43g | 61.60g |
| 13 | `bun_cha_ca` | Bún chả cá | 1 tô | 401.0g | **390.63** | 12.60g | 14.27g | 52.78g |
| 14 | `com_chien_ga` | Cơm chiên gà | 1 đĩa | 353.5g | **570.17** | 25.75g | 24.82g | 59.87g |
| 15 | `chao_long` | Cháo lòng | 1 tô | 366.5g | **438.86** | 31.57g | 10.80g | 53.58g |
| 16 | `nom_hoa_chuoi` | Nộm hoa chuối tai heo | 1 đĩa | 271.0g | **246.94** | 18.18g | 9.78g | 23.14g |
| 17 | `nui_xao_bo` | Nui xào bò | 1 đĩa | 307.5g | **364.18** | 27.94g | 9.31g | 42.07g |
| 18 | `sup_cua` | Súp cua gà xé | 1 bát | 163.5g | **299.81** | 27.37g | 10.67g | 23.46g |

---

## Chi tiết Định lượng & Thành phần từng món

### 1. Gỏi cuốn (`goi_cuon` - 1 phần 3 cuốn ~241.0g)
* **Bánh tráng khô (cuốn gỏi):** 20.0g (`VDD_1017_13046_convert`)
* **Bún tươi sợi nhỏ:** 50.0g (`VDD_1020`)
* **Tôm biển luộc bóc vỏ:** 45.0g (`VDD_8051002`)
* **Thịt chân giò / ba chỉ luộc:** 40.0g (`VDD_7032002`)
* **Hẹ lá tươi:** 10.0g (`VDD_4042`)
* **Rau thơm, xà lách:** 30.0g (`VDD_4094`)
* **Giá đậu xanh tươi:** 20.0g (`VDD_4036`)
* **Tương đen chấm gỏi cuốn:** 20.0g (`USDA_HOISIN`)
* **Đậu phộng rang rắc lên tương:** 5.0g (`USDA_174261`)
* **Ớt tươi băm:** 1.0g (`VDD_13039`)

### 2. Hamburger bò (`hamburger` - 1 cái ~225.0g)
* **Bánh mì burger bun:** 70.0g (`VDD_1012`)
* **Thịt bò nạc xay làm patty áp chảo:** 90.0g (`VDD_7005018`)
* **Phô mát (cheddar):** 20.0g (`VDD_10009`)
* **Quả cà chua tươi:** 20.0g (`VDD_4005`)
* **Xà lách tươi:** 15.0g (`VDD_4069`)
* **Sốt mayonnaise:** 5.0g (`VDD_13023`)
* **Tương cà (ketchup):** 5.0g (`USDA_2709733`)

### 3. Hủ tiếu Nam Vang (`hu_tieu` - 1 tô ~550.0g)
* **Sợi hủ tiếu (bún gạo chần):** 140.0g (`VDD_1020`)
* **Tôm biển luộc bóc vỏ:** 30.0g (`VDD_8051002`)
* **Thịt heo nạc vai băm xào tỏi:** 30.0g (`VDD_7083`)
* **Thịt nạc thăn heo luộc thái mỏng:** 30.0g (`VDD_7085002`)
* **Gan heo luộc thái lát:** 20.0g (`VDD_7041002`)
* **Trứng chim cút luộc (2 quả):** 20.0g (`VDD_9007_luoc_convert`)
* **Giá đậu xanh tươi:** 30.0g (`VDD_4036`)
* **Hẹ lá tươi:** 15.0g (`VDD_4042`)
* **Cần tây tươi:** 15.0g (`VDD_4018`)
* **Hành phi giòn:** 5.0g (`VDD_4037_phi_convert`)
* **Tỏi ta băm:** 4.0g (`VDD_4103`)
* **Nước dùng xương hầm:** 200.0g (`MANUAL_BROTH_HEO`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Chanh tươi:** 5.0g (`VDD_5003`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 4. Khổ qua dồi thịt (`kho_qua_thit` - 1 bát ~205.5g)
* **Mướp đắng (khổ qua) tươi:** 120.0g (`VDD_4055`)
* **Thịt heo nạc vai băm nhồi:** 60.0g (`VDD_7083`)
* **Mộc nhĩ khô thái sợi nhuyễn:** 5.0g (`VDD_4125`)
* **Miến dong khô cắt vụn:** 5.0g (`VDD_2015`)
* **Hành tím băm:** 3.0g (`VDD_4037`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Dầu thực vật:** 2.0g (`VDD_11001`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 5. Lẩu thập cẩm (`lau` - 1 phần ăn cá nhân ~647.0g)
* **Nước dùng xương hầm thanh ngọt:** 250.0g (`MANUAL_BROTH_HEO`)
* **Thịt bò bắp thái mỏng nhúng:** 60.0g (`VDD_7005018`)
* **Tôm biển tươi:** 40.0g (`VDD_8051`)
* **Mực tươi cắt miếng khía hoa:** 40.0g (`VDD_8040`)
* **Đậu phụ trắng tươi:** 50.0g (`VDD_3025`)
* **Nấm rơm tươi:** 30.0g (`VDD_4129`)
* **Nấm kim châm tươi:** 30.0g (`VDD_4133`)
* **Rau muống cọng:** 50.0g (`VDD_4083`)
* **Cải cúc (tần ô) tươi:** 40.0g (`VDD_4013`)
* **Bún tươi sợi nhúng lẩu:** 50.0g (`VDD_1020`)
* **Nước mắm cá gia giảm:** 5.0g (`VDD_13017`)
* **Ớt tươi cắt lát:** 2.0g (`VDD_13039`)

### 6. Salad rau củ quả (`salad` - 1 đĩa ~211.0g)
* **Xà lách tươi:** 70.0g (`VDD_4069`)
* **Quả cà chua tươi:** 40.0g (`VDD_4005`)
* **Dưa chuột tươi thái lát:** 40.0g (`VDD_4027`)
* **Ngô tươi (hạt bắp ngọt):** 20.0g (`VDD_1007`)
* **Trứng gà ta luộc thái lát:** 25.0g (`VDD_9001_luoc_convert`)
* **Dầu thực vật trộn giấm:** 8.0g (`VDD_11001`)
* **Giấm ăn:** 5.0g (`VDD_13034`)
* **Đường cát:** 3.0g (`VDD_12013`)

### 7. Thịt kho tàu trứng (`thit_kho` - 1 phần ~217.5g)
* **Thịt heo ba chỉ thái khối vuông:** 100.0g (`VDD_7018`)
* **Trứng vịt luộc bóc vỏ (1 quả):** 55.0g (`VDD_9004_luoc_convert`)
* **Nước dừa non tươi nấu kho:** 40.0g (`VDD_14006`)
* **Nước mắm cá kho đậm đà:** 10.0g (`VDD_13017`)
* **Đường cát thắng nước màu:** 6.0g (`VDD_12013`)
* **Hành củ tím băm:** 3.0g (`VDD_4037`)
* **Tỏi ta băm:** 2.0g (`VDD_4103`)
* **Ớt tươi:** 1.0g (`VDD_13039`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 8. Thịt nướng sả mè (`thit_nuong` - 1 đĩa ~150.5g)
* **Thịt heo nạc vai thái lát:** 120.0g (`VDD_7083`)
* **Sả tươi băm nhuyễn:** 10.0g (`LONGCHAU_SA_TUOI`)
* **Dầu thực vật ướp bóng mềm:** 5.0g (`VDD_11001`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Mật ong ướp vàng thơm:** 4.0g (`VDD_12015`)
* **Tỏi ta băm:** 3.0g (`VDD_4103`)
* **Hành củ tím băm:** 3.0g (`VDD_4037`)
* **Hạt tiêu đen xay:** 0.5g (`VDD_13004`)

### 9. Bánh bèo tôm cháy (`banh_beo` - 1 đĩa 6 chén ~161.0g)
* **Bột gạo tẻ pha hấp bánh:** 70.0g (`VDD_1017`)
* **Tôm biển tươi giã làm tôm cháy:** 35.0g (`VDD_8051`)
* **Thịt heo nạc vai băm xào nhân:** 20.0g (`VDD_7083`)
* **Bánh mì vụn sấy giòn (thay tóp mỡ):** 10.0g (`VDD_1012`)
* **Dầu thực vật làm mỡ hành:** 6.0g (`VDD_11001`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá pha chan bánh:** 8.0g (`VDD_13017`)
* **Đường cát pha nước mắm:** 6.0g (`VDD_12013`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 10. Cao lầu Hội An (`cao_lau` - 1 tô ~330.0g)
* **Sợi cao lầu (bún gạo sợi dày):** 140.0g (`VDD_1020`)
* **Thịt nạc thăn heo làm xá xíu:** 80.0g (`VDD_7085`)
* **Bì lợn chiên giòn (ram cao lầu):** 15.0g (`VDD_7031`)
* **Giá đậu xanh luộc sơ:** 40.0g (`VDD_4036002`)
* **Rau sống, rau thơm Trà Quế:** 35.0g (`VDD_4094`)
* **Xì dầu (nước tương) ướp xíu:** 8.0g (`VDD_13019`)
* **Dầu thực vật:** 5.0g (`VDD_11001`)
* **Tỏi ta băm:** 3.0g (`VDD_4103`)
* **Đường cát:** 3.0g (`VDD_12013`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 11. Mì Quảng tôm thịt (`mi_quang` - 1 tô ~347.0g)
* **Bánh phở / Mì Quảng tươi:** 150.0g (`VDD_1013`)
* **Tôm biển tươi rim:** 40.0g (`VDD_8051`)
* **Thịt heo ba chỉ rim:** 40.0g (`VDD_7018`)
* **Trứng chim cút luộc rim (2 quả):** 20.0g (`VDD_9007_luoc_convert`)
* **Bánh tráng nướng mè bẻ vụn:** 15.0g (`VDD_1017_13046_convert`)
* **Đậu phộng rang rắc mặt:** 10.0g (`USDA_174261`)
* **Hoa chuối tươi bào mỏng:** 30.0g (`VDD_4043`)
* **Rau thơm, xà lách:** 25.0g (`VDD_4094`)
* **Dầu thực vật rim nhưn:** 6.0g (`VDD_11001`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 12. Cơm chiên Dương Châu (`com_chien_duong_chau` - 1 đĩa ~323.5g)
* **Cơm trắng tẻ chiên:** 180.0g (`VDD_1004_convert`)
* **Trứng gà ta chiên cơm:** 30.0g (`VDD_9001`)
* **Dăm bông lợn thái hạt lựu:** 25.0g (`VDD_7066`)
* **Tôm biển luộc chín thái hạt lựu:** 30.0g (`VDD_8051002`)
* **Củ cà rốt tươi thái hạt lựu:** 20.0g (`VDD_4007`)
* **Hạt đậu Hà Lan:** 20.0g (`VDD_4031`)
* **Dầu thực vật chiên cơm:** 10.0g (`VDD_11001`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá nêm cơm:** 3.0g (`VDD_13017`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 13. Bún chả cá (`bun_cha_ca` - 1 tô ~401.0g)
* **Bún tươi:** 150.0g (`VDD_1020`)
* **Chả cá rán lát:** 60.0g (`VDD_8064008`)
* **Quả cà chua tươi:** 40.0g (`VDD_4005`)
* **Dứa ta tươi nấu nước dùng chua ngọt:** 30.0g (`VDD_5014`)
* **Quả bí ngô (bí đỏ) tươi:** 30.0g (`VDD_4003`)
* **Rau sống, xà lách, rau thơm:** 40.0g (`VDD_4094`)
* **Giá đậu xanh tươi:** 30.0g (`VDD_4036`)
* **Dầu thực vật xào cà chua:** 5.0g (`VDD_11001`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Chanh tươi:** 5.0g (`VDD_5003`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 14. Cơm chiên gà (`com_chien_ga` - 1 đĩa ~353.5g)
* **Cơm trắng tẻ chiên:** 180.0g (`VDD_1004_convert`)
* **Thịt chân đùi gà luộc xé sợi:** 80.0g (`VDD_7108002`)
* **Trứng gà ta chiên cùng cơm:** 25.0g (`VDD_9001`)
* **Dầu thực vật chiên cơm:** 10.0g (`VDD_11001`)
* **Củ cà rốt tươi thái hạt lựu:** 15.0g (`VDD_4007`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Dưa chuột tươi ăn kèm:** 20.0g (`VDD_4027`)
* **Cà chua tươi ăn kèm:** 15.0g (`VDD_4005`)
* **Nước mắm cá:** 3.0g (`VDD_13017`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 15. Cháo lòng (`chao_long` - 1 tô ~366.5g)
* **Cháo trắng nấu nhừ:** 160.0g (`VDD_1004_convert`)
* **Tiết lợn luộc:** 40.0g (`VDD_7059`)
* **Lòng non lợn luộc:** 35.0g (`VDD_7046`)
* **Dạ dày lợn luộc:** 25.0g (`VDD_7034002`)
* **Gan lợn luộc:** 25.0g (`VDD_7041002`)
* **Dồi lợn chín:** 25.0g (`VDD_7067`)
* **Tim lợn luộc:** 20.0g (`VDD_7057002`)
* **Giá đậu xanh tươi:** 20.0g (`VDD_4036`)
* **Hành lá tươi:** 10.0g (`VDD_4038`)
* **Nước mắm cá:** 5.0g (`VDD_13017`)
* **Ớt tươi:** 1.0g (`VDD_13039`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 16. Nộm hoa chuối tai heo (`nom_hoa_chuoi` - 1 đĩa ~271.0g)
* **Hoa chuối tươi bào mỏng:** 120.0g (`VDD_4043`)
* **Tai lợn luộc giòn sần sật:** 45.0g (`VDD_7054002`)
* **Củ cà rốt tươi bào sợi:** 25.0g (`VDD_4007`)
* **Giá đậu xanh tươi:** 25.0g (`VDD_4036`)
* **Đậu phộng rang giã dập:** 15.0g (`USDA_174261`)
* **Rau thơm, kinh giới, ngò rí:** 15.0g (`VDD_4094`)
* **Nước mắm cá trộn gỏi:** 10.0g (`VDD_13017`)
* **Đường cát trộn chua ngọt:** 8.0g (`VDD_12013`)
* **Chanh tươi vắt lấy nước cốt:** 5.0g (`VDD_5003`)
* **Tỏi ta băm:** 2.0g (`VDD_4103`)
* **Ớt tươi:** 1.0g (`VDD_13039`)

### 17. Nui xào bò (`nui_xao_bo` - 1 đĩa ~307.5g)
* **Nui / bún gạo luộc chín:** 130.0g (`VDD_1020`)
* **Thịt bò nạc xào mềm:** 70.0g (`VDD_7005018`)
* **Rau cải ngọt tươi xào kèm:** 40.0g (`VDD_4108`)
* **Quả cà chua tươi:** 30.0g (`VDD_4005`)
* **Hành tây tươi xào:** 20.0g (`VDD_4039`)
* **Dầu thực vật xào:** 8.0g (`VDD_11001`)
* **Xì dầu (nước tương):** 6.0g (`VDD_13019`)
* **Tỏi ta băm phi thơm:** 3.0g (`VDD_4103`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

### 18. Súp cua gà xé (`sup_cua` - 1 bát ~163.5g)
* **Thịt cua bể hấp gỡ thịt:** 40.0g (`VDD_8033_luoc_convert`)
* **Thịt gà ta (thịt nạc lườn luộc xé):** 30.0g (`VDD_7106002`)
* **Trứng gà ta đánh vân trứng:** 25.0g (`VDD_9001`)
* **Trứng chim cút luộc (2 quả):** 20.0g (`VDD_9007_luoc_convert`)
* **Ngô tươi (hạt bắp ngọt):** 20.0g (`VDD_1007`)
* **Bột gạo tẻ / bột năng tạo sánh:** 10.0g (`VDD_1017`)
* **Nấm hương khô thái sợi:** 5.0g (`VDD_4126`)
* **Hành lá tươi:** 5.0g (`VDD_4038`)
* **Nước mắm cá nêm súp:** 5.0g (`VDD_13017`)
* **Dầu thực vật phi:** 3.0g (`VDD_11001`)
* **Hạt tiêu đen:** 0.5g (`VDD_13004`)

-- =====================================================================
-- FlexiDiet Seed: Công thức nguyên liệu 67 món ăn hệ thống (dish_ingredients)
-- Định lượng chuẩn cho 1 khẩu phần (serving_label) theo Viện Dinh Dưỡng
-- =====================================================================

USE flexidiet;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Phở (dish_code: pho - 1 tô ~570g, chuẩn VDD 15066: 67 Kcal/100g ~ 382 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'pho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1013'), 150.0, 1),      -- Bánh phở (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'pho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 65.0, 2),    -- Thịt bò tái (65g)
  ((SELECT id FROM dishes WHERE dish_code = 'pho'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_BO'), 330.0, 3), -- Nước dùng bò (330g)
  ((SELECT id FROM dishes WHERE dish_code = 'pho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 15.0, 4),       -- Hành lá (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'pho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4039'), 10.0, 5);       -- Hành tây (10g)

-- ---------------------------------------------------------------------
-- 2. Bánh canh (dish_code: banh_canh - 1 tô giò heo ~610g ~ 428 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_1021_BANH_CANH'), 150.0, 1), -- Sợi bánh canh tươi (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7032002'), 80.0, 2),            -- Thịt chân giò heo luộc (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_HEO'), 320.0, 3),       -- Nước dùng hầm xương heo (320g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4021002'), 25.0, 4),            -- Củ cải trắng luộc (25g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007002'), 20.0, 5),            -- Cà rốt luộc (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 10.0, 6),               -- Hành lá (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037_phi_convert'), 5.0, 7);      -- Hành phi giòn (5g)

-- ---------------------------------------------------------------------
-- 3. Bánh chưng (dish_code: banh_chung - 1 cái truyền thống ~1005g, chuẩn VDD 15006 ~ 1798 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_chung'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1001_convert'), 580.0, 1),       -- Xôi nếp cái hoa vàng chín (580g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_chung'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3010_hap_convert'), 270.0, 2),  -- Đậu xanh hấp chín (270g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_chung'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018'), 150.0, 3),              -- Thịt ba chỉ heo nửa nạc nửa mỡ (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_chung'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 5.0, 4);                -- Hành tím + tiêu gia vị (5g)

-- ---------------------------------------------------------------------
-- 4. Bánh cuốn (dish_code: banh_cuon - 1 đĩa nhân thịt kèm chả ~310g ~ 390 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_15010'), 200.0, 1),              -- Bánh cuốn nóng nhân thịt mộc nhĩ (200g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_15041'), 40.0, 2),              -- Chả quế / Giò lụa ăn kèm (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036002'), 30.0, 3),            -- Giá đậu xanh luộc (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 25.0, 4),              -- Nước mắm cá pha chua ngọt (25g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 10.0, 5),               -- Rau thơm tươi (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037_phi_convert'), 5.0, 6);      -- Hành phi giòn rắc mặt (5g)

-- ---------------------------------------------------------------------
-- 5. Bánh khọt (dish_code: banh_khot - 1 đĩa 6-8 cái ~230g ~ 395 Kcal)
-- Phân rã nguyên liệu gốc: Bột gạo tẻ + Nước cốt dừa + Dầu rán + Tôm luộc
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017'), 50.0, 1),              -- Bột gạo tẻ (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_170172'), 10.0, 2),            -- Nước cốt dừa (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 10.0, 3),             -- Dầu thực vật chiên giòn (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 50.0, 4),           -- Tôm biển luộc chín (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8059'), 5.0, 5),                 -- Ruốc tôm / tôm chấy đỏ (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 10.0, 6),                -- Hành lá / mỡ hành thoa mặt (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 50.0, 7),                -- Rau thơm & xà lách ăn kèm (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 20.0, 8),                 -- Cà rốt ngâm đồ chua (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 25.0, 9);              -- Nước mắm cá pha chua ngọt (25g)

-- ---------------------------------------------------------------------
-- 6. Bánh mì (dish_code: banh_mi - 1 ổ tiêu chuẩn ~200g ~ 397 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1012'), 80.0, 1),                 -- Vỏ bánh mì không (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7063'), 20.0, 2),                 -- Ba tê lợn (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7069'), 40.0, 3),                 -- Giò lụa lợn / chả lụa (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_SOT_BO_TRUNG'), 10.0, 4),      -- Sốt bơ trứng gà / bơ bánh mì (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 20.0, 5),                 -- Dưa chuột / dưa leo thái lát (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 10.0, 6),                 -- Đồ chua: Cà rốt ngâm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4021'), 10.0, 7),                 -- Đồ chua: Củ cải trắng ngâm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 5.0, 8),                  -- Rau thơm / ngò rí (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13019'), 5.0, 9);                 -- Xì dầu / nước tương (5g)

-- ---------------------------------------------------------------------
-- 7. Bánh tráng (dish_code: banh_trang - Khẩu phần 100g ~ 293 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 8. Bánh tráng trộn (dish_code: banh_trang_tron - 1 phần mẻ chuẩn ~340g ~ 780 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 100.0, 1), -- Bánh tráng phơi sương (100g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7075'), 50.0, 2),                -- Thịt bò khô xé sợi (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9007_luoc_convert'), 40.0, 3),   -- Trứng chim cút luộc (40g ~ 4 quả)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_169910'), 50.0, 4),             -- Xoài xanh bào sợi (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4088'), 10.0, 5),                -- Rau răm tươi xắt nhuyễn (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_174261'), 20.0, 6),             -- Đậu phộng / lạc rang giã dập (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037_phi_convert'), 15.0, 7),   -- Hành phi giòn (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8059'), 15.0, 8),                -- Ruốc tôm / tép sấy khô (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13054'), 10.0, 9),               -- Sa tế tôm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13019'), 15.0, 10),              -- Xì dầu / nước tương (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 5.0, 11),               -- Đường cát (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_trang_tron'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 10.0, 12);              -- Nước cốt tắc / quất (10g)

-- ---------------------------------------------------------------------
-- 9. Bánh xèo (dish_code: banh_xeo - 1 cái cỡ vừa kèm rau ~310g ~ 495 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017'), 50.0, 1),              -- Bột gạo tẻ pha bột bánh xèo (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_170172'), 10.0, 2),            -- Nước cốt dừa tạo vị béo ngậy (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 8.0, 3),              -- Dầu thực vật tráng chảo chiên giòn viền (8g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 40.0, 4),           -- Tôm biển luộc chín (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018'), 40.0, 5),              -- Thịt ba rọi (nửa nạc nửa mỡ) xào nhân (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3010_hap_convert'), 20.0, 6),    -- Đậu xanh đã đãi vỏ hấp chín (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 40.0, 7),              -- Giá đậu xanh tươi chín tái (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4039'), 15.0, 8),              -- Hành tây thái mỏng (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 7.0, 9),               -- Hành lá tươi cắt nhỏ (7g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 60.0, 10),             -- Rau ăn kèm: xà lách, cải xanh, rau thơm (60g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_xeo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 20.0, 11);             -- Nước mắm cá pha chua ngọt chấm bánh (20g)

-- ---------------------------------------------------------------------
-- 10. Bò kho (dish_code: bo_kho - 1 tô cá nhân chuẩn ~416g ~ 359 Kcal)
-- Nồi 4 người chia 1 tô: Nạm bò + Gân bò + Nước hầm xương bò + Nước dừa + Sốt sánh
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7004'), 100.0, 1),               -- Thịt bò nạm (loại II - tươi) (100g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7043'), 40.0, 2),                -- Gân chân bò tươi hầm mềm (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 52.5, 3),                -- Cà rốt kho mềm (52.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_BO'), 100.0, 4),        -- Nước dùng bò / nước cốt hầm xương bò (100g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_14006'), 60.0, 5),               -- Nước dừa non tươi nấu sốt (60g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 3.5, 6),                -- Dầu thực vật / dầu màu điều (3.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_2709733'), 10.5, 7),             -- Sốt cà chua / tương cà (10.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 10.5, 8),        -- Sả cây đập dập thơm (10.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4039'), 17.5, 9),               -- Hành tây hầm mềm (17.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_169655'), 3.5, 10),             -- Đường phèn tạo vị thanh (3.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13046'), 3.5, 11),              -- Bột năng tạo độ sánh sệt (3.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 14.0, 12),              -- Rau thơm: húng quế, ngò gai ăn kèm (14g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13005'), 1.0, 13);              -- Muối ăn / gia vị nêm (1g)

-- ---------------------------------------------------------------------
-- 11. Bò lá lốt (dish_code: bo_la_lot - 1 phần ăn cuốn bánh tráng bún ~312.5g)
-- Mẻ 4 người chia 1 phần cá nhân (8-10 cuốn bò nướng kèm bánh tráng, bún, rau sống, mỡ hành, đậu phộng)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7003'), 100.0, 1),             -- Thịt bò băm nạc loại I (100g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7016'), 25.0, 2),              -- Thịt mỡ heo băm giữ độ mềm ẩm không khô (25g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4046'), 10.0, 3),              -- Lá lốt tươi cuốn chả (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 4.0, 4),               -- Hành tím băm (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 4.0, 5),               -- Tỏi ta băm (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 4.0, 6),        -- Sả tươi băm nhuyễn (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13027'), 4.0, 7),              -- Dầu hào ướp nhân (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 26.5, 8),             -- Nước mắm cá (1.5g ướp + 25g pha nước chấm) (26.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 1.5, 9),              -- Đường cát ướp nhân (1.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 10),             -- Hạt tiêu đen xay (0.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'FOODCALORIECALCULATOR_CHINESE_FIVE_SPICE'), 0.5, 11), -- Bột ngũ vị hương (0.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 50.0, 12),             -- Bún tươi ăn kèm (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 15.0, 13), -- Bánh tráng cuốn bánh (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 50.0, 14),             -- Rau sống: xà lách, rau thơm cuốn kèm (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_174261'), 10.0, 15),          -- Đậu phộng rang vàng giòn rắc lên (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 4.0, 16),             -- Dầu thực vật phi mỡ hành (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bo_la_lot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 4.0, 17);              -- Hành lá làm mỡ hành (4g)

-- ---------------------------------------------------------------------
-- 12. Bông cải (dish_code: bong_cai - 1 đĩa luộc chín chuẩn 100g ~ 34 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bong_cai'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4100002'), 100.0, 1);           -- Súp lơ xanh (luộc) chín tới giữ trọn vitamin (100g)

-- ---------------------------------------------------------------------
-- 13. Bún tươi (dish_code: bun - 1 phần chuẩn 100g ~ 116 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 100.0, 1);                  -- Bún tươi sợi trắng (100g)

-- ---------------------------------------------------------------------
-- 14. Bún bò Huế (dish_code: bun_bo_hue - 1 tô đầy đủ chuẩn vị ~703g ~ 532.5 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 160.0, 1),            -- Bún tươi sợi to trụng nóng (160g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 40.0, 2),          -- Thịt bắp/nạm bò chín thái mỏng (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7032002'), 50.0, 3),          -- Thịt chân giò lợn luộc mềm thơm (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7069'), 30.0, 4),             -- Giò lụa / chả Huế (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7059'), 30.0, 5),             -- Tiết lợn (huyết) luộc chín (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13011'), 3.0, 6),             -- Mắm ruốc đặc nêm cốt nước dùng (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 10.0, 7),       -- Sả cây đập dập nấu nước dùng (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13054'), 3.0, 8),             -- Sa tế sả ớt tạo độ cay thơm (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 2.0, 9),             -- Dầu thực vật phi hạt điều váng đỏ (2g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_BO'), 350.0, 10),     -- Nước dùng hầm xương bò (350g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4043'), 20.0, 11),            -- Hoa chuối tươi bào sợi (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 10.0, 12),            -- Rau thơm: húng quế, ngò gai (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 15.0, 13),            -- Giá đậu xanh tươi (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 14),             -- Hành lá, ngò gai rắc mặt (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 2.0, 15),            -- Đường cát nêm dịu vị (2g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_bo_hue'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 3.0, 16);            -- Nước mắm cá nêm cốt (3g)










-- ---------------------------------------------------------------------
-- 15. Cơm trắng (dish_code: com - 1 bát ~150g ~ 211.8 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'com'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1004_convert'), 150.0, 1);

-- ---------------------------------------------------------------------
-- 16. Xôi (dish_code: xoi - 1 bát ~150g ~ 261.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'xoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1001_convert'), 150.0, 1);

-- ---------------------------------------------------------------------
-- 17. Mì (dish_code: mi - 1 phần luộc 100g ~ 102.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1043002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 18. Thịt bò (dish_code: thit_bo - 1 phần 100g ~ 157.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'thit_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 19. Thịt gà (dish_code: thit_ga - 1 phần 100g ~ 214.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'thit_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7108002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 20. Thịt heo (dish_code: thit_heo - 1 phần 100g ~ 126.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'thit_heo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 21. Heo quay (dish_code: heo_quay - 1 phần 100g ~ 358.8 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'heo_quay'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018_convert'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 22. Chả lụa (dish_code: cha - 1 phần 100g ~ 136.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7069'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 23. Lòng heo (dish_code: long_heo - 1 phần 100g ~ 167.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'long_heo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7046'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 24. Cá (dish_code: ca - 1 phần 100g ~ 133.2 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8062_hap_convert'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 25. Tôm (dish_code: tom - 1 phần 100g ~ 82.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'tom'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 26. Mực (dish_code: muc - 1 phần 100g ~ 115.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'muc'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8040_luoc_convert'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 27. Cua (dish_code: cua - 1 phần 100g ~ 123.6 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8033_luoc_convert'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 28. Ốc (dish_code: oc - 1 phần 100g ~ 337.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'oc'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8043003'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 29. Trứng (dish_code: trung - 1 quả luộc ~50g ~ 76.9 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'trung'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001_luoc_convert'), 50.0, 1);

-- ---------------------------------------------------------------------
-- 30. Đậu hũ (dish_code: dau_hu - 1 phần 100g ~ 98.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'dau_hu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3025002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 31. Rau xanh (dish_code: rau - 1 đĩa luộc 100g ~ 27.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'rau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4083002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 32. Cà chua (dish_code: ca_chua - 1 phần 100g ~ 24.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'ca_chua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 33. Dưa leo (dish_code: dua_leo - 1 phần 100g ~ 16.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'dua_leo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 34. Cà rốt (dish_code: ca_rot - 1 phần luộc 100g ~ 47.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'ca_rot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007002'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 35. Cà pháo (dish_code: ca_phao - 1 phần 100g ~ 20.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'ca_phao'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4113'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 36. Củ kiệu (dish_code: cu_kieu - 1 phần 100g ~ 29.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'cu_kieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4121'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 37. Ớt chuông (dish_code: ot_chuong - 1 phần 100g ~ 31.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'ot_chuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4061'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 38. Nấm (dish_code: nam - 1 phần 100g ~ 61.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'nam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4129'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 39. Chanh (dish_code: chanh - 1 quả ~30g ~ 8.7 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'chanh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 30.0, 1);

-- ---------------------------------------------------------------------
-- 40. Phô mai (dish_code: pho_mai - 1 phần 100g ~ 380.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'pho_mai'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_10009'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 41. Dưa chua (dish_code: dua_chua - 1 phần 100g ~ 25.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'dua_chua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4116'), 100.0, 1);

-- ---------------------------------------------------------------------
-- 42. Khoai tây chiên (dish_code: khoai_tay_chien - 1 phần 100g ~ 323.0 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'khoai_tay_chien'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_170721'), 100.0, 1);

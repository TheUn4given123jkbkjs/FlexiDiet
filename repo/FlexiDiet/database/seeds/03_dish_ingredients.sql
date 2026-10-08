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

-- ---------------------------------------------------------------------
-- 43. Bún chả (dish_code: bun_cha - 1 suất đầy đủ thịt nướng, bún, nước chấm, đồ chua ~540g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 80.0, 1),              -- Thịt nạc vai heo băm nướng chả viên (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018'), 80.0, 2),              -- Thịt ba chỉ thái mỏng nướng chả miếng (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 180.0, 3),             -- Bún tươi sợi nhỏ (180g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 4.0, 4),        -- Sả tươi băm nhuyễn ướp thịt (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 4.0, 5),               -- Hành củ (hành tím) băm ướp thịt (4g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 7.0, 6),               -- Tỏi ta băm (4g ướp thịt + 3g pha nước chấm) (7g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 35.0, 7),             -- Nước mắm cá (5g ướp thịt + 30g pha nước chấm) (35g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 30.0, 8),             -- Đường cát (5g thắng nước hàng + 25g pha nước chấm) (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 9),              -- Hạt tiêu đen xay (0.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12015'), 2.5, 10),             -- Mật ong ướp chả nướng thơm vàng (2.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13034'), 25.0, 11),            -- Giấm ăn pha nước chấm chua ngọt (25g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4096'), 20.0, 12),             -- Củ su hào tươi thái mỏng làm đồ chua (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 20.0, 13),             -- Củ cà rốt tươi thái mỏng làm đồ chua (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 2.0, 14),             -- Ớt tươi băm nhuyễn pha nước chấm (2g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 50.0, 15);            -- Rau sống: xà lách, tía tô, kinh giới, húng quế (50g)

-- ---------------------------------------------------------------------
-- 44. Bún đậu mắm tôm (dish_code: bun_dau - 1 suất mẹt bún đậu thịt chả mắm tôm ~572.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3026'), 150.0, 1),             -- Đậu phụ rán chiên giòn rụm (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7032002'), 80.0, 2),           -- Thịt chân giò lợn luộc thái lát (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_CHA_COM_CHIEN'), 50.0, 3), -- Chả cốm chiên vàng giòn (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 180.0, 4),            -- Bún tươi sợi cắt miếng/bún lá (180g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13011'), 30.0, 5),            -- Mắm tôm đặc nguyên chất (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 7.5, 6),             -- Đường cát pha dịu mắm tôm (7.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 10.0, 7),             -- Nước cốt tắc/quất vắt tạo bọt (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 10.0, 8),            -- Dầu thực vật sôi đánh sủi bọt mắm tôm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 3.0, 9),              -- Tỏi ta băm nhuyễn (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 2.0, 10),            -- Ớt tươi băm nhuyễn (2g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 30.0, 11),            -- Dưa chuột tươi thái lát ăn kèm (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_dau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 20.0, 12);           -- Rau thơm: tía tô, kinh giới, húng quế (20g)

-- ---------------------------------------------------------------------
-- 45. Bún mắm (dish_code: bun_mam - 1 tô chuẩn vị miền Tây ~597g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_MAM_CA'), 40.0, 1),       -- Mắm cá linh/sặc cốt nấu nước dùng (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_14006'), 150.0, 2),             -- Nước dừa non tươi nấu nước dùng ngọt thanh (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 15.0, 3),        -- Sả cây băm và đập dập (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4009002'), 40.0, 4),            -- Cà tím luộc/nấu nước lèo (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 150.0, 5),              -- Bún tươi sợi lớn (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 40.0, 6),            -- Tôm biển luộc chín bóc vỏ (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8040_luoc_convert'), 30.0, 7),   -- Mực tươi luộc cắt khoanh (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018_convert'), 40.0, 8),        -- Thịt heo quay giòn bì (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8064008'), 30.0, 9),            -- Chả cá rán miếng (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4083'), 30.0, 10),              -- Rau muống chẻ (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4043'), 30.0, 11),              -- Hoa chuối/bắp chuối bào (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 20.0, 12),              -- Giá đỗ tươi (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 10.0, 13),              -- Chanh tươi gia giảm vị (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_mam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 2.0, 14);              -- Ớt tươi (2g)

-- ---------------------------------------------------------------------
-- 46. Bún riêu (dish_code: bun_rieu - 1 tô bún riêu cua đầy đủ topping ~562g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8034'), 50.0, 1),            -- Cua đồng tươi lọc gạch/thịt riêu (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 30.0, 2),            -- Thịt lợn nạc vai băm trộn riêu (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 20.0, 3),            -- Trứng gà ta đánh tạo mảng riêu (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 60.0, 4),            -- Quả cà chua tươi bổ múi cau xào nước dùng (60g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 5.0, 5),            -- Dầu thực vật phi xào cà chua (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13034'), 10.0, 6),           -- Giấm ăn / giấm bỗng tạo vị chua thanh (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 180.0, 7),           -- Bún tươi (180g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3026'), 50.0, 8),            -- Đậu phụ rán vàng giòn (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7059'), 40.0, 9),            -- Tiết lợn luộc chín (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7069'), 30.0, 10),           -- Giò lụa chín thái miếng (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13011'), 5.0, 11),           -- Mắm tôm đặc nêm nước dùng dậy mùi (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 12),           -- Nước mắm cá (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 10.0, 13),           -- Hành lá / hành hoa thái nhỏ (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 60.0, 14),           -- Rau sống: rau muống chẻ, hoa chuối, kinh giới, giá (60g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 5.0, 15),            -- Chanh tươi (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_rieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 2.0, 16);           -- Ớt tươi (2g)

-- ---------------------------------------------------------------------
-- 47. Canh (dish_code: canh - 1 bát canh rau ngót nấu thịt băm chuẩn gia đình Việt ~141g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4086'), 100.0, 1),           -- Rau ngót tươi (100g)
  ((SELECT id FROM dishes WHERE dish_code = 'canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 30.0, 2),             -- Thịt lợn nạc vai băm (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 3.0, 3),             -- Dầu thực vật phi xào thịt (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 4),             -- Nước mắm cá (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'canh'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 3.0, 5);              -- Hành củ (hành tím) phi thơm (3g)

-- ---------------------------------------------------------------------
-- 48. Chả giò / Nem rán (dish_code: cha_gio - 1 đĩa nem rán gồm 5-6 chiếc giòn rụm ~265.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 80.0, 1),            -- Thịt lợn nạc vai băm nhuyễn (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051'), 30.0, 2),            -- Tôm biển tươi bóc vỏ băm nhỏ (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4125'), 10.0, 3),            -- Mộc nhĩ khô ngâm nở thái sợi (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4126'), 5.0, 4),             -- Nấm hương khô ngâm nở băm nhỏ (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_2015'), 10.0, 5),            -- Miến dong khô ngâm mềm cắt khúc (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 20.0, 6),            -- Củ cà rốt tươi bào sợi (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4096'), 20.0, 7),            -- Củ su hào tươi bào sợi (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 20.0, 8),            -- Giá đậu xanh tươi (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 25.0, 9),            -- Trứng gà ta kết dính nhân (25g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 5.0, 10),            -- Hành tím băm (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 30.0, 11), -- Bánh tráng / bánh đa nem cuốn vỏ giòn (30g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 12.0, 12),           -- Dầu thực vật rán vàng giòn (độ ngấm dầu ~12g) (12g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 13),           -- Hạt tiêu đen xay (0.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'cha_gio'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 3.0, 14);           -- Nước mắm cá nêm nhân (3g)

-- ---------------------------------------------------------------------
-- 49. Cơm tấm (dish_code: com_tam - 1 đĩa cơm tấm sườn bì chả mỡ hành đồ chua ~391g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1004_convert'), 150.0, 1),    -- Cơm tấm dẻo thơm (150g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7053'), 80.0, 2),             -- Sườn heo cốt lết ướp nướng (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 20.0, 3),             -- Thịt heo nạc vai băm làm chả trứng (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7031'), 15.0, 4),             -- Bì heo (da heo luộc thái sợi) (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7084002'), 10.0, 5),          -- Thịt nạc mông luộc xé sợi trộn bì (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 15.0, 6),             -- Trứng gà ta làm chả trứng hấp (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4125'), 3.0, 7),              -- Mộc nhĩ khô thái sợi làm chả trứng (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_2015'), 2.0, 8),              -- Miến dong khô cắt nhỏ làm chả trứng (2g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 9),              -- Hành lá tươi làm mỡ hành (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 7.0, 10),            -- Dầu thực vật (3g ướp sườn + 4g mỡ hành) (7g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 20.0, 11),            -- Dưa chuột tươi thái lát ăn kèm (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 15.0, 12),            -- Cà chua tươi thái lát ăn kèm (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 15.0, 13),            -- Cà rốt tươi làm đồ chua chua ngọt (15g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 10.0, 14),           -- Nước mắm cá (5g ướp sườn + 5g pha nước mắm chấm) (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 8.0, 15),            -- Đường cát (4g ướp sườn + 4g pha nước mắm chấm) (8g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12015'), 3.0, 16),            -- Mật ong ướp sườn thơm vàng (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 5.0, 17),             -- Tỏi ta băm (3g ướp sườn + 2g pha nước mắm) (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 3.0, 18),             -- Hành tím băm ướp sườn (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 19),            -- Hạt tiêu đen xay (0.5g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 3.0, 20),             -- Chanh tươi vắt nước mắm chấm (3g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_tam'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.5, 21);            -- Ớt tươi băm pha nước mắm (1.5g)

-- ---------------------------------------------------------------------
-- 50. Gỏi cuốn (dish_code: goi_cuon - 1 phần 3 cuốn ~241.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 20.0, 1), -- Bánh tráng khô (cuốn gỏi) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 50.0, 2), -- Bún tươi sợi nhỏ (50.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 45.0, 3), -- Tôm biển luộc bóc vỏ (45.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7032002'), 40.0, 4), -- Thịt chân giò / ba chỉ luộc (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4042'), 10.0, 5), -- Hẹ lá tươi (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 30.0, 6), -- Rau thơm, xà lách (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 20.0, 7), -- Giá đậu xanh tươi (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_HOISIN'), 20.0, 8), -- Tương đen chấm gỏi cuốn (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_174261'), 5.0, 9), -- Đậu phộng rang rắc lên tương (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'goi_cuon'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 10); -- Ớt tươi băm (1.0g)

-- ---------------------------------------------------------------------
-- 51. Hamburger bò (dish_code: hamburger - 1 cái ~225.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1012'), 70.0, 1), -- Bánh mì burger bun (70.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 90.0, 2), -- Thịt bò nạc xay làm patty áp chảo (90.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_10009'), 20.0, 3), -- Phô mát (cheddar) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 20.0, 4), -- Quả cà chua tươi (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4069'), 15.0, 5), -- Xà lách tươi (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13023'), 5.0, 6), -- Sốt mayonnaise (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hamburger'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_2709733'), 5.0, 7); -- Tương cà (ketchup) (5.0g)

-- ---------------------------------------------------------------------
-- 52. Hủ tiếu Nam Vang (dish_code: hu_tieu - 1 tô ~550.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 140.0, 1), -- Sợi hủ tiếu (bún gạo chần) (140.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 30.0, 2), -- Tôm biển luộc bóc vỏ (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 30.0, 3), -- Thịt heo nạc vai băm xào tỏi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7085002'), 30.0, 4), -- Thịt nạc thăn heo luộc thái mỏng (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7041002'), 20.0, 5), -- Gan heo luộc thái lát (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9007_luoc_convert'), 20.0, 6), -- Trứng chim cút luộc (2 quả) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 30.0, 7), -- Giá đậu xanh tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4042'), 15.0, 8), -- Hẹ lá tươi (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4018'), 15.0, 9), -- Cần tây tươi (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037_phi_convert'), 5.0, 10), -- Hành phi giòn (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 4.0, 11), -- Tỏi ta băm (4.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_HEO'), 200.0, 12), -- Nước dùng xương hầm (200.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 13), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 5.0, 14), -- Chanh tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'hu_tieu'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 15); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 53. Khổ qua dồi thịt (dish_code: kho_qua_thit - 1 bát ~205.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4055'), 120.0, 1), -- Mướp đắng (khổ qua) tươi (120.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 60.0, 2), -- Thịt heo nạc vai băm nhồi (60.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4125'), 5.0, 3), -- Mộc nhĩ khô thái sợi nhuyễn (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_2015'), 5.0, 4), -- Miến dong khô cắt vụn (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 3.0, 5), -- Hành tím băm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 6), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 7), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 2.0, 8), -- Dầu thực vật (2.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'kho_qua_thit'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 9); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 54. Lẩu thập cẩm (dish_code: lau - 1 phần ăn cá nhân ~647.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_BROTH_HEO'), 250.0, 1), -- Nước dùng xương hầm thanh ngọt (250.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 60.0, 2), -- Thịt bò bắp thái mỏng nhúng (60.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051'), 40.0, 3), -- Tôm biển tươi (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8040'), 40.0, 4), -- Mực tươi cắt miếng khía hoa (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_3025'), 50.0, 5), -- Đậu phụ trắng tươi (50.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4129'), 30.0, 6), -- Nấm rơm tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4133'), 30.0, 7), -- Nấm kim châm tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4083'), 50.0, 8), -- Rau muống cọng (50.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4013'), 40.0, 9), -- Cải cúc (tần ô) tươi (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 50.0, 10), -- Bún tươi sợi nhúng lẩu (50.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 11), -- Nước mắm cá gia giảm (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 2.0, 12); -- Ớt tươi cắt lát (2.0g)

-- ---------------------------------------------------------------------
-- 55. Salad rau củ quả (dish_code: salad - 1 đĩa ~211.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4069'), 70.0, 1), -- Xà lách tươi (70.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 40.0, 2), -- Quả cà chua tươi (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 40.0, 3), -- Dưa chuột tươi thái lát (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1007'), 20.0, 4), -- Ngô tươi (hạt bắp ngọt) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001_luoc_convert'), 25.0, 5), -- Trứng gà ta luộc thái lát (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 8.0, 6), -- Dầu thực vật trộn giấm (8.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13034'), 5.0, 7), -- Giấm ăn (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'salad'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 3.0, 8); -- Đường cát (3.0g)

-- ---------------------------------------------------------------------
-- 56. Thịt kho tàu trứng (dish_code: thit_kho - 1 phần ~217.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018'), 100.0, 1), -- Thịt heo ba chỉ thái khối vuông (100.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9004_luoc_convert'), 55.0, 2), -- Trứng vịt luộc bóc vỏ (1 quả) (55.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_14006'), 40.0, 3), -- Nước dừa non tươi nấu kho (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 10.0, 4), -- Nước mắm cá kho đậm đà (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 6.0, 5), -- Đường cát thắng nước màu (6.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 3.0, 6), -- Hành củ tím băm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 2.0, 7), -- Tỏi ta băm (2.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 8), -- Ớt tươi (1.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_kho'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 9); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 57. Thịt nướng sả mè (dish_code: thit_nuong - 1 đĩa ~150.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 120.0, 1), -- Thịt heo nạc vai thái lát (120.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'LONGCHAU_SA_TUOI'), 10.0, 2), -- Sả tươi băm nhuyễn (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 5.0, 3), -- Dầu thực vật ướp bóng mềm (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 4), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12015'), 4.0, 5), -- Mật ong ướp vàng thơm (4.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 3.0, 6), -- Tỏi ta băm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4037'), 3.0, 7), -- Hành củ tím băm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'thit_nuong'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 8); -- Hạt tiêu đen xay (0.5g)

-- ---------------------------------------------------------------------
-- 58. Bánh bèo tôm cháy (dish_code: banh_beo - 1 đĩa 6 chén ~161.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017'), 70.0, 1), -- Bột gạo tẻ pha hấp bánh (70.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051'), 35.0, 2), -- Tôm biển tươi giã làm tôm cháy (35.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7083'), 20.0, 3), -- Thịt heo nạc vai băm xào nhân (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1012'), 10.0, 4), -- Bánh mì vụn sấy giòn (thay tóp mỡ) (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 6.0, 5), -- Dầu thực vật làm mỡ hành (6.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 6), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 8.0, 7), -- Nước mắm cá pha chan bánh (8.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 6.0, 8), -- Đường cát pha nước mắm (6.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_beo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 9); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 59. Cao lầu Hội An (dish_code: cao_lau - 1 tô ~330.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 140.0, 1), -- Sợi cao lầu (bún gạo sợi dày) (140.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7085'), 80.0, 2), -- Thịt nạc thăn heo làm xá xíu (80.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7031'), 15.0, 3), -- Bì lợn chiên giòn (ram cao lầu) (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036002'), 40.0, 4), -- Giá đậu xanh luộc sơ (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 35.0, 5), -- Rau sống, rau thơm Trà Quế (35.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13019'), 8.0, 6), -- Xì dầu (nước tương) ướp xíu (8.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 5.0, 7), -- Dầu thực vật (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 3.0, 8), -- Tỏi ta băm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 3.0, 9), -- Đường cát (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'cao_lau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 10); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 60. Mì Quảng tôm thịt (dish_code: mi_quang - 1 tô ~347.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1013'), 150.0, 1), -- Bánh phở / Mì Quảng tươi (150.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051'), 40.0, 2), -- Tôm biển tươi rim (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7018'), 40.0, 3), -- Thịt heo ba chỉ rim (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9007_luoc_convert'), 20.0, 4), -- Trứng chim cút luộc rim (2 quả) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_13046_convert'), 15.0, 5), -- Bánh tráng nướng mè bẻ vụn (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_174261'), 10.0, 6), -- Đậu phộng rang rắc mặt (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4043'), 30.0, 7), -- Hoa chuối tươi bào mỏng (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 25.0, 8), -- Rau thơm, xà lách (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 6.0, 9), -- Dầu thực vật rim nhưn (6.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 10), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 11), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'mi_quang'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 12); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 61. Cơm chiên Dương Châu (dish_code: com_chien_duong_chau - 1 đĩa ~323.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1004_convert'), 180.0, 1), -- Cơm trắng tẻ chiên (180.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 30.0, 2), -- Trứng gà ta chiên cơm (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7066'), 25.0, 3), -- Dăm bông lợn thái hạt lựu (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051002'), 30.0, 4), -- Tôm biển luộc chín thái hạt lựu (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 20.0, 5), -- Củ cà rốt tươi thái hạt lựu (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4031'), 20.0, 6), -- Hạt đậu Hà Lan (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 10.0, 7), -- Dầu thực vật chiên cơm (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 8), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 3.0, 9), -- Nước mắm cá nêm cơm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_duong_chau'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 10); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 62. Bún chả cá (dish_code: bun_cha_ca - 1 tô ~401.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1020'), 150.0, 1), -- Bún tươi (150.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8064008'), 60.0, 2), -- Chả cá rán lát (60.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 40.0, 3), -- Quả cà chua tươi (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5014'), 30.0, 4), -- Dứa ta tươi nấu nước dùng chua ngọt (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4003'), 30.0, 5), -- Quả bí ngô (bí đỏ) tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 40.0, 6), -- Rau sống, xà lách, rau thơm (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 30.0, 7), -- Giá đậu xanh tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 5.0, 8), -- Dầu thực vật xào cà chua (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 9), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 10), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 5.0, 11), -- Chanh tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'bun_cha_ca'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 12); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 63. Cơm chiên gà (dish_code: com_chien_ga - 1 đĩa ~353.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1004_convert'), 180.0, 1), -- Cơm trắng tẻ chiên (180.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7108002'), 80.0, 2), -- Thịt chân đùi gà luộc xé sợi (80.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 25.0, 3), -- Trứng gà ta chiên cùng cơm (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 10.0, 4), -- Dầu thực vật chiên cơm (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 15.0, 5), -- Củ cà rốt tươi thái hạt lựu (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 6), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 20.0, 7), -- Dưa chuột tươi ăn kèm (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 15.0, 8), -- Cà chua tươi ăn kèm (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 3.0, 9), -- Nước mắm cá (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'com_chien_ga'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 10); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 64. Cháo lòng (dish_code: chao_long - 1 tô ~366.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1004_chao_convert'), 160.0, 1), -- Cháo trắng nấu nhừ (160.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7059'), 40.0, 2), -- Tiết lợn luộc (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7046'), 35.0, 3), -- Lòng non lợn luộc (35.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7034002'), 25.0, 4), -- Dạ dày lợn luộc (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7041002'), 25.0, 5), -- Gan lợn luộc (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7067'), 25.0, 6), -- Dồi lợn chín (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7057002'), 20.0, 7), -- Tim lợn luộc (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 20.0, 8), -- Giá đậu xanh tươi (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 10.0, 9), -- Hành lá tươi (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 10), -- Nước mắm cá (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 11), -- Ớt tươi (1.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'chao_long'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 12); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 65. Nộm hoa chuối tai heo (dish_code: nom_hoa_chuoi - 1 đĩa ~271.0g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4043'), 120.0, 1), -- Hoa chuối tươi bào mỏng (120.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7054002'), 45.0, 2), -- Tai lợn luộc giòn sần sật (45.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 25.0, 3), -- Củ cà rốt tươi bào sợi (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4036'), 25.0, 4), -- Giá đậu xanh tươi (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'USDA_174261'), 15.0, 5), -- Đậu phộng rang giã dập (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 15.0, 6), -- Rau thơm, kinh giới, ngò rí (15.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 10.0, 7), -- Nước mắm cá trộn gỏi (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_12013'), 8.0, 8), -- Đường cát trộn chua ngọt (8.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_5003'), 5.0, 9), -- Chanh tươi vắt lấy nước cốt (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 2.0, 10), -- Tỏi ta băm (2.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nom_hoa_chuoi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13039'), 1.0, 11); -- Ớt tươi (1.0g)

-- ---------------------------------------------------------------------
-- 66. Nui xào bò (dish_code: nui_xao_bo - 1 đĩa ~307.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1046002'), 130.0, 1), -- Nui luộc chín (130.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7005018'), 70.0, 2), -- Thịt bò nạc xào mềm (70.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4108'), 40.0, 3), -- Rau cải ngọt tươi xào kèm (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4005'), 30.0, 4), -- Quả cà chua tươi (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4039'), 20.0, 5), -- Hành tây tươi xào (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 8.0, 6), -- Dầu thực vật xào (8.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13019'), 6.0, 7), -- Xì dầu (nước tương) (6.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4103'), 3.0, 8), -- Tỏi ta băm phi thơm (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'nui_xao_bo'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 9); -- Hạt tiêu đen (0.5g)

-- ---------------------------------------------------------------------
-- 67. Súp cua gà xé (dish_code: sup_cua - 1 bát ~163.5g)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8033_luoc_convert'), 40.0, 1), -- Thịt cua bể hấp gỡ thịt (40.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7106002'), 30.0, 2), -- Thịt gà ta (thịt nạc lườn luộc xé) (30.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9001'), 25.0, 3), -- Trứng gà ta đánh vân trứng (25.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_9007_luoc_convert'), 20.0, 4), -- Trứng chim cút luộc (2 quả) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1007'), 20.0, 5), -- Ngô tươi (hạt bắp ngọt) (20.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13046'), 10.0, 6), -- Bột năng tạo độ sánh sệt (10.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4126'), 5.0, 7), -- Nấm hương khô thái sợi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 5.0, 8), -- Hành lá tươi (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 5.0, 9), -- Nước mắm cá nêm súp (5.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_11001'), 3.0, 10), -- Dầu thực vật phi (3.0g)
  ((SELECT id FROM dishes WHERE dish_code = 'sup_cua'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13004'), 0.5, 11); -- Hạt tiêu đen (0.5g)


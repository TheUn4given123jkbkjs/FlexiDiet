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
-- 5. Bánh khọt (dish_code: banh_khot - 1 đĩa 6-8 cái ~280g ~ 366 Kcal)
-- ---------------------------------------------------------------------
INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1017_chien_convert'), 120.0, 1), -- Bột gạo chiên giòn (120g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8051_chien_convert'), 50.0, 2),  -- Tôm biển chiên giòn (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_8059'), 5.0, 3),                 -- Ruốc tôm / tôm chấy đỏ (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4038'), 10.0, 4),                -- Hành lá / mỡ hành thoa mặt (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 50.0, 5),                -- Rau thơm & xà lách ăn kèm (50g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007002'), 20.0, 6),            -- Cà rốt ngâm đồ chua (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_khot'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13017'), 25.0, 7),              -- Nước mắm cá pha chua ngọt (25g)

  -- -------------------------------------------------------------------
  -- Món 6: banh_mi (Bánh mì kẹp thịt / thập cẩm - 1 ổ tiêu chuẩn ~200g)
  -- Năng lượng chuẩn: ~397.5 Kcal (Đạm: 18.2g, Béo: 14.6g, Carb: 48.3g)
  -- -------------------------------------------------------------------
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_1012'), 80.0, 1),                 -- Vỏ bánh mì không (80g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7063'), 20.0, 2),                 -- Ba tê lợn (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_7069'), 40.0, 3),                 -- Giò lụa lợn / chả lụa (40g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'MANUAL_SOT_BO_TRUNG'), 10.0, 4),      -- Sốt bơ trứng gà / bơ bánh mì (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4027'), 20.0, 5),                 -- Dưa chuột / dưa leo thái lát (20g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4007'), 10.0, 6),                 -- Đồ chua: Cà rốt ngâm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4021'), 10.0, 7),                 -- Đồ chua: Củ cải trắng ngâm (10g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_4094'), 5.0, 8),                  -- Rau thơm / ngò rí (5g)
  ((SELECT id FROM dishes WHERE dish_code = 'banh_mi'), (SELECT id FROM ingredients WHERE source_ref = 'VDD_13019'), 5.0, 9);                 -- Xì dầu / nước tương (5g)





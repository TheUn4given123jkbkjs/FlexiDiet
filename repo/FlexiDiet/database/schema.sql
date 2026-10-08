-- =====================================================================
-- FlexiDiet: Lược đồ CSDL chính thức (Pha Elaboration E1)
-- Yêu cầu: MySQL >= 8.0.16 (InnoDB, utf8mb4_unicode_ci, múi giờ +07:00)
-- Tài liệu thiết kế: docs/02_elaboration/02_database_design.md
-- =====================================================================

CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flexidiet;

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- Dọn dẹp bảng cũ nếu đã tồn tại để script có thể chạy lại an toàn nhiều lần
DROP TABLE IF EXISTS ai_feedback;
DROP TABLE IF EXISTS rate_limit_hits;
DROP TABLE IF EXISTS draft_images;
DROP TABLE IF EXISTS water_logs;
DROP TABLE IF EXISTS meal_entry_items;
DROP TABLE IF EXISTS meal_entries;
DROP TABLE IF EXISTS dish_ingredients;
DROP TABLE IF EXISTS dishes;
DROP TABLE IF EXISTS portion_units;
DROP TABLE IF EXISTS ingredient_aliases;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS workouts;
DROP TABLE IF EXISTS exercise_met_rules;
DROP TABLE IF EXISTS exercise_types;
DROP TABLE IF EXISTS daily_budgets;
DROP TABLE IF EXISTS weight_logs;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Người dùng & Chỉ số
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(255)    NOT NULL,              -- lưu chữ thường
  password_hash VARCHAR(255)    NOT NULL,              -- password_hash() của PHP
  display_name  VARCHAR(100)    NOT NULL,
  role          ENUM('member','admin') NOT NULL DEFAULT 'member',
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hồ sơ KHÔNG có cột cân nặng: cân nặng hiện tại = bản ghi mới nhất của weight_logs.
CREATE TABLE user_profiles (
  user_id             BIGINT UNSIGNED NOT NULL,
  sex                 ENUM('male','female') NOT NULL,
  birth_date          DATE            NOT NULL,
  height_cm           DECIMAL(5,1)    NOT NULL,
  goal                ENUM('lose','maintain','gain','build_muscle') NOT NULL,
  target_weight_kg    DECIMAL(5,2)    NULL,
  body_fat_pct        DECIMAL(4,1)    NULL,             -- chỉ cần khi dùng Katch-McArdle
  bmr_formula         ENUM('mifflin_st_jeor','katch_mcardle') NOT NULL DEFAULT 'mifflin_st_jeor',
  credit_policy       ENUM('full','partial','capped') NOT NULL DEFAULT 'partial', -- chính sách mặc định, sao chép sang daily_budgets
  weekly_workout_goal TINYINT UNSIGNED NOT NULL,        -- số buổi tập mục tiêu mỗi tuần: chỉ để nhắc nhở
  reminders_enabled   TINYINT(1)      NOT NULL DEFAULT 1,
  created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_profiles_height CHECK (height_cm > 0),
  CONSTRAINT ck_profiles_goal_days CHECK (weekly_workout_goal BETWEEN 0 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE weight_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  log_date   DATE            NOT NULL,
  weight_kg  DECIMAL(5,2)    NOT NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_weight_user_date (user_id, log_date),   -- mỗi ngày một bản ghi; ghi lại thì thay thế
  CONSTRAINT fk_weight_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_weight_positive CHECK (weight_kg > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Ngân sách theo ngày
--    exercise_credit_kcal = LEAST(exercise_raw_kcal * credit_factor, credit_cap_kcal nếu có)
--      full   : factor 1.00, cap NULL
--      partial: factor 0.50, cap NULL
--      capped : factor 1.00, cap 500
--    "Còn lại" KHÔNG lưu: target + exercise_credit - SUM(meal_entries.total_kcal).
-- ---------------------------------------------------------------------
CREATE TABLE daily_budgets (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              BIGINT UNSIGNED NOT NULL,
  budget_date          DATE            NOT NULL,
  weight_kg_used       DECIMAL(5,2)    NOT NULL,
  bmr_formula          ENUM('mifflin_st_jeor','katch_mcardle') NOT NULL,
  bmr_kcal             DECIMAL(8,1)    NOT NULL,
  baseline_kcal        DECIMAL(8,1)    NOT NULL,        -- BMR x hệ số sinh hoạt cố định (1.2)
  goal_adjust_kcal     DECIMAL(8,1)    NOT NULL DEFAULT 0,
  floor_applied        TINYINT(1)      NOT NULL DEFAULT 0, -- 1 nếu đã đặt bằng sàn calo
  target_kcal          DECIMAL(8,1)    NOT NULL,        -- ngân sách mục tiêu của ngày (sau khi áp sàn)
  credit_policy        ENUM('full','partial','capped') NOT NULL,
  credit_factor        DECIMAL(4,2)    NOT NULL,
  credit_cap_kcal      DECIMAL(8,1)    NULL,
  exercise_raw_kcal    DECIMAL(8,1)    NOT NULL DEFAULT 0, -- tổng calo thô các buổi tập (dẫn xuất, do recalcDay giữ)
  exercise_credit_kcal DECIMAL(8,1)    NOT NULL DEFAULT 0, -- phần được cộng (dẫn xuất)
  created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_budget_user_date (user_id, budget_date),
  CONSTRAINT fk_budget_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_budget_target CHECK (target_kcal > 0),
  CONSTRAINT ck_budget_credit CHECK (exercise_raw_kcal >= 0 AND exercise_credit_kcal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tập luyện
-- ---------------------------------------------------------------------
CREATE TABLE exercise_types (
  id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code          VARCHAR(40)  NOT NULL,
  name_vi       VARCHAR(100) NOT NULL,
  uses_distance TINYINT(1)   NOT NULL DEFAULT 0,        -- đi bộ/chạy: chọn MET theo tốc độ
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exercise_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exercise_met_rules (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  exercise_type_id SMALLINT UNSIGNED NOT NULL,
  speed_min_kmh    DECIMAL(4,1) NULL,
  speed_max_kmh    DECIMAL(4,1) NULL,
  met              DECIMAL(4,1) NOT NULL,
  source_ref       VARCHAR(100) NULL,                   -- mã trong Compendium of Physical Activities
  PRIMARY KEY (id),
  KEY ix_met_type (exercise_type_id),
  CONSTRAINT fk_met_type FOREIGN KEY (exercise_type_id) REFERENCES exercise_types (id) ON DELETE CASCADE,
  CONSTRAINT ck_met_positive CHECK (met > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Buổi tập chỉ lưu calo THÔ; không lưu hệ số cộng (FR-04.7).
CREATE TABLE workouts (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          BIGINT UNSIGNED NOT NULL,
  workout_date     DATE            NOT NULL,
  exercise_type_id SMALLINT UNSIGNED NOT NULL,
  duration_min     DECIMAL(6,1)    NOT NULL,
  distance_km      DECIMAL(6,2)    NULL,
  calorie_source   ENUM('met','device') NOT NULL,
  confidence       ENUM('high','medium','low') NOT NULL, -- device: high, met: medium
  met_value        DECIMAL(4,1)    NULL,                 -- bắt buộc khi calorie_source = 'met'
  weight_kg_used   DECIMAL(5,2)    NULL,                 -- bắt buộc khi calorie_source = 'met'
  raw_kcal         DECIMAL(8,1)    NOT NULL,
  note             VARCHAR(255)    NULL,
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_workouts_user_date (user_id, workout_date),
  CONSTRAINT fk_workouts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_workouts_type FOREIGN KEY (exercise_type_id) REFERENCES exercise_types (id) ON DELETE RESTRICT,
  CONSTRAINT ck_workouts_values CHECK (duration_min > 0 AND raw_kcal >= 0),
  CONSTRAINT ck_workouts_met CHECK (calorie_source = 'device' OR (met_value IS NOT NULL AND weight_kg_used IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Danh mục dinh dưỡng (hệ thống + cá nhân dùng chung bảng)
-- ---------------------------------------------------------------------
CREATE TABLE ingredients (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id  BIGINT UNSIGNED NULL,
  name           VARCHAR(150) NOT NULL,
  name_norm      VARCHAR(150) NOT NULL,                  -- chữ thường, bỏ dấu, đ -> d
  kcal_100g      DECIMAL(7,2) NOT NULL,
  protein_100g   DECIMAL(6,2) NOT NULL,
  carb_100g      DECIMAL(6,2) NOT NULL,
  fat_100g       DECIMAL(6,2) NOT NULL,
  state          ENUM('raw','cooked','na') NOT NULL DEFAULT 'na',   -- sống / chín / không áp dụng
  edible_pct     DECIMAL(5,2) NOT NULL DEFAULT 100.00,   -- tỷ lệ phần ăn được
  source         ENUM('vdd','usda','manual','user') NOT NULL,       -- vdd = Bảng thành phần thực phẩm VN
  source_ref     VARCHAR(100) NULL,                      -- mã trong bảng nguồn; khóa tự nhiên cho seed
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ingredients_source_ref (source, source_ref),
  UNIQUE KEY uq_ingredients_owner_name (owner_user_id, name_norm),
  KEY ix_ingredients_name_norm (name_norm),
  CONSTRAINT fk_ingredients_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_ingredients_nonneg CHECK (kcal_100g >= 0 AND protein_100g >= 0 AND carb_100g >= 0 AND fat_100g >= 0),
  CONSTRAINT ck_ingredients_macro_sum CHECK (protein_100g + carb_100g + fat_100g <= 100),
  CONSTRAINT ck_ingredients_edible CHECK (edible_pct > 0 AND edible_pct <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Từ đồng nghĩa / tên gọi địa phương 3 miền hỗ trợ tìm kiếm nguyên liệu (custom món / tạo món cá nhân)
CREATE TABLE ingredient_aliases (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  alias         VARCHAR(150) NOT NULL,
  alias_norm    VARCHAR(150) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_alias_ingredient (ingredient_id, alias_norm),
  KEY ix_alias_norm (alias_norm),
  CONSTRAINT fk_alias_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE portion_units (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ingredient_id  BIGINT UNSIGNED NOT NULL,
  unit_name      VARCHAR(40)  NOT NULL,
  unit_norm      VARCHAR(40)  NOT NULL,
  size_label     ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
  grams_per_unit DECIMAL(7,1) NOT NULL,
  note           VARCHAR(150) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_portion (ingredient_id, unit_norm, size_label),
  CONSTRAINT fk_portion_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE CASCADE,
  CONSTRAINT ck_portion_positive CHECK (grams_per_unit > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dishes (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id BIGINT UNSIGNED NULL,
  dish_code     VARCHAR(60)  NULL,                       -- khớp nhãn mô hình AI; chỉ món hệ thống
  name          VARCHAR(150) NOT NULL,
  name_norm     VARCHAR(150) NOT NULL,
  origin        ENUM('system','custom','saved') NOT NULL,
  serving_label VARCHAR(60)  NOT NULL DEFAULT '1 phần',  -- khẩu phần chuẩn: "1 dĩa", "1 tô"...
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dishes_code (dish_code),
  UNIQUE KEY uq_dishes_owner_name (owner_user_id, name_norm),
  KEY ix_dishes_name_norm (name_norm),
  CONSTRAINT fk_dishes_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dish_ingredients (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dish_id       BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  grams         DECIMAL(7,1) NOT NULL,
  sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dish_ingredient (dish_id, ingredient_id),
  KEY ix_di_ingredient (ingredient_id),
  CONSTRAINT fk_di_dish FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE,
  CONSTRAINT fk_di_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE RESTRICT,
  CONSTRAINT ck_di_grams CHECK (grams BETWEEN 0.1 AND 2000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Nhật ký ăn uống: SNAPSHOT
-- ---------------------------------------------------------------------
CREATE TABLE meal_entries (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  entry_date      DATE            NOT NULL,
  meal_type       ENUM('breakfast','lunch','dinner','snack') NOT NULL,
  name            VARCHAR(150)    NOT NULL,
  source_kind     ENUM('ai','manual','saved') NOT NULL,
  source_dish_id  BIGINT UNSIGNED NULL,
  serving_factor  DECIMAL(5,2)    NULL,
  ai_dish_code    VARCHAR(60)     NULL,
  ai_confidence   DECIMAL(4,3)    NULL,
  image_path      VARCHAR(255)    NULL,                  -- chỉ khi Member chọn "Lưu ảnh vào nhật ký"
  total_kcal      DECIMAL(8,1)    NOT NULL,
  total_protein_g DECIMAL(8,1)    NOT NULL,
  total_carb_g    DECIMAL(8,1)    NOT NULL,
  total_fat_g     DECIMAL(8,1)    NOT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_meals_user_date (user_id, entry_date),
  CONSTRAINT fk_meals_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_meals_dish FOREIGN KEY (source_dish_id) REFERENCES dishes (id) ON DELETE SET NULL,
  CONSTRAINT ck_meals_totals CHECK (total_kcal >= 0 AND total_protein_g >= 0 AND total_carb_g >= 0 AND total_fat_g >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meal_entry_items (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id         BIGINT UNSIGNED NOT NULL,
  ingredient_id    BIGINT UNSIGNED NULL,
  ingredient_name  VARCHAR(150)    NOT NULL,             -- tên tại thời điểm ghi
  grams            DECIMAL(7,1)    NOT NULL,
  kcal             DECIMAL(8,1)    NOT NULL,
  protein_g        DECIMAL(8,1)    NOT NULL,
  carb_g           DECIMAL(8,1)    NOT NULL,
  fat_g            DECIMAL(8,1)    NOT NULL,
  nutrition_source ENUM('vdd','usda','manual','user') NOT NULL,   -- nhãn nguồn (NFR-05)
  line_origin      ENUM('recipe','description','manual') NOT NULL, -- công thức chuẩn / từ mô tả / thủ công
  sort_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_items_entry (entry_id),
  CONSTRAINT fk_items_entry FOREIGN KEY (entry_id) REFERENCES meal_entries (id) ON DELETE CASCADE,
  CONSTRAINT fk_items_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE SET NULL,
  CONSTRAINT ck_items_grams CHECK (grams BETWEEN 0.1 AND 2000),
  CONSTRAINT ck_items_nonneg CHECK (kcal >= 0 AND protein_g >= 0 AND carb_g >= 0 AND fat_g >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Nước, ảnh tạm, rate limit, phản hồi AI
-- ---------------------------------------------------------------------
CREATE TABLE water_logs (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id   BIGINT UNSIGNED NOT NULL,
  log_date  DATE            NOT NULL,
  amount_ml INT UNSIGNED    NOT NULL,
  logged_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_water_user_date (user_id, log_date),
  CONSTRAINT fk_water_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_water_positive CHECK (amount_ml > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE draft_images (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  draft_token      CHAR(32)        NOT NULL,
  user_id          BIGINT UNSIGNED NOT NULL,
  file_path        VARCHAR(255)    NOT NULL,
  keep_in_log      TINYINT(1)      NOT NULL DEFAULT 0,
  consent_training TINYINT(1)      NOT NULL DEFAULT 0,
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at       DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_draft_token (draft_token),
  KEY ix_draft_expires (expires_at),
  CONSTRAINT fk_draft_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limit_hits (
  id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  bucket  VARCHAR(40)     NOT NULL,                      -- ví dụ 'analyze'
  hit_at  DATETIME(3)     NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY ix_rl_lookup (user_id, bucket, hit_at),
  CONSTRAINT fk_rl_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_feedback (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              BIGINT UNSIGNED NOT NULL,
  model_version        VARCHAR(40)  NOT NULL,
  predicted_dish_code  VARCHAR(60)  NULL,
  predicted_confidence DECIMAL(4,3) NULL,
  corrected_dish_code  VARCHAR(60)  NULL,
  corrected_items      JSON         NULL,
  image_path           VARCHAR(255) NULL,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_feedback_user (user_id),
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

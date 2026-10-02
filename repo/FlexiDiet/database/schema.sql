-- =============================================================================
-- CSDL FLEXIDIET - SMART DYNAMIC NUTRITION & WORKOUT PLATFORM
-- Thiết kế theo mô hình 2 tầng dữ liệu (System Catalog vs Personal Catalog)
-- và cơ chế Snapshot bất biến cho Nhật ký (theo quyết định D22, D23, D25)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `flexidiet_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `flexidiet_db`;

-- 1. Bảng Tài khoản Người dùng
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('MEMBER', 'ADMIN') DEFAULT 'MEMBER',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Bảng Hồ sơ Thể chất & Mục tiêu Năng lượng
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `gender` ENUM('male', 'female') NOT NULL,
    `age` INT NOT NULL,
    `height_cm` DECIMAL(5,2) NOT NULL,
    `weight_kg` DECIMAL(5,2) NOT NULL,
    `activity_level` VARCHAR(50) DEFAULT 'sedentary', -- Hệ số sinh hoạt PAL (1.2 -> 1.9)
    `goal_type` ENUM('lose', 'maintain', 'gain', 'recomp') DEFAULT 'maintain',
    `calorie_adjustment` INT DEFAULT 0, -- vd: -500, 0, +400
    `bmr_formula` ENUM('mifflin', 'katch') DEFAULT 'mifflin',
    `workout_policy` ENUM('full_100', 'partial_75', 'capped') DEFAULT 'full_100',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Bảng Nguyên liệu Gốc (System Ingredients - từ Viện Dinh Dưỡng / USDA)
CREATE TABLE IF NOT EXISTS `system_ingredients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_vi` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100),
    `serving_base_g` DECIMAL(6,2) DEFAULT 100.00, -- Tính trên 100g
    `calories_kcal` DECIMAL(6,2) NOT NULL,
    `protein_g` DECIMAL(6,2) DEFAULT 0,
    `carb_g` DECIMAL(6,2) DEFAULT 0,
    `fat_g` DECIMAL(6,2) DEFAULT 0,
    `is_verified` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- 4. Bảng Công thức Món Chuẩn (System Standard Recipes - 30-50 món Việt)
CREATE TABLE IF NOT EXISTS `system_recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dish_name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) UNIQUE, -- vd: 'pho_bo', 'com_tam'
    `default_serving_name` VARCHAR(50) DEFAULT '1 bát vừa (450g)',
    `default_weight_g` DECIMAL(6,2) NOT NULL,
    `total_calories` DECIMAL(6,2) NOT NULL,
    `total_protein` DECIMAL(6,2) NOT NULL,
    `total_carb` DECIMAL(6,2) NOT NULL,
    `total_fat` DECIMAL(6,2) NOT NULL,
    `image_url` VARCHAR(255)
) ENGINE=InnoDB;

-- 5. Bảng Danh mục Cá nhân (Personal Saved Foods & Custom Recipes)
CREATE TABLE IF NOT EXISTS `user_saved_foods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `food_name` VARCHAR(150) NOT NULL,
    `serving_size` VARCHAR(100) DEFAULT '1 phần',
    `weight_g` DECIMAL(6,2) NOT NULL,
    `calories` DECIMAL(6,2) NOT NULL,
    `protein` DECIMAL(6,2) DEFAULT 0,
    `carb` DECIMAL(6,2) DEFAULT 0,
    `fat` DECIMAL(6,2) DEFAULT 0,
    `source_type` ENUM('ai_scanned', 'custom_created') DEFAULT 'custom_created',
    `recipe_json` TEXT, -- Lưu chi tiết công thức nguyên liệu + gram
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Bảng Nhật ký Bữa ăn (Daily Meal Logs - Dạng Snapshot Bất Biến)
CREATE TABLE IF NOT EXISTS `meal_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `meal_type` ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    `food_name` VARCHAR(150) NOT NULL,
    `weight_g` DECIMAL(6,2) NOT NULL,
    `calories` DECIMAL(6,2) NOT NULL,
    `protein` DECIMAL(6,2) DEFAULT 0,
    `carb` DECIMAL(6,2) DEFAULT 0,
    `fat` DECIMAL(6,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Bảng Nhật ký Luyện tập (Workout Logs)
CREATE TABLE IF NOT EXISTS `workout_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `sport_name` VARCHAR(100) NOT NULL,
    `duration_minutes` INT NOT NULL,
    `distance_km` DECIMAL(5,2) DEFAULT NULL,
    `calories_burned` INT NOT NULL,
    `source` ENUM('device_input', 'met_calculated') DEFAULT 'met_calculated',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Bảng Lịch sử Cân nặng & Nước uống
CREATE TABLE IF NOT EXISTS `weight_water_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `weight_kg` DECIMAL(5,2),
    `water_liters` DECIMAL(4,2) DEFAULT 0,
    UNIQUE KEY `user_date_unique` (`user_id`, `log_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

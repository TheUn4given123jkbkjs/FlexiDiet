-- =====================================================================
-- FlexiDiet Seed: Tài khoản mẫu Admin và Member để kiểm thử hệ thống
-- Mật khẩu mặc định:
--   Admin: admin@flexidiet.vn / Admin@123456
--   Demo:  demo@flexidiet.vn  / Demo@123456
-- =====================================================================

USE flexidiet;
SET NAMES utf8mb4;

-- 1. Tài khoản Quản trị viên (Admin)
INSERT INTO users (id, email, password_hash, display_name, role)
VALUES (1, 'admin@flexidiet.vn', '$2y$10$kFNq39mjpAQh.U4mjmW14./4vnKx3vhjxdH0TJQ87Qrief68Ie23i', 'System Admin', 'admin')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), role = VALUES(role);

-- Profile cơ bản cho Admin
INSERT INTO user_profiles (user_id, sex, birth_date, height_cm, goal, target_weight_kg, bmr_formula, credit_policy, weekly_workout_goal)
VALUES (1, 'male', '1995-01-01', 175.0, 'maintain', 70.0, 'mifflin_st_jeor', 'partial', 3)
ON DUPLICATE KEY UPDATE height_cm = VALUES(height_cm);

INSERT INTO weight_logs (user_id, log_date, weight_kg)
VALUES (1, CURDATE(), 70.0)
ON DUPLICATE KEY UPDATE weight_kg = VALUES(weight_kg);

-- 2. Tài khoản Người dùng mẫu (Member)
INSERT INTO users (id, email, password_hash, display_name, role)
VALUES (2, 'demo@flexidiet.vn', '$2y$10$l2oNyb8Ww0YTvTcOUCDCQOk68gUkfnrxwoIaiNKn6PfA65WecMpaG', 'Nguyễn Văn Demo', 'member')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), role = VALUES(role);

-- Profile cho Member
INSERT INTO user_profiles (user_id, sex, birth_date, height_cm, goal, target_weight_kg, bmr_formula, credit_policy, weekly_workout_goal)
VALUES (2, 'male', '1998-05-15', 172.0, 'maintain', 68.0, 'mifflin_st_jeor', 'partial', 4)
ON DUPLICATE KEY UPDATE height_cm = VALUES(height_cm);

INSERT INTO weight_logs (user_id, log_date, weight_kg)
VALUES (2, CURDATE(), 68.5)
ON DUPLICATE KEY UPDATE weight_kg = VALUES(weight_kg);

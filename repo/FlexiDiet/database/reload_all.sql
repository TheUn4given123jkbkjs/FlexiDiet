-- =====================================================================
-- FlexiDiet: Master Database Loader (UTF-8 Clean)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/schema.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/01_dishes.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/02_ingredients.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/03_dish_ingredients.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/04_ingredient_aliases.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/05_portion_units.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/06_exercise_types.sql;
source d:/record_by_me/tdtu/WEB/Midterm/repo/FlexiDiet/database/seeds/07_demo_accounts.sql;

SET FOREIGN_KEY_CHECKS = 1;

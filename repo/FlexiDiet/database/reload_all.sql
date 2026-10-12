-- =====================================================================
-- FlexiDiet: Master Database Loader (UTF-8 Clean)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

source schema.sql;
source seeds/01_dishes.sql;
source seeds/02_ingredients.sql;
source seeds/03_dish_ingredients.sql;
source seeds/04_ingredient_aliases.sql;
source seeds/05_portion_units.sql;
source seeds/06_exercise_types.sql;
source seeds/07_demo_accounts.sql;

SET FOREIGN_KEY_CHECKS = 1;

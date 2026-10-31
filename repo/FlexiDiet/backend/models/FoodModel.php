<?php
declare(strict_types=1);

/**
 * FoodModel - Truy xuất và thao tác dữ liệu về Dishes, Ingredients, Recipes và Meal Entries
 */
class FoodModel {
    public static function searchDishes(PDO $pdo, int $uid, string $term): array {
        return all($pdo, '
            SELECT 
                d.id, d.name, d.name_norm, d.serving_label, d.dish_code, d.origin, d.image_url,
                ROUND(SUM(di.grams * i.kcal_100g / 100), 1) AS kcal,
                ROUND(SUM(di.grams * i.protein_100g / 100), 1) AS protein_g,
                ROUND(SUM(di.grams * i.carb_100g / 100), 1) AS carb_g,
                ROUND(SUM(di.grams * i.fat_100g / 100), 1) AS fat_g
            FROM dishes d
            JOIN dish_ingredients di ON di.dish_id = d.id
            JOIN ingredients i ON i.id = di.ingredient_id
            WHERE d.is_active = 1 
              AND i.is_active = 1 
              AND (d.owner_user_id IS NULL OR d.owner_user_id = ?)
              AND (d.name LIKE ? OR d.name_norm LIKE ? OR d.dish_code LIKE ?)
            GROUP BY d.id, d.name, d.name_norm, d.serving_label, d.dish_code, d.origin, d.image_url
            ORDER BY d.origin DESC, d.name
            LIMIT 300',
            [$uid, $term, $term, $term]
        );
    }

    public static function getCandidateDishesForRecommendation(PDO $pdo, int $uid): array {
        return all($pdo, '
            SELECT 
                d.id, d.name, d.name_norm, d.serving_label, d.dish_code, d.origin, d.image_url, d.owner_user_id,
                ROUND(SUM(di.grams * i.kcal_100g / 100), 1) AS kcal,
                ROUND(SUM(di.grams * i.protein_100g / 100), 1) AS protein_g,
                ROUND(SUM(di.grams * i.carb_100g / 100), 1) AS carb_g,
                ROUND(SUM(di.grams * i.fat_100g / 100), 1) AS fat_g
            FROM dishes d
            JOIN dish_ingredients di ON di.dish_id = d.id
            JOIN ingredients i ON i.id = di.ingredient_id
            WHERE d.is_active = 1 
              AND i.is_active = 1 
              AND (d.owner_user_id IS NULL OR d.owner_user_id = ?)
            GROUP BY d.id, d.name, d.name_norm, d.serving_label, d.dish_code, d.origin, d.image_url, d.owner_user_id
        ', [$uid]);
    }

    public static function getUserDishCountsInDays(PDO $pdo, int $uid, int $days): array {
        $rows = all($pdo, '
            SELECT COALESCE(m.source_dish_id, d.id) AS dish_id, COUNT(*) AS cnt
            FROM meal_entries m
            LEFT JOIN dishes d ON (d.dish_code = m.ai_dish_code OR d.name = m.name)
            WHERE m.user_id = ? 
              AND m.entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND COALESCE(m.source_dish_id, d.id) IS NOT NULL
            GROUP BY dish_id
        ', [$uid, $days]);
        return array_column($rows, 'cnt', 'dish_id');
    }

    public static function getUserAllTimeDishCounts(PDO $pdo, int $uid): array {
        $rows = all($pdo, '
            SELECT COALESCE(m.source_dish_id, d.id) AS dish_id, COUNT(*) AS cnt
            FROM meal_entries m
            LEFT JOIN dishes d ON (d.dish_code = m.ai_dish_code OR d.name = m.name)
            WHERE m.user_id = ? 
              AND COALESCE(m.source_dish_id, d.id) IS NOT NULL
            GROUP BY dish_id
        ', [$uid]);
        return array_column($rows, 'cnt', 'dish_id');
    }

    public static function getUserTotalMealsCount(PDO $pdo, int $uid): int {
        return (int)(one($pdo, 'SELECT COUNT(*) AS c FROM meal_entries WHERE user_id = ?', [$uid])['c'] ?? 0);
    }

    public static function getPeerDishCountsByGoal(PDO $pdo, string $goal, int $days = 30): array {
        $rows = all($pdo, '
            SELECT COALESCE(m.source_dish_id, d.id) AS dish_id, COUNT(*) AS cnt
            FROM meal_entries m
            JOIN user_profiles p ON p.user_id = m.user_id
            LEFT JOIN dishes d ON (d.dish_code = m.ai_dish_code OR d.name = m.name)
            WHERE p.goal = ? 
              AND m.entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND COALESCE(m.source_dish_id, d.id) IS NOT NULL
            GROUP BY dish_id
        ', [$goal, $days]);
        return array_column($rows, 'cnt', 'dish_id');
    }

    public static function findDishById(PDO $pdo, int $dishId, int $uid): ?array {
        return one($pdo, '
            SELECT d.id, d.name, d.serving_label, d.dish_code, d.origin, d.image_url 
            FROM dishes d 
            WHERE d.id = ? AND d.is_active = 1 AND (d.owner_user_id IS NULL OR d.owner_user_id = ?)',
            [$dishId, $uid]
        );
    }

    public static function getDishRecipeIngredients(PDO $pdo, int $dishId): array {
        return all($pdo, '
            SELECT 
                di.ingredient_id, i.name, di.grams, i.kcal_100g, i.protein_100g, i.carb_100g, i.fat_100g, i.source,
                (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id = i.id AND pu.size_label = "small" LIMIT 1) AS grams_small,
                (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id = i.id AND pu.size_label = "medium" LIMIT 1) AS grams_medium,
                (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id = i.id AND pu.size_label = "large" LIMIT 1) AS grams_large
            FROM dish_ingredients di 
            JOIN ingredients i ON i.id = di.ingredient_id
            WHERE di.dish_id = ? AND i.is_active = 1 
            ORDER BY di.sort_order',
            [$dishId]
        );
    }

    public static function searchIngredients(PDO $pdo, int $uid, string $term, string $normTerm): array {
        return all($pdo, '
            SELECT 
                i.id, i.name, i.kcal_100g, i.protein_100g, i.carb_100g, i.fat_100g, i.state, i.source,
                (SELECT ia.alias FROM ingredient_aliases ia WHERE ia.ingredient_id = i.id AND (ia.alias LIKE ? OR ia.alias_norm LIKE ?) LIMIT 1) AS matched_alias
            FROM ingredients i 
            WHERE i.is_active = 1 
              AND (i.owner_user_id IS NULL OR i.owner_user_id = ?) 
              AND (
                i.name LIKE ? 
                OR i.name_norm LIKE ? 
                OR EXISTS (
                    SELECT 1 FROM ingredient_aliases ia 
                    WHERE ia.ingredient_id = i.id 
                      AND (ia.alias LIKE ? OR ia.alias_norm LIKE ?)
                )
              ) 
            ORDER BY (i.name LIKE ? OR i.name_norm LIKE ?) DESC, i.name 
            LIMIT 30',
            [$term, $normTerm, $uid, $term, $normTerm, $term, $normTerm, $term, $normTerm]
        );
    }

    public static function findIngredientById(PDO $pdo, int $id): ?array {
        return one($pdo, '
            SELECT id, name, source, kcal_100g, protein_100g, carb_100g, fat_100g 
            FROM ingredients 
            WHERE id = ? AND is_active = 1',
            [$id]
        );
    }

    public static function findDuplicateDish(PDO $pdo, int $uid, string $nameNorm): ?array {
        return one($pdo, 'SELECT id FROM dishes WHERE owner_user_id = ? AND name_norm = ? AND is_active = 1', [$uid, $nameNorm]);
    }

    public static function createDish(PDO $pdo, int $uid, string $name, string $nameNorm, string $servingLabel, ?string $imageUrl = null): int {
        execSql($pdo, '
            INSERT INTO dishes (owner_user_id, name, name_norm, origin, serving_label, image_url, is_active) 
            VALUES (?, ?, ?, "saved", ?, ?, 1)',
            [$uid, $name, $nameNorm, $servingLabel, $imageUrl]
        );
        return (int)$pdo->lastInsertId();
    }

    public static function deleteCustomDish(PDO $pdo, int $dishId, int $uid): bool {
        $stmt = $pdo->prepare('DELETE FROM dishes WHERE id = ? AND owner_user_id = ? AND origin IN ("saved", "custom")');
        $stmt->execute([$dishId, $uid]);
        return $stmt->rowCount() > 0;
    }

    public static function addDishIngredient(PDO $pdo, int $dishId, int $ingredientId, float $grams, int $sortOrder): void {
        execSql($pdo, '
            INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) 
            VALUES (?, ?, ?, ?)',
            [$dishId, $ingredientId, $grams, $sortOrder]
        );
    }

    public static function listMeals(PDO $pdo, int $uid, string $day): array {
        return all($pdo, '
            SELECT 
                id, entry_date, meal_type, name, source_kind, source_dish_id, 
                serving_factor, total_kcal, total_protein_g, total_carb_g, total_fat_g, created_at 
            FROM meal_entries 
            WHERE user_id = ? AND entry_date = ? 
            ORDER BY created_at DESC, id DESC',
            [$uid, $day]
        );
    }

    public static function findMealById(PDO $pdo, int $mealId, int $uid): ?array {
        return one($pdo, 'SELECT * FROM meal_entries WHERE id = ? AND user_id = ?', [$mealId, $uid]);
    }

    public static function insertMealEntry(
        PDO $pdo,
        int $uid,
        string $day,
        string $mealType,
        string $name,
        string $sourceKind,
        ?int $dishId,
        ?float $servingFactor,
        ?string $aiDishCode,
        ?float $aiConfidence,
        ?string $imagePath,
        float $kcal,
        float $protein,
        float $carb,
        float $fat
    ): int {
        execSql($pdo, '
            INSERT INTO meal_entries (
                user_id, entry_date, meal_type, name, source_kind, source_dish_id, 
                serving_factor, ai_dish_code, ai_confidence, image_path, 
                total_kcal, total_protein_g, total_carb_g, total_fat_g
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $uid, $day, $mealType, $name, $sourceKind, $dishId,
                $servingFactor, $aiDishCode, $aiConfidence, $imagePath,
                $kcal, $protein, $carb, $fat
            ]
        );
        return (int)$pdo->lastInsertId();
    }

    public static function insertMealEntryItem(
        PDO $pdo,
        int $entryId,
        int $ingredientId,
        string $name,
        float $grams,
        float $kcal,
        float $protein,
        float $carb,
        float $fat,
        string $source,
        int $sortOrder
    ): void {
        execSql($pdo, '
            INSERT INTO meal_entry_items (
                entry_id, ingredient_id, ingredient_name, grams, kcal, 
                protein_g, carb_g, fat_g, nutrition_source, line_origin, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "recipe", ?)',
            [$entryId, $ingredientId, $name, $grams, $kcal, $protein, $carb, $fat, $source, $sortOrder]
        );
    }

    public static function deleteMealEntry(PDO $pdo, int $mealId, int $uid): bool {
        $stmt = $pdo->prepare('DELETE FROM meal_entries WHERE id = ? AND user_id = ?');
        $stmt->execute([$mealId, $uid]);
        return $stmt->rowCount() > 0;
    }

    public static function recordAiFeedback(
        PDO $pdo,
        int $uid,
        string $predCode,
        ?float $predConf,
        string $correctedCode,
        ?string $imagePath
    ): void {
        try {
            execSql($pdo, '
                INSERT INTO ai_feedback (user_id, model_version, predicted_dish_code, predicted_confidence, corrected_dish_code, image_path, created_at) 
                VALUES (?, "YOLOv10m-VietFood67", ?, ?, ?, ?, NOW())',
                [$uid, $predCode, $predConf, $correctedCode, $imagePath]
            );
        } catch (Throwable) {
            // Ignore optional AI feedback logging errors to prevent breaking meal entry
        }
    }
}


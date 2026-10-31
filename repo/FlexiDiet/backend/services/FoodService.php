<?php
declare(strict_types=1);

/**
 * FoodService - Xử lý nghiệp vụ dinh dưỡng, công thức món, tính calo và lưu bữa ăn
 */
class FoodService {
    public static function normalizeVietnamese(string $str): string {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $trans = [
            'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
            'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d',
            'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
            'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
            'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
            'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
            'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
            'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y'
        ];
        return strtr($str, $trans);
    }

    public static function searchFoods(PDO $pdo, int $uid, string $query): array {
        $q = trim($query);
        if (mb_strlen($q) > 100) fail(422, 'INVALID_QUERY', 'Từ khóa quá dài.');
        $term = '%' . $q . '%';
        return FoodModel::searchDishes($pdo, $uid, $term);
    }

    public static function getRecipe(PDO $pdo, int $uid, int $dishId): array {
        $dish = FoodModel::findDishById($pdo, $dishId, $uid);
        if (!$dish) fail(404, 'DISH_NOT_FOUND', 'Không tìm thấy món ăn.');
        $ingredients = FoodModel::getDishRecipeIngredients($pdo, $dishId);
        return ['dish' => $dish, 'ingredients' => $ingredients];
    }

    public static function searchIngredients(PDO $pdo, int $uid, string $query): array {
        $q = trim($query);
        if (mb_strlen($q) > 100) fail(422, 'INVALID_QUERY', 'Từ khóa quá dài.');
        $term = '%' . $q . '%';
        $normTerm = '%' . self::normalizeVietnamese($q) . '%';
        return FoodModel::searchIngredients($pdo, $uid, $term, $normTerm);
    }

    public static function createCustomDish(PDO $pdo, int $uid, array $params): array {
        $name = requiredString($params, 'name');
        $label = isset($params['serving_label']) ? requiredString($params, 'serving_label', 60) : '1 phần';
        $items = $params['ingredients'] ?? [];
        if (!is_array($items) || empty($items)) {
            fail(422, 'EMPTY_INGREDIENTS', 'Món ăn phải có ít nhất một nguyên liệu.');
        }

        $norm = self::normalizeVietnamese($name);

        $pdo->beginTransaction();
        try {
            $dup = FoodModel::findDuplicateDish($pdo, $uid, $norm);
            if ($dup) {
                fail(409, 'DISH_EXISTS', 'Bạn đã có món ăn với tên này trong danh mục cá nhân.');
            }

            $dishId = FoodModel::createDish($pdo, $uid, $name, $norm, $label);

            foreach ($items as $idx => $it) {
                $ingId = (int)num($it, 'ingredient_id', 1, 999999999);
                $grams = num($it, 'grams', 0.1, 2000);
                $check = FoodModel::findIngredientById($pdo, $ingId);
                if (!$check) {
                    fail(422, 'INVALID_INGREDIENT', 'Nguyên liệu không tồn tại.');
                }
                FoodModel::addDishIngredient($pdo, $dishId, $ingId, $grams, $idx);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return ['id' => $dishId, 'name' => $name, 'serving_label' => $label, 'origin' => 'saved'];
    }

    public static function listMeals(PDO $pdo, int $uid, string $date): array {
        $day = dateValue($date);
        return FoodModel::listMeals($pdo, $uid, $day);
    }

    public static function createMeal(PDO $pdo, int $uid, array $params): array {
        $day = dateValue($params['entry_date'] ?? today());
        $meal = choice($params, 'meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
        $dishId = isset($params['dish_id']) ? (int)num($params, 'dish_id', 1, 999999999) : null;
        $items = isset($params['items']) && is_array($params['items']) ? $params['items'] : null;
        $recipeItems = [];

        if ($items !== null) {
            $name = requiredString($params, 'name');
            if (empty($items)) fail(422, 'EMPTY_ITEMS', 'Món ăn phải có ít nhất một nguyên liệu.');
            $totals = [0, 0, 0, 0];
            foreach ($items as $idx => $it) {
                $ingId = (int)num($it, 'ingredient_id', 1, 999999999);
                $grams = num($it, 'grams', 0.1, 2000);
                $ing = FoodModel::findIngredientById($pdo, $ingId);
                if (!$ing) fail(422, 'INVALID_INGREDIENT', 'Nguyên liệu không tồn tại hoặc đã bị khóa.');
                $ingKcal = round($grams / 100 * (float)$ing['kcal_100g'], 1);
                $ingProt = round($grams / 100 * (float)$ing['protein_100g'], 1);
                $ingCarb = round($grams / 100 * (float)$ing['carb_100g'], 1);
                $ingFat = round($grams / 100 * (float)$ing['fat_100g'], 1);
                $totals[0] += $ingKcal;
                $totals[1] += $ingProt;
                $totals[2] += $ingCarb;
                $totals[3] += $ingFat;
                $recipeItems[] = [
                    'ingredient_id' => $ingId,
                    'name' => $ing['name'],
                    'grams' => $grams,
                    'kcal' => $ingKcal,
                    'protein_g' => $ingProt,
                    'carb_g' => $ingCarb,
                    'fat_g' => $ingFat,
                    'source' => $ing['source']
                ];
            }
            [$kcal, $protein, $carb, $fat] = array_map(static fn($v) => round($v, 1), $totals);
            $factor = null;
        } elseif ($dishId) {
            $dish = FoodModel::findDishById($pdo, $dishId, $uid);
            if (!$dish) fail(404, 'DISH_NOT_FOUND', 'Không tìm thấy món ăn.');
            $factor = num($params, 'serving_factor', 0.1, 10, 1);
            $recipeItemsRaw = all($pdo, '
                SELECT di.ingredient_id, i.name, i.source, di.grams, i.kcal_100g, i.protein_100g, i.carb_100g, i.fat_100g 
                FROM dish_ingredients di 
                JOIN ingredients i ON i.id = di.ingredient_id 
                WHERE di.dish_id = ? AND i.is_active = 1 
                ORDER BY di.sort_order',
                [$dishId]
            );
            if (!$recipeItemsRaw) fail(422, 'DISH_WITHOUT_RECIPE', 'Món ăn chưa có công thức dinh dưỡng. Hãy ghi thủ công.');
            $name = $dish['name'];
            $totals = [0, 0, 0, 0];
            foreach ($recipeItemsRaw as $it) {
                $grams = round((float)$it['grams'] * $factor, 1);
                $ingKcal = round($grams / 100 * (float)$it['kcal_100g'], 1);
                $ingProt = round($grams / 100 * (float)$it['protein_100g'], 1);
                $ingCarb = round($grams / 100 * (float)$it['carb_100g'], 1);
                $ingFat = round($grams / 100 * (float)$it['fat_100g'], 1);
                $totals[0] += $ingKcal;
                $totals[1] += $ingProt;
                $totals[2] += $ingCarb;
                $totals[3] += $ingFat;
                $recipeItems[] = [
                    'ingredient_id' => $it['ingredient_id'],
                    'name' => $it['name'],
                    'grams' => $grams,
                    'kcal' => $ingKcal,
                    'protein_g' => $ingProt,
                    'carb_g' => $ingCarb,
                    'fat_g' => $ingFat,
                    'source' => $it['source']
                ];
            }
            [$kcal, $protein, $carb, $fat] = array_map(static fn($v) => round($v, 1), $totals);
        } else {
            $factor = null;
            $name = requiredString($params, 'name');
            $kcal = num($params, 'total_kcal', 0, 5000);
            $protein = num($params, 'total_protein_g', 0, 500);
            $carb = num($params, 'total_carb_g', 0, 800);
            $fat = num($params, 'total_fat_g', 0, 500);
        }

        $pdo->beginTransaction();
        try {
            if ($day === today()) {
                ensureBudget($pdo, $uid, $day);
                $budget = one($pdo, 'SELECT target_kcal, exercise_credit_kcal FROM daily_budgets WHERE user_id = ? AND budget_date = ? FOR UPDATE', [$uid, $day]);
                $existingMeals = all($pdo, 'SELECT total_kcal FROM meal_entries WHERE user_id = ? AND entry_date = ? FOR UPDATE', [$uid, $day]);
                $consumed = array_sum(array_map(static fn($meal) => (float)$meal['total_kcal'], $existingMeals));
                $remaining = (float)$budget['target_kcal'] + (float)$budget['exercise_credit_kcal'] - $consumed;
                if ($kcal > $remaining + 0.0001 && ($params['confirm_over_budget'] ?? false) !== true) {
                    fail(409, 'BUDGET_EXCEEDED', 'Món ăn sẽ vượt ngân sách calo hôm nay. Vui lòng xác nhận nếu vẫn muốn thêm.');
                }
            }

            $sourceKind = in_array($params['source_kind'] ?? '', ['ai', 'manual', 'saved'], true)
                ? (string)$params['source_kind']
                : (!empty($params['ai_dish_code']) ? 'ai' : 'manual');
            $aiDishCode = !empty($params['ai_dish_code']) ? (string)$params['ai_dish_code'] : null;
            $aiConfidence = isset($params['ai_confidence']) && is_numeric($params['ai_confidence']) ? (float)$params['ai_confidence'] : null;
            $imagePath = !empty($params['image_path']) ? (string)$params['image_path'] : null;

            $id = FoodModel::insertMealEntry(
                $pdo, $uid, $day, $meal, $name, $sourceKind, $dishId,
                $factor, $aiDishCode, $aiConfidence, $imagePath,
                $kcal, $protein, $carb, $fat
            );

            foreach ($recipeItems as $i => $it) {
                FoodModel::insertMealEntryItem(
                    $pdo, $id, $it['ingredient_id'], $it['name'],
                    $it['grams'], $it['kcal'], $it['protein_g'],
                    $it['carb_g'], $it['fat_g'], $it['source'], $i
                );
            }

            if (!empty($params['ai_predicted_dish_code'])) {
                $predCode = (string)$params['ai_predicted_dish_code'];
                $predConf = isset($params['ai_predicted_confidence']) ? (float)$params['ai_predicted_confidence'] : $aiConfidence;
                $correctedCode = $aiDishCode ?? ($dish['dish_code'] ?? $name);
                FoodModel::recordAiFeedback($pdo, $uid, $predCode, $predConf, $correctedCode, $imagePath);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return FoodModel::findMealById($pdo, $id, $uid);
    }

    public static function deleteMeal(PDO $pdo, int $uid, int $mealId): void {
        $deleted = FoodModel::deleteMealEntry($pdo, $mealId, $uid);
        if (!$deleted) {
            fail(404, 'NOT_FOUND', 'Không tìm thấy món đã ghi.');
        }
    }
}

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

    public static function deleteCustomDish(PDO $pdo, int $dishId, int $uid): array {
        $dish = FoodModel::findDishById($pdo, $dishId, $uid);
        if (!$dish) {
            fail(404, 'NOT_FOUND', 'Không tìm thấy món ăn này hoặc bạn không có quyền xóa.');
        }
        if ($dish['origin'] === 'system') {
            fail(403, 'FORBIDDEN', 'Không thể xóa các món ăn chuẩn của hệ thống.');
        }

        $ok = FoodModel::deleteCustomDish($pdo, $dishId, $uid);
        if (!$ok) {
            fail(400, 'DELETE_FAILED', 'Không thể xóa món ăn này.');
        }

        return ['success' => true, 'deleted_id' => $dishId, 'name' => $dish['name']];
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

    public static function getSmartSuggestions(
        PDO $pdo,
        int $uid,
        string $tag = 'all',
        string $meal = 'all',
        string $origin = 'all'
    ): array {
        // Tương thích ngược nếu tag truyền vào là giá trị của origin hoặc meal
        if (in_array($tag, ['viet', 'custom'], true) && $origin === 'all') {
            $origin = $tag;
            $tag = 'all';
        } elseif (in_array($tag, ['breakfast', 'main_meal', 'snack'], true) && $meal === 'all') {
            $meal = $tag;
            $tag = 'all';
        }

        // 1. Lấy thông tin mục tiêu và ngân sách hôm nay
        ensureBudget($pdo, $uid, today());
        $budget = budgetView($pdo, $uid, today());
        $profile = profileRow($pdo, $uid);

        $targetKcal = (float)$budget['target_kcal'];
        $creditKcal = (float)$budget['exercise_credit_kcal'];
        $consumedKcal = (float)$budget['consumed_kcal'];
        $remainingKcal = round(($targetKcal + $creditKcal) - $consumedKcal, 1);
        $goal = (string)($profile['goal'] ?? 'lose');

        // 2. Lấy không gian ứng viên (67 món hệ thống + món custom của chính user)
        $candidates = FoodModel::getCandidateDishesForRecommendation($pdo, $uid);
        if (empty($candidates)) {
            return [
                'budget' => ['remaining_kcal' => $remainingKcal, 'target_kcal' => $targetKcal, 'consumed_kcal' => $consumedKcal, 'goal' => $goal],
                'suggestions' => []
            ];
        }

        // 3. Multi-filter Tiêu chí 1: Nguồn gốc món ăn ($origin)
        if ($origin === 'custom') {
            $candidates = array_values(array_filter($candidates, fn($d) => $d['origin'] === 'custom'));
        } elseif ($origin === 'system' || $origin === 'viet') {
            $candidates = array_values(array_filter($candidates, fn($d) => $d['origin'] === 'system'));
        }

        if (empty($candidates)) {
            return [
                'budget' => ['remaining_kcal' => $remainingKcal, 'target_kcal' => $targetKcal, 'consumed_kcal' => $consumedKcal, 'goal' => $goal],
                'suggestions' => []
            ];
        }

        // 4. Lấy dữ liệu lịch sử cá nhân và xu hướng cộng đồng
        $user7dMap = FoodModel::getUserDishCountsInDays($pdo, $uid, 7);
        $userAllMap = FoodModel::getUserAllTimeDishCounts($pdo, $uid);
        $userTotalMeals = FoodModel::getUserTotalMealsCount($pdo, $uid);
        $peerMap = FoodModel::getPeerDishCountsByGoal($pdo, $goal, 30);
        $maxPeerCount = max(1, ...array_values($peerMap) ?: [1]);

        // 5. Giai đoạn 1: Sàng lọc theo Calo (Cascade Filter by Kcal)
        $filtered = [];
        $kcalCap = ($remainingKcal > 0) ? max(350.0, 1.3 * $remainingKcal) : 250.0;
        if ($tag === 'high_energy' || $meal === 'main_meal') {
            $kcalCap = 1200.0; // Nới lỏng trần calo nếu tìm món năng lượng cao hoặc bữa chính
        }
        foreach ($candidates as $dish) {
            $kcal = (float)$dish['kcal'];
            if ($remainingKcal <= 0 && $tag !== 'high_energy') {
                if ($kcal <= 250.0) $filtered[] = $dish;
            } else {
                if ($kcal <= $kcalCap) $filtered[] = $dish;
            }
        }
        if (empty($filtered)) {
            $filtered = $candidates;
        }

        // 6. Multi-filter Tiêu chí 2: Mục tiêu Dinh dưỡng ($tag)
        if ($tag === 'high_prot') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['protein_g'] >= 25.0));
        } elseif ($tag === 'low_cal') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] <= 350.0));
        } elseif ($tag === 'low_carb') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['carb_g'] <= 30.0));
        } elseif ($tag === 'low_fat') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['fat_g'] <= 10.0));
        } elseif ($tag === 'high_energy') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] >= 550.0));
        }

        // 7. Multi-filter Tiêu chí 3: Phân loại Bữa ăn ($meal)
        if ($meal === 'breakfast') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] >= 200.0 && (float)$d['kcal'] <= 550.0));
        } elseif ($meal === 'main_meal' || $meal === 'lunch') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] >= 450.0));
        } elseif ($meal === 'snack') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] <= 300.0));
        }

        if (empty($filtered)) {
            return [
                'budget' => ['remaining_kcal' => $remainingKcal, 'target_kcal' => $targetKcal, 'consumed_kcal' => $consumedKcal, 'goal' => $goal],
                'suggestions' => []
            ];
        }

        // 8. Giai đoạn 2: Chấm điểm 3 thành phần (Component Scoring)
        $idealMealKcal = $remainingKcal > 0 ? min($remainingKcal, max(300.0, $targetKcal * 0.35)) : 150.0;
        $scored = [];

        // Bản đồ ảnh đại diện mẫu cho các món ăn
        $imageMap = [
            'pho_bo' => 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=500&auto=format&fit=crop&q=80',
            'com_tam' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80',
            'bun_cha' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80',
            'banh_mi' => 'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=500&auto=format&fit=crop&q=80',
            'salad_ga' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',
            'goi_cuon' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80',
            'banh_cuon' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=500&auto=format&fit=crop&q=80',
            'bun_bo_hue' => 'https://images.unsplash.com/photo-1594041680534-e8c8cdebd659?w=500&auto=format&fit=crop&q=80',
            'canh_chua' => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=500&auto=format&fit=crop&q=80'
        ];
        $defaultImage = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80';

        foreach ($filtered as $dish) {
            $id = (int)$dish['id'];
            $kcal = (float)$dish['kcal'];
            $prot = (float)$dish['protein_g'];
            $carb = (float)$dish['carb_g'];
            $fat = (float)$dish['fat_g'];
            $isCustom = ($dish['origin'] === 'custom');

            // 5.1 Điểm dinh dưỡng (S_nutri)
            $sCal = max(0.0, 10.0 - 10.0 * (abs($kcal - $idealMealKcal) / max($idealMealKcal, 200.0)));
            $sMacro = 8.0;
            if ($goal === 'build_muscle') {
                $sMacro = min(10.0, ($prot / 30.0) * 10.0);
            } elseif ($goal === 'lose') {
                $protScore = min(6.0, ($prot / 20.0) * 6.0);
                $fatPenalty = ($fat <= 15.0) ? 4.0 : max(0.0, 4.0 - ($fat - 15.0) * 0.3);
                $sMacro = min(10.0, $protScore + $fatPenalty);
            } elseif ($goal === 'gain') {
                $sMacro = min(10.0, ($kcal / 500.0) * 5.0 + ($carb / 50.0) * 5.0);
            }
            $sNutri = round(0.5 * $sCal + 0.5 * $sMacro, 2);

            // 5.2 Điểm sở thích cá nhân (S_history) theo đúng quy tắc
            if ($isCustom) {
                $timesAll = (int)($userAllMap[$id] ?? 0);
                if ($timesAll >= 3) $sHist = 10.0;
                elseif ($timesAll >= 1) $sHist = 9.0;
                else $sHist = 8.0; // Khởi điểm mặc định 8 điểm
            } else {
                $times7d = (int)($user7dMap[$id] ?? 0);
                if ($times7d >= 3) $sHist = 10.0; // Món khoái khẩu tuần qua
                elseif ($times7d >= 1) $sHist = 6.0; // Đã ăn 1-2 lần
                else $sHist = 4.0; // Chưa từng ăn
            }

            // 5.3 Điểm xu hướng cộng đồng (S_peer)
            if ($isCustom) {
                $sPeer = 0.0;
            } else {
                $peerCnt = (int)($peerMap[$id] ?? 0);
                if (empty($peerMap)) {
                    $sPeer = 5.0; // Mức trung tính khi hệ thống chưa có dữ liệu đám đông
                } else {
                    $sPeer = $peerCnt > 0 ? round(($peerCnt / $maxPeerCount) * 10.0, 2) : 4.0;
                }
            }

            // 6. Giai đoạn 3: Adaptive Weighting & Switching
            if ($isCustom) {
                $sFinal = round(0.6 * $sNutri + 0.4 * $sHist, 2);
            } else {
                if ($userTotalMeals < 3) {
                    // Chế độ Cold-Start cho người dùng mới
                    $sFinal = round(0.7 * $sNutri + 0.3 * $sPeer, 2);
                } else {
                    $sFinal = round(0.5 * $sNutri + 0.3 * $sHist + 0.2 * $sPeer, 2);
                }
            }

            // 7. Giai đoạn 4: Ranking & Match Reason
            $matchPct = (int)round(min(99.0, max(50.0, $sFinal * 10.0)));

            $reasonParts = [];
            if ($tag === 'high_prot') $reasonParts[] = "Giàu đạm ({$prot}g Protein)";
            elseif ($tag === 'low_carb') $reasonParts[] = "Ít tinh bột ({$carb}g Carb)";
            elseif ($tag === 'low_fat') $reasonParts[] = "Ít chất béo ({$fat}g Fat)";
            elseif ($tag === 'low_cal') $reasonParts[] = "Thanh nhẹ ({$kcal} kcal)";
            elseif ($tag === 'high_energy') $reasonParts[] = "Năng lượng dồi dào ({$kcal} kcal)";

            if ($meal === 'breakfast') $reasonParts[] = "thích hợp điểm tâm sáng";
            elseif ($meal === 'main_meal' || $meal === 'lunch') $reasonParts[] = "no lâu cho bữa chính";
            elseif ($meal === 'snack') $reasonParts[] = "cho bữa ăn nhẹ";

            if ($origin === 'custom' || $isCustom) $reasonParts[] = "(món ruột tự tạo)";

            if (!empty($reasonParts)) {
                $reason = implode(' — ', $reasonParts);
            } elseif ($prot >= 25.0) {
                $reason = "Giàu đạm ({$prot}g Protein) — Tối ưu phát triển cơ bắp";
            } elseif ($remainingKcal <= 0) {
                $reason = "Thanh đạm ({$kcal} kcal) — Giúp kiểm soát năng lượng";
            } elseif (($user7dMap[$id] ?? 0) >= 3) {
                $reason = "Món khoái khẩu tuần qua của bạn";
            } elseif ($goal === 'lose') {
                $reason = "Món thanh nhẹ ({$kcal} kcal) — Tối ưu giảm cân";
            } else {
                $reason = "Cân đối dinh dưỡng, được cộng đồng ưa chuộng";
            }

            $code = (string)($dish['dish_code'] ?? '');
            $img = $imageMap[$code] ?? $defaultImage;

            $scored[] = [
                'dish_id' => $id,
                'name' => $dish['name'],
                'dish_code' => $code,
                'origin' => $dish['origin'],
                'serving_label' => $dish['serving_label'],
                'kcal' => $kcal,
                'protein_g' => $prot,
                'carb_g' => $carb,
                'fat_g' => $fat,
                'match_pct' => $matchPct,
                'match_reason' => $reason,
                'image_url' => $img,
                'score_final' => $sFinal
            ];
        }

        // Sắp xếp điểm số từ cao xuống thấp và lấy Top 6
        usort($scored, fn($a, $b) => $b['score_final'] <=> $a['score_final']);
        $top6 = array_slice($scored, 0, 6);

        return [
            'budget' => [
                'remaining_kcal' => $remainingKcal,
                'target_kcal' => $targetKcal,
                'consumed_kcal' => $consumedKcal,
                'goal' => $goal
            ],
            'filter_tag' => $tag,
            'suggestions' => $top6
        ];
    }
}


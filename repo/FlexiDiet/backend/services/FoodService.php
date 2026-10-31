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
        $imageUrl = isset($params['image_url']) ? trim((string)$params['image_url']) : null;
        if ($imageUrl === '') $imageUrl = null;

        $pdo->beginTransaction();
        try {
            $dup = FoodModel::findDuplicateDish($pdo, $uid, $norm);
            if ($dup) {
                fail(409, 'DISH_EXISTS', 'Bạn đã có món ăn với tên này trong danh mục cá nhân.');
            }

            $dishId = FoodModel::createDish($pdo, $uid, $name, $norm, $label, $imageUrl);

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
        if (in_array($tag, ['viet', 'custom', 'saved'], true) && $origin === 'all') {
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
        if ($origin === 'custom' || $origin === 'saved') {
            $candidates = array_values(array_filter($candidates, fn($d) => in_array($d['origin'], ['custom', 'saved'], true) || (!empty($d['owner_user_id']) && (int)$d['owner_user_id'] === $uid)));
        } elseif ($origin === 'system' || $origin === 'viet') {
            $candidates = array_values(array_filter($candidates, fn($d) => $d['origin'] === 'system' && empty($d['owner_user_id'])));
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
        if ($origin === 'custom' || $origin === 'saved') {
            // Khi lọc riêng món ruột tự tạo, giữ trọn vẹn các món của user để chấm điểm và xếp hạng
            $filtered = $candidates;
        } else {
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
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] >= 600.0));
        } elseif ($tag === 'balanced') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['protein_g'] >= 15.0 && (float)$d['carb_g'] >= 25.0 && (float)$d['fat_g'] <= 25.0));
        } elseif ($tag === 'light_digestion') {
            $filtered = array_values(array_filter($filtered, fn($d) => (float)$d['kcal'] <= 350.0 && (float)$d['fat_g'] <= 8.0));
        } elseif ($tag === 'vegetarian') {
            $meatWords = ['thịt', 'bò', 'heo', 'gà', 'chả', 'lòng', 'cua', 'cá', 'tôm', 'mực', 'ốc', 'ba tê', 'pate'];
            $filtered = array_values(array_filter($filtered, function($d) use ($meatWords) {
                $nameLower = mb_strtolower($d['name']);
                foreach ($meatWords as $w) {
                    if (str_contains($nameLower, $w)) return false;
                }
                return true;
            }));
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
            'banh_canh' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c4/Banh-Canh-Noodle-Soup.jpg/500px-Banh-Canh-Noodle-Soup.jpg',
            'banh_chung' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/e/ec/Banh_chung_vuong.jpg/500px-Banh_chung_vuong.jpg',
            'banh_cuon' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a7/Banh_cuon.jpg/500px-Banh_cuon.jpg',
            'banh_khot' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/89/B%C3%A1nh_kh%E1%BB%8Dt.jpg/500px-B%C3%A1nh_kh%E1%BB%8Dt.jpg',
            'banh_mi' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/e/e2/13-08-31-Kochtreffen-Wien-RalfR-N3S_7849-024.jpg/500px-13-08-31-Kochtreffen-Wien-RalfR-N3S_7849-024.jpg',
            'banh_trang' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/36/B%C3%A1nh_tr%C3%A1ng_1.jpg/500px-B%C3%A1nh_tr%C3%A1ng_1.jpg',
            'banh_trang_tron' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/63/Vietnamese_%22banh_trang_tron%22.JPG/500px-Vietnamese_%22banh_trang_tron%22.JPG',
            'banh_xeo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a5/B%C3%A1nh_x%C3%A8o_with_n%C6%B0%E1%BB%9Bc_m%E1%BA%AFm.jpg/500px-B%C3%A1nh_x%C3%A8o_with_n%C6%B0%E1%BB%9Bc_m%E1%BA%AFm.jpg',
            'bo_kho' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/16/Bo_kho_mien_Bac.jpg/500px-Bo_kho_mien_Bac.jpg',
            'bo_la_lot' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/95/B%C3%B2_n%C6%B0%E1%BB%9Bng_l%C3%A1_l%E1%BB%91t_t%E1%BA%A1i_Manchester.jpg/500px-B%C3%B2_n%C6%B0%E1%BB%9Bng_l%C3%A1_l%E1%BB%91t_t%E1%BA%A1i_Manchester.jpg',
            'bun' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6f/Rice_vermicelli.jpg/500px-Rice_vermicelli.jpg',
            'bun_bo_hue' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/00/Bun-Bo-Hue-from-Huong-Giang-2011.jpg/500px-Bun-Bo-Hue-from-Huong-Giang-2011.jpg',
            'bun_cha' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/8b/B%C3%BAn_ch%E1%BA%A3_Th%E1%BB%A5y_Khu%C3%AA.jpg/500px-B%C3%BAn_ch%E1%BA%A3_Th%E1%BB%A5y_Khu%C3%AA.jpg',
            'bun_mam' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/80/B%C3%BAn_m%E1%BA%AFm.jpg/500px-B%C3%BAn_m%E1%BA%AFm.jpg',
            'bun_rieu' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/2/2a/B%C3%BAn_ri%C3%AAu_cua_n%C6%B0%E1%BB%9Bc.jpg/500px-B%C3%BAn_ri%C3%AAu_cua_n%C6%B0%E1%BB%9Bc.jpg',
            'bun_dau' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/11/B%C3%BAn_%C4%91%E1%BA%ADu_m%E1%BA%AFm_t%C3%B4m_%282019%29.jpg/500px-B%C3%BAn_%C4%91%E1%BA%ADu_m%E1%BA%AFm_t%C3%B4m_%282019%29.jpg',
            'ca_chua' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/88/Bright_red_tomato_and_cross_section02.jpg/500px-Bright_red_tomato_and_cross_section02.jpg',
            'ca_phao' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c5/Solanum_macrocarpon_HabitusFruits_BotGardBln0906a.JPG/500px-Solanum_macrocarpon_HabitusFruits_BotGardBln0906a.JPG',
            'canh' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a0/Canhchua2.jpg/500px-Canhchua2.jpg',
            'chanh' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/0d/16-09-17-WikiLovesCocktails-Zutaten-Img0144.jpg/500px-16-09-17-WikiLovesCocktails-Zutaten-Img0144.jpg',
            'cua' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/3e/Blue_crab_on_market_in_Piraeus_-_Callinectes_sapidus_Rathbun_20020819-317.jpg/500px-Blue_crab_on_market_in_Piraeus_-_Callinectes_sapidus_Rathbun_20020819-317.jpg',
            'ca_rot' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/34/Baby_carrots_-_jules.jpg/500px-Baby_carrots_-_jules.jpg',
            'com' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/39/Meshi.jpg/500px-Meshi.jpg',
            'com_tam' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/b/b0/C%C6%A1m_T%E1%BA%A5m%2C_Da_Nang%2C_Vietnam.jpg/500px-C%C6%A1m_T%E1%BA%A5m%2C_Da_Nang%2C_Vietnam.jpg',
            'dua_leo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a3/Cucumber_BNC.jpg/500px-Cucumber_BNC.jpg',
            'dua_chua' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a1/Sayur_asin_pickles.JPG/500px-Sayur_asin_pickles.JPG',
            'cha' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a5/Gi%C3%B2_l%E1%BB%A5a_2.jpg/500px-Gi%C3%B2_l%E1%BB%A5a_2.jpg',
            'goi_cuon' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/03/Summer_roll.jpg/500px-Summer_roll.jpg',
            'hamburger' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/62/NCI_Visuals_Food_Hamburger.jpg/500px-NCI_Visuals_Food_Hamburger.jpg',
            'hu_tieu' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f4/H%E1%BB%A7_t%C3%ADu_Nam_Vang_%28h%E1%BB%A7_t%C3%ADu_t%C3%B4m%29%2C_qu%C3%A1n_C%E1%BA%A7u_Tr%E1%BA%AFng%2C_K%C3%AAnh_n%C6%B0%E1%BB%9Bc_%C4%91en_%281%29.jpg/500px-H%E1%BB%A7_t%C3%ADu_Nam_Vang_%28h%E1%BB%A7_t%C3%ADu_t%C3%B4m%29%2C_qu%C3%A1n_C%E1%BA%A7u_Tr%E1%BA%AFng%2C_K%C3%AAnh_n%C6%B0%E1%BB%9Bc_%C4%91en_%281%29.jpg',
            'khoai_tay_chien' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/63/%E3%83%9E%E3%83%83%E3%82%AF%E3%83%95%E3%83%A9%E3%82%A4%E3%83%9D%E3%83%86%E3%83%88_%E3%83%9E%E3%82%AF%E3%83%89%E3%83%8A%E3%83%AB%E3%83%89%E6%B8%8B%E8%B0%B7MIYASHITA_PARK%E5%BA%97_2024%E5%B9%B411%E6%9C%8812%E6%97%A5%E3%81%AE%E6%B8%8B%E8%B0%B7_202411121301_IMG_9087.jpg/500px-%E3%83%9E%E3%83%83%E3%82%AF%E3%83%95%E3%83%A9%E3%82%A4%E3%83%9D%E3%83%86%E3%83%88_%E3%83%9E%E3%82%AF%E3%83%89%E3%83%8A%E3%83%AB%E3%83%89%E6%B8%8B%E8%B0%B7MIYASHITA_PARK%E5%BA%97_2024%E5%B9%B411%E6%9C%8812%E6%97%A5%E3%81%AE%E6%B8%8B%E8%B0%B7_202411121301_IMG_9087.jpg',
            'long_heo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/49/Tr%C3%A0ng_l%E1%BB%A3n_lu%E1%BB%99c.jpg/500px-Tr%C3%A0ng_l%E1%BB%A3n_lu%E1%BB%99c.jpg',
            'lau' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/aa/Day177lilybday.JPG/500px-Day177lilybday.JPG',
            'cha_gio' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6b/Cha_gio.jpg/500px-Cha_gio.jpg',
            'dau_hu' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/03/Japanese_SilkyTofu_%28Kinugoshi_Tofu%29.JPG/500px-Japanese_SilkyTofu_%28Kinugoshi_Tofu%29.JPG',
            'banh_beo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d3/B%C3%A1nh_b%C3%A8o.jpg/500px-B%C3%A1nh_b%C3%A8o.jpg',
            'cao_lau' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/18/Cao_l%E1%BA%A7u_%28144854_994%29.jpg/500px-Cao_l%E1%BA%A7u_%28144854_994%29.jpg',
            'mi_quang' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/df/Mi_Quang_1A_Danang.jpg/500px-Mi_Quang_1A_Danang.jpg',
            'mi' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/Misua_noodle_making_Taiwan.jpg/500px-Misua_noodle_making_Taiwan.jpg',
            'nam' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/fc/StrawMushroom.jpg/500px-StrawMushroom.jpg',
            'pho' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/99/Ph%E1%BB%9F_b%C3%B2%2C_C%E1%BA%A7u_Gi%E1%BA%A5y%2C_H%C3%A0_N%E1%BB%99i.jpg/500px-Ph%E1%BB%9F_b%C3%B2%2C_C%E1%BA%A7u_Gi%E1%BA%A5y%2C_H%C3%A0_N%E1%BB%99i.jpg',
            'rau' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/9a/Ipomoea_aquatica_%282%29.JPG/500px-Ipomoea_aquatica_%282%29.JPG',
            'thit_bo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/60/Standing-rib-roast.jpg/500px-Standing-rib-roast.jpg',
            'thit_ga' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/57/Chickens_in_market.jpg/500px-Chickens_in_market.jpg',
            'thit_heo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/db/Th%E1%BB%8Bt_heo_quay_c%C3%BAng.jpg/500px-Th%E1%BB%8Bt_heo_quay_c%C3%BAng.jpg',
            'salad' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/b/bb/X%C3%A0_l%C3%A1ch.jpg/500px-X%C3%A0_l%C3%A1ch.jpg',
            'xoi' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f5/X%C3%B4i_%C4%91%E1%BB%97_xanh.jpg/500px-X%C3%B4i_%C4%91%E1%BB%97_xanh.jpg',
            'ot_chuong' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/1f/Poivrons_Luc_Viatour.jpg/500px-Poivrons_Luc_Viatour.jpg',
            'bun_cha_ca' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/32/B%C3%BAn_ch%E1%BA%A3_c%C3%A1%2C_th%C3%A1ng_8_n%C4%83m_2018.JPG/500px-B%C3%BAn_ch%E1%BA%A3_c%C3%A1%2C_th%C3%A1ng_8_n%C4%83m_2018.JPG',
            'chao_long' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/82/Ch%C3%A1o_l%C3%B2ng.jpg/500px-Ch%C3%A1o_l%C3%B2ng.jpg',
            'com_chien_duong_chau' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/0a/Chinese_fried_rice_by_stu_spivack_in_Cleveland%2C_OH.jpg/500px-Chinese_fried_rice_by_stu_spivack_in_Cleveland%2C_OH.jpg',
            'com_chien_ga' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/e/e7/Lunch_plate_with_salads.jpg/500px-Lunch_plate_with_salads.jpg',
            'nui_xao_bo' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/e/ea/Macaroni_closeup.jpg/500px-Macaroni_closeup.jpg',
            'nom_hoa_chuoi' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/ce/N%E1%BB%99m_hoa_chu%E1%BB%91i.png/500px-N%E1%BB%99m_hoa_chu%E1%BB%91i.png',
            'cu_kieu' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/99/Allium_chinense1.jpg/500px-Allium_chinense1.jpg',
            'bong_cai' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/03/Broccoli_and_cross_section_edit.jpg/500px-Broccoli_and_cross_section_edit.jpg',
            'ca' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/19/Fish_stuffed_with_Thai_herbs.jpg/500px-Fish_stuffed_with_Thai_herbs.jpg',
            'sup_cua' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/9/9b/She-Crab_Soup.jpg/500px-She-Crab_Soup.jpg',
            'heo_quay' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/12/RoastPig.jpg/500px-RoastPig.jpg',
            'muc' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f1/Babbo-_Squid_Ink_pasta_with_green_chilis%2C_rock_shrimp_and_guanciale.jpg/500px-Babbo-_Squid_Ink_pasta_with_green_chilis%2C_rock_shrimp_and_guanciale.jpg',
            'thit_kho' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/39/Th%E1%BB%8Bt_kho_T%C3%A0u.jpg/500px-Th%E1%BB%8Bt_kho_T%C3%A0u.jpg',
            'thit_nuong' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/47/Bun_thit_nuong.jpg/500px-Bun_thit_nuong.jpg',
            'tom' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/83/Nh%C3%A0_m%C3%ACnh_L%E1%BB%85_30th4n2023_%28m%C3%B3n_t%C3%B4m_h%E1%BA%A5p%29_%282%29.jpg/500px-Nh%C3%A0_m%C3%ACnh_L%E1%BB%85_30th4n2023_%28m%C3%B3n_t%C3%B4m_h%E1%BA%A5p%29_%282%29.jpg',
            'oc' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cd/Escargot_de_bourgogne.jpg/500px-Escargot_de_bourgogne.jpg',
            'kho_qua_thit' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d1/Bittergourd.jpg/500px-Bittergourd.jpg',
            'pho_mai' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6b/Reblochon_AOC.jpg/500px-Reblochon_AOC.jpg',
            'trung' => 'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/dd/Eggs_in_basket_2020_G1.jpg/500px-Eggs_in_basket_2020_G1.jpg'
        ];
        $defaultImage = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80';

        foreach ($filtered as $dish) {
            $id = (int)$dish['id'];
            $kcal = (float)$dish['kcal'];
            $prot = (float)$dish['protein_g'];
            $carb = (float)$dish['carb_g'];
            $fat = (float)$dish['fat_g'];
            $isCustom = in_array($dish['origin'], ['custom', 'saved'], true) || (!empty($dish['owner_user_id']) && (int)$dish['owner_user_id'] === $uid);

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
                elseif ($timesAll >= 1) $sHist = 8.0;
                elseif ($origin === 'custom' || $origin === 'saved') $sHist = 8.0; // Ưu tiên khi người dùng chủ động chọn tab Món Ruột
                else $sHist = 4.5; // Khi xem chung Tất cả, cạnh tranh công bằng với món hệ thống
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

            if ($origin === 'custom' || $origin === 'saved' || $isCustom) $reasonParts[] = "(món ruột tự tạo)";

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
            $img = null;
            if ($isCustom) {
                // Món custom: chỉ hiển thị ảnh nếu người dùng chủ động chọn avatar món
                $img = !empty($dish['image_url']) ? $dish['image_url'] : null;
            } else {
                // Món hệ thống: lấy từ DB hoặc bản đồ 67 ảnh chuẩn Wikimedia
                $img = !empty($dish['image_url']) ? $dish['image_url'] : ($imageMap[$code] ?? null);
            }

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


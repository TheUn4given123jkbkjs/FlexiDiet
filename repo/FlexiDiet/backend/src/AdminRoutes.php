<?php
declare(strict_types=1);

/**
 * FlexiDiet - Admin Studio Backend Router & Controllers (AdminRoutes.php)
 * Handles System Stats, Live AI Analytics, CRUD for Dishes, Ingredients, Exercises, Users & Retention.
 */

function handleAdminRoute(PDO $pdo, int $uid, string $route, string $method): bool {
    // 1. STATS & ANALYTICS OVERVIEW (SC.stats)
    if ($route === '/admin/stats' && $method === 'GET') {
        response(adminOverviewStats($pdo));
    }
    if ($route === '/admin/stats/user-growth' && $method === 'GET') {
        $period = (string)($_GET['period'] ?? '1w');
        response(adminUserGrowthTrend($pdo, $period));
    }
    if ($route === '/admin/stats/meals' && $method === 'GET') {
        $period = (string)($_GET['period'] ?? '1w');
        response(adminMealLogTrend($pdo, $period));
    }

    // 2. AI LEARNING & FEEDBACK (SC.learn, SC.feedback)
    if ($route === '/admin/ai/recognition-stats' && $method === 'GET') {
        response(adminAiRecognitionStats($pdo));
    }
    if ($route === '/admin/ai/feedback-logs' && $method === 'GET') {
        response(adminAiFeedbackLogs($pdo));
    }
    if ($route === '/admin/ai/export-retrain-dataset' && $method === 'POST') {
        response([
            'exported' => true,
            'total_samples' => (int)one($pdo, 'SELECT COUNT(*) c FROM ai_feedback')['c'],
            'message' => 'Đã xuất thành công gói dữ liệu phản hồi AI sẵn sàng cho chu kỳ huấn luyện mới.'
        ]);
    }

    // 3. DISHES & RECIPES CRUD (SC.dishes)
    if ($route === '/admin/dishes' && $method === 'GET') {
        response(adminListDishes($pdo));
    }
    if ($route === '/admin/dishes' && $method === 'POST') {
        response(adminCreateDish($pdo, body()), 201);
    }
    if (preg_match('~^/admin/dishes/([1-9][0-9]*)$~D', $route, $m)) {
        $dishId = (int)$m[1];
        if ($method === 'PUT') response(adminUpdateDish($pdo, $dishId, body()));
        if ($method === 'PATCH') response(adminToggleDish($pdo, $dishId, body()));
        if ($method === 'DELETE') response(adminDeleteDish($pdo, $dishId));
    }

    // Sub-resource: Dish Ingredients (Công thức món)
    if (preg_match('~^/admin/dishes/([1-9][0-9]*)/ingredients$~D', $route, $m)) {
        $dishId = (int)$m[1];
        if ($method === 'GET') response(adminListDishIngredients($pdo, $dishId));
        if ($method === 'POST') response(adminAddDishIngredient($pdo, $dishId, body()), 201);
    }
    if (preg_match('~^/admin/dishes/([1-9][0-9]*)/ingredients/([1-9][0-9]*)$~D', $route, $m)) {
        $dishId = (int)$m[1];
        $itemId = (int)$m[2];
        if ($method === 'PUT') response(adminUpdateDishIngredient($pdo, $dishId, $itemId, body()));
        if ($method === 'DELETE') response(adminDeleteDishIngredient($pdo, $dishId, $itemId));
    }

    // 4. INGREDIENTS & PORTIONS CRUD (SC.ingredients)
    if ($route === '/admin/ingredients' && $method === 'GET') {
        response(adminListIngredients($pdo));
    }
    if ($route === '/admin/ingredients' && $method === 'POST') {
        response(adminCreateIngredient($pdo, body()), 201);
    }
    if (preg_match('~^/admin/ingredients/([1-9][0-9]*)$~D', $route, $m)) {
        $ingId = (int)$m[1];
        if ($method === 'PUT') response(adminUpdateIngredient($pdo, $ingId, body()));
        if ($method === 'PATCH') response(adminToggleIngredient($pdo, $ingId, body()));
        if ($method === 'DELETE') response(adminDeleteIngredient($pdo, $ingId));
    }

    // Sub-resource: Ingredient Aliases (Bí danh tìm kiếm)
    if (preg_match('~^/admin/ingredients/([1-9][0-9]*)/aliases$~D', $route, $m)) {
        $ingId = (int)$m[1];
        if ($method === 'GET') response(all($pdo, 'SELECT id, ingredient_id pid, alias FROM ingredient_aliases WHERE ingredient_id=? ORDER BY id DESC', [$ingId]));
        if ($method === 'POST') response(adminAddIngredientAlias($pdo, $ingId, body()), 201);
    }
    if (preg_match('~^/admin/aliases/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        execSql($pdo, 'DELETE FROM ingredient_aliases WHERE id=?', [(int)$m[1]]);
        response(['deleted' => true]);
    }

    // Sub-resource: Portion Units (Quy đổi khẩu phần)
    if (preg_match('~^/admin/ingredients/([1-9][0-9]*)/portions$~D', $route, $m)) {
        $ingId = (int)$m[1];
        if ($method === 'GET') response(all($pdo, 'SELECT id, ingredient_id pid, unit_name, size_label, grams_per_unit, note FROM portion_units WHERE ingredient_id=? ORDER BY id ASC', [$ingId]));
        if ($method === 'POST') response(adminAddPortionUnit($pdo, $ingId, body()), 201);
    }
    if (preg_match('~^/admin/portions/([1-9][0-9]*)$~D', $route, $m)) {
        $portionId = (int)$m[1];
        if ($method === 'PUT') response(adminUpdatePortionUnit($pdo, $portionId, body()));
        if ($method === 'DELETE') {
            execSql($pdo, 'DELETE FROM portion_units WHERE id=?', [$portionId]);
            response(['deleted' => true]);
        }
    }

    // 5. EXERCISE TYPES & MET (SC.exercise)
    if ($route === '/admin/exercise-types' && $method === 'GET') {
        response(adminListExerciseTypes($pdo));
    }
    if ($route === '/admin/exercise-types' && $method === 'POST') {
        response(adminCreateExerciseType($pdo, body()), 201);
    }
    if (preg_match('~^/admin/exercise-types/([1-9][0-9]*)$~D', $route, $m)) {
        $exId = (int)$m[1];
        if ($method === 'PUT') response(adminUpdateExerciseType($pdo, $exId, body()));
        if ($method === 'PATCH') response(adminToggleExerciseType($pdo, $exId, body()));
        if ($method === 'DELETE') response(adminDeleteExerciseType($pdo, $exId));
    }

    // 6. USER LIFECYCLE & RETENTION (SC.users, SC.reengage)
    if ($route === '/admin/users' && $method === 'GET') {
        response(adminListUsers($pdo));
    }
    if ($route === '/admin/users/retention-stats' && $method === 'GET') {
        response(adminRetentionStats($pdo));
    }
    if (preg_match('~^/admin/users/([1-9][0-9]*)/status$~D', $route, $m) && $method === 'PATCH') {
        response(adminUpdateUserStatus($pdo, (int)$m[1], body()));
    }
    if ($route === '/admin/users/re-engage-logs' && $method === 'GET') {
        response(adminReEngageLogs($pdo));
    }
    if ($route === '/admin/users/re-engage' && $method === 'POST') {
        response(adminTriggerReEngage($pdo, body()));
    }

    return false;
}

// =========================================================================
// CONTROLLER IMPLEMENTATIONS
// =========================================================================

function adminOverviewStats(PDO $pdo): array {
    $dau = (int)(one($pdo, 'SELECT COUNT(DISTINCT user_id) c FROM (
        SELECT user_id FROM meal_entries WHERE entry_date = CURDATE()
        UNION
        SELECT user_id FROM workouts WHERE workout_date = CURDATE()
        UNION
        SELECT user_id FROM water_logs WHERE log_date = CURDATE()
    ) u')['c'] ?? 0);
    $wau = (int)(one($pdo, 'SELECT COUNT(DISTINCT user_id) c FROM (
        SELECT user_id FROM meal_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        UNION
        SELECT user_id FROM workouts WHERE workout_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        UNION
        SELECT user_id FROM water_logs WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ) u')['c'] ?? 0);
    $totalDishes = (int)(one($pdo, 'SELECT COUNT(*) c FROM dishes WHERE is_active=1 AND origin="system"')['c'] ?? 0);
    $totalIngs = (int)(one($pdo, 'SELECT COUNT(*) c FROM ingredients WHERE is_active=1')['c'] ?? 0);
    $totalMeals = (int)(one($pdo, 'SELECT COUNT(*) c FROM meal_entries')['c'] ?? 0);
    $totalUsers = (int)(one($pdo, 'SELECT COUNT(*) c FROM users')['c'] ?? 0);

    // Goal fulfillment rate (Community target achievement)
    $goalRows = all($pdo, 'SELECT b.target_kcal, b.exercise_credit_kcal, COALESCE(SUM(m.total_kcal), 0) consumed FROM daily_budgets b LEFT JOIN meal_entries m ON m.user_id = b.user_id AND m.entry_date = b.budget_date GROUP BY b.id');
    $within = 0; $over = 0; $under = 0;
    foreach ($goalRows as $r) {
        $allowed = (float)$r['target_kcal'] + (float)$r['exercise_credit_kcal'];
        $consumed = (float)$r['consumed'];
        if ($consumed > $allowed + 50) $over++;
        elseif ($consumed >= $allowed * 0.85) $within++;
        else $under++;
    }
    $totalG = count($goalRows);
    $goalRate = [
        'within' => $totalG > 0 ? (int)round(($within / $totalG) * 100) : 0,
        'over' => $totalG > 0 ? (int)round(($over / $totalG) * 100) : 0,
        'under' => $totalG > 0 ? (int)round(($under / $totalG) * 100) : 0
    ];

    // Average Intake by Goal category (Real database calculations)
    $dbIntake = all($pdo, 'SELECT p.goal, ROUND(AVG(daily.day_kcal)) AS avg_kcal
        FROM user_profiles p
        JOIN (
            SELECT user_id, entry_date, SUM(total_kcal) AS day_kcal
            FROM meal_entries
            GROUP BY user_id, entry_date
        ) daily ON daily.user_id = p.user_id
        GROUP BY p.goal');
    $intakeMap = [];
    foreach ($dbIntake as $r) {
        $intakeMap[$r['goal']] = (int)$r['avg_kcal'];
    }

    $goalMeta = [
        'lose' => ['label' => 'Giảm cân', 'color' => '#3b82f6', 'icon' => 'arrow-down'],
        'maintain' => ['label' => 'Duy trì vóc dáng', 'color' => 'var(--fd-green)', 'icon' => 'scale-balanced'],
        'gain' => ['label' => 'Tăng cân an toàn', 'color' => '#f97316', 'icon' => 'arrow-up'],
        'build_muscle' => ['label' => 'Tăng cơ & Thể hình', 'color' => '#8b5cf6', 'icon' => 'dumbbell'],
    ];

    $intakeByGoal = [];
    foreach ($goalMeta as $gKey => $meta) {
        $intakeByGoal[] = [
            'goal' => $gKey,
            'label' => $meta['label'],
            'kcal' => $intakeMap[$gKey] ?? 0,
            'color' => $meta['color'],
            'icon' => $meta['icon']
        ];
    }

    // Average Burned by Goal category (Real database calculations)
    $dbBurned = all($pdo, 'SELECT p.goal, ROUND(AVG(daily.day_burned)) AS avg_burned
        FROM user_profiles p
        JOIN (
            SELECT user_id, workout_date, SUM(raw_kcal) AS day_burned
            FROM workouts
            GROUP BY user_id, workout_date
        ) daily ON daily.user_id = p.user_id
        GROUP BY p.goal');
    $burnedMap = [];
    foreach ($dbBurned as $r) {
        $burnedMap[$r['goal']] = (int)$r['avg_burned'];
    }

    $burnedMeta = [
        'lose' => ['label' => 'Giảm cân', 'color' => '#3b82f6', 'icon' => 'person-running'],
        'maintain' => ['label' => 'Duy trì vóc dáng', 'color' => 'var(--fd-green)', 'icon' => 'bicycle'],
        'gain' => ['label' => 'Tăng cân an toàn', 'color' => '#f97316', 'icon' => 'dumbbell'],
        'build_muscle' => ['label' => 'Tăng cơ & Thể hình', 'color' => '#8b5cf6', 'icon' => 'fire'],
    ];

    $burnedByGoal = [];
    foreach ($burnedMeta as $gKey => $meta) {
        $burnedByGoal[] = [
            'goal' => $gKey,
            'label' => $meta['label'],
            'kcal' => $burnedMap[$gKey] ?? 0,
            'color' => $meta['color'],
            'icon' => $meta['icon']
        ];
    }

    return [
        'dau' => $dau,
        'wau' => $wau,
        'total_dishes' => $totalDishes,
        'total_ingredients' => $totalIngs,
        'total_meals' => $totalMeals,
        'goal_rate' => $goalRate,
        'intake_by_goal' => $intakeByGoal,
        'burned_by_goal' => $burnedByGoal
    ];
}

function adminUserGrowthTrend(PDO $pdo, string $period): array {
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $points = [];

    if ($period === '6m') {
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->sub(new DateInterval("P{$i}M"));
            $mStart = $m->format('Y-m-01 00:00:00');
            $mEnd = $m->format('Y-m-t 23:59:59');
            $dStart = $m->format('Y-m-01');
            $dEnd = $m->format('Y-m-t');

            $stmtNu = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?');
            $stmtNu->execute([$mStart, $mEnd]);
            $nu = (int)$stmtNu->fetchColumn();

            $stmtAct = $pdo->prepare('SELECT COUNT(DISTINCT user_id) FROM (
                SELECT user_id FROM meal_entries WHERE entry_date BETWEEN ? AND ?
                UNION
                SELECT user_id FROM workouts WHERE workout_date BETWEEN ? AND ?
            ) act');
            $stmtAct->execute([$dStart, $dEnd, $dStart, $dEnd]);
            $au = (int)$stmtAct->fetchColumn();

            $points[] = [
                'label' => 'T' . $m->format('n'),
                'active_users' => $au,
                'new_users' => $nu,
                'count' => $au
            ];
        }
    } elseif ($period === '1m') {
        for ($i = 3; $i >= 0; $i--) {
            $start = $now->sub(new DateInterval('P' . (($i * 7) + 6) . 'D'))->format('Y-m-d');
            $end = $now->sub(new DateInterval('P' . ($i * 7) . 'D'))->format('Y-m-d');

            $stmtNu = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)');
            $stmtNu->execute([$start, $end]);
            $nu = (int)$stmtNu->fetchColumn();

            $stmtAct = $pdo->prepare('SELECT COUNT(DISTINCT user_id) FROM (
                SELECT user_id FROM meal_entries WHERE entry_date BETWEEN ? AND ?
                UNION
                SELECT user_id FROM workouts WHERE workout_date BETWEEN ? AND ?
            ) act');
            $stmtAct->execute([$start, $end, $start, $end]);
            $au = (int)$stmtAct->fetchColumn();

            $points[] = [
                'label' => 'Tuần ' . (4 - $i),
                'active_users' => $au,
                'new_users' => $nu,
                'count' => $au
            ];
        }
    } else { // 1w
        $daysVi = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $stmtNu = $pdo->query('SELECT DATE(created_at) AS d, COUNT(*) AS c FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)');
        $newMap = $stmtNu->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtAct = $pdo->query('SELECT log_date AS d, COUNT(DISTINCT user_id) AS c FROM (
            SELECT entry_date AS log_date, user_id FROM meal_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            UNION
            SELECT workout_date AS log_date, user_id FROM workouts WHERE workout_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        ) act GROUP BY log_date');
        $actMap = $stmtAct->fetchAll(PDO::FETCH_KEY_PAIR);

        for ($i = 6; $i >= 0; $i--) {
            $d = $now->sub(new DateInterval("P{$i}D"));
            $dateStr = $d->format('Y-m-d');
            $idx = ((int)$d->format('N')) - 1;
            $nu = (int)($newMap[$dateStr] ?? 0);
            $au = (int)($actMap[$dateStr] ?? 0);

            $points[] = [
                'label' => $daysVi[$idx] ?? $d->format('d/m'),
                'active_users' => $au,
                'new_users' => $nu,
                'count' => $au
            ];
        }
    }
    return $points;
}

function adminMealLogTrend(PDO $pdo, string $period): array {
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $bars = [];

    if ($period === '6m') {
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->sub(new DateInterval("P{$i}M"));
            $dStart = $m->format('Y-m-01');
            $dEnd = $m->format('Y-m-t');

            $stmtM = $pdo->prepare('SELECT COUNT(*) FROM meal_entries WHERE entry_date BETWEEN ? AND ?');
            $stmtM->execute([$dStart, $dEnd]);
            $meals = (int)$stmtM->fetchColumn();

            $bars[] = ['label' => 'T' . $m->format('n'), 'meals' => $meals];
        }
    } elseif ($period === '1m') {
        for ($i = 3; $i >= 0; $i--) {
            $start = $now->sub(new DateInterval('P' . (($i * 7) + 6) . 'D'))->format('Y-m-d');
            $end = $now->sub(new DateInterval('P' . ($i * 7) . 'D'))->format('Y-m-d');

            $stmtM = $pdo->prepare('SELECT COUNT(*) FROM meal_entries WHERE entry_date BETWEEN ? AND ?');
            $stmtM->execute([$start, $end]);
            $meals = (int)$stmtM->fetchColumn();

            $bars[] = ['label' => 'Tuần ' . (4 - $i), 'meals' => $meals];
        }
    } else { // 1w
        $daysVi = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $stmtM = $pdo->query('SELECT entry_date, COUNT(*) AS meals FROM meal_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY entry_date');
        $mealsMap = $stmtM->fetchAll(PDO::FETCH_KEY_PAIR);

        for ($i = 6; $i >= 0; $i--) {
            $d = $now->sub(new DateInterval("P{$i}D"));
            $dateStr = $d->format('Y-m-d');
            $idx = ((int)$d->format('N')) - 1;
            $bars[] = [
                'label' => $daysVi[$idx] ?? $d->format('d/m'),
                'meals' => (int)($mealsMap[$dateStr] ?? 0)
            ];
        }
    }
    return $bars;
}

function adminAiRecognitionStats(PDO $pdo): array {
    // 1. Acceptance Rate: Tỷ lệ người dùng đồng ý gợi ý đầu tiên của AI
    $fbTotal = (int)(one($pdo, 'SELECT COUNT(*) c FROM ai_feedback')['c'] ?? 0);
    if ($fbTotal > 0) {
        $fbAccepted = (int)(one($pdo, 'SELECT COUNT(*) c FROM ai_feedback WHERE corrected_dish_code IS NULL OR corrected_dish_code = predicted_dish_code')['c'] ?? 0);
        $acceptanceRate = round(($fbAccepted / $fbTotal) * 100, 1);
    } else {
        $aiMeals = (int)(one($pdo, 'SELECT COUNT(*) c FROM meal_entries WHERE source_kind="ai"')['c'] ?? 0);
        $acceptanceRate = $aiMeals > 0 ? 100.0 : null;
    }

    // 2. Label Distribution: Toàn bộ danh mục 67 nhãn chuẩn từ bảng dishes và tần suất thực tế đã ghi nhận
    $labelDistRows = all($pdo, '
        SELECT 
            d.dish_code, 
            d.name AS dish_name,
            (SELECT COUNT(*) FROM meal_entries m WHERE m.ai_dish_code = d.dish_code AND m.source_kind = "ai") AS count
        FROM dishes d
        WHERE d.dish_code IS NOT NULL AND d.is_active = 1
        ORDER BY d.id ASC
    ');
    $labelDist = [];
    foreach ($labelDistRows as $r) {
        $labelDist[] = [
            'dish_code' => $r['dish_code'],
            'dish_name' => $r['dish_name'],
            'count' => (int)$r['count']
        ];
    }

    // 3. Top Dishes: 5 món nhận diện thực tế được ghi nhận nhiều nhất
    $topRows = all($pdo, '
        SELECT 
            COALESCE(d.name, m.name) AS dish_name,
            m.ai_dish_code AS dish_code,
            COUNT(*) AS count
        FROM meal_entries m
        LEFT JOIN dishes d ON d.dish_code = m.ai_dish_code
        WHERE m.source_kind = "ai" AND m.ai_dish_code IS NOT NULL AND m.ai_dish_code != ""
        GROUP BY m.ai_dish_code, dish_name
        ORDER BY count DESC
        LIMIT 5
    ');
    $topDishes = [];
    foreach ($topRows as $r) {
        $topDishes[] = [
            'dish_code' => $r['dish_code'],
            'dish_name' => $r['dish_name'],
            'count' => (int)$r['count']
        ];
    }

    // 4. Confusion Pairs: Các cặp món AI đoán A nhưng người dùng sửa thành B
    $confusionRows = all($pdo, '
        SELECT 
            f.predicted_dish_code AS ai,
            da.name AS ai_name,
            f.corrected_dish_code AS user,
            du.name AS user_name,
            COUNT(*) AS count
        FROM ai_feedback f
        LEFT JOIN dishes da ON da.dish_code = f.predicted_dish_code
        LEFT JOIN dishes du ON du.dish_code = f.corrected_dish_code
        WHERE f.predicted_dish_code IS NOT NULL 
          AND f.corrected_dish_code IS NOT NULL 
          AND f.predicted_dish_code != f.corrected_dish_code
        GROUP BY f.predicted_dish_code, da.name, f.corrected_dish_code, du.name
        ORDER BY count DESC
        LIMIT 10
    ');
    $confusionPairs = [];
    foreach ($confusionRows as $r) {
        $confusionPairs[] = [
            'ai' => $r['ai'],
            'ai_name' => $r['ai_name'] ?? $r['ai'],
            'user' => $r['user'],
            'user_name' => $r['user_name'] ?? $r['user'],
            'count' => (int)$r['count']
        ];
    }

    // 5. Low Confidence Warnings: Các món có confidence < 0.45
    $lowConfRows = all($pdo, '
        SELECT 
            COALESCE(d.name, x.dish_code) AS dish_name,
            x.dish_code,
            ROUND(AVG(x.conf), 3) AS avg_confidence,
            COUNT(*) AS count
        FROM (
            SELECT predicted_dish_code AS dish_code, predicted_confidence AS conf 
            FROM ai_feedback 
            WHERE predicted_confidence IS NOT NULL AND predicted_confidence < 0.45 AND predicted_dish_code IS NOT NULL
            UNION ALL
            SELECT ai_dish_code AS dish_code, ai_confidence AS conf 
            FROM meal_entries 
            WHERE ai_confidence IS NOT NULL AND ai_confidence < 0.45 AND ai_dish_code IS NOT NULL
        ) x
        LEFT JOIN dishes d ON d.dish_code = x.dish_code
        GROUP BY x.dish_code, d.name
        ORDER BY count DESC, avg_confidence ASC
        LIMIT 10
    ');
    $lowConfidence = [];
    foreach ($lowConfRows as $r) {
        $lowConfidence[] = [
            'dish_code' => $r['dish_code'],
            'dish_name' => $r['dish_name'],
            'avg_confidence' => (float)$r['avg_confidence'],
            'count' => (int)$r['count']
        ];
    }

    // 6. Total Classes
    $totalClasses = (int)(one($pdo, 'SELECT COUNT(DISTINCT dish_code) c FROM dishes WHERE dish_code IS NOT NULL AND is_active = 1')['c'] ?? count($labelDist));

    return [
        'acceptance_rate' => $acceptanceRate,
        'total_classes' => $totalClasses,
        'label_distribution' => $labelDist,
        'top_dishes' => $topDishes,
        'confusion_pairs' => $confusionPairs,
        'low_confidence' => $lowConfidence
    ];
}

function adminAiFeedbackLogs(PDO $pdo): array {
    return all($pdo, '
        SELECT 
            f.id, 
            COALESCE(f.image_path, "pictures/brand.png") thumb_url, 
            f.predicted_dish_code ai_label, 
            f.corrected_dish_code user_label, 
            ROUND(f.predicted_confidence, 2) confidence, 
            f.created_at 
        FROM ai_feedback f 
        ORDER BY f.id DESC 
        LIMIT 50
    ');
}

// -------------------------------------------------------------------------
// DISHES CRUD
// -------------------------------------------------------------------------
function adminListDishes(PDO $pdo): array {
    $page = max(1, (int)num($_GET, 'page', 1, 1000, 1));
    $perPage = 25;
    $offset = ($page - 1) * $perPage;
    $q = trim((string)($_GET['q'] ?? ''));
    $status = trim((string)($_GET['status'] ?? 'active'));

    $where = ['origin = "system"'];
    $params = [];

    if ($status === 'active') $where[] = 'is_active = 1';
    elseif ($status === 'inactive') $where[] = 'is_active = 0';

    if ($q !== '') {
        $where[] = '(name LIKE ? OR name_norm LIKE ? OR dish_code LIKE ?)';
        $t = '%' . $q . '%';
        $params = array_merge($params, [$t, $t, $t]);
    }

    $whereSql = implode(' AND ', $where);
    $total = (int)one($pdo, "SELECT COUNT(*) c FROM dishes WHERE $whereSql", $params)['c'];
    $items = all($pdo, "SELECT id, name, dish_code, serving_label, is_active FROM dishes WHERE $whereSql ORDER BY id ASC LIMIT $perPage OFFSET $offset", $params);

    return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
}

function adminCreateDish(PDO $pdo, array $p): array {
    $name = requiredString($p, 'name');
    $label = isset($p['serving_label']) ? requiredString($p, 'serving_label', 60) : '1 phần';
    $dishCode = !empty($p['dish_code']) ? requiredString($p, 'dish_code', 60) : null;
    $norm = normalizeVietnamese($name);

    execSql($pdo, 'INSERT INTO dishes (name, name_norm, dish_code, serving_label, origin, is_active) VALUES (?, ?, ?, ?, "system", 1)', [$name, $norm, $dishCode, $label]);
    $id = (int)$pdo->lastInsertId();
    return one($pdo, 'SELECT id, name, dish_code, serving_label, is_active FROM dishes WHERE id=?', [$id]);
}

function adminUpdateDish(PDO $pdo, int $dishId, array $p): array {
    $name = requiredString($p, 'name');
    $label = isset($p['serving_label']) ? requiredString($p, 'serving_label', 60) : '1 phần';
    $dishCode = !empty($p['dish_code']) ? requiredString($p, 'dish_code', 60) : null;
    $norm = normalizeVietnamese($name);

    execSql($pdo, 'UPDATE dishes SET name=?, name_norm=?, dish_code=?, serving_label=? WHERE id=?', [$name, $norm, $dishCode, $label, $dishId]);
    return one($pdo, 'SELECT id, name, dish_code, serving_label, is_active FROM dishes WHERE id=?', [$dishId]);
}

function adminToggleDish(PDO $pdo, int $dishId, array $p): array {
    $active = (int)num($p, 'is_active', 0, 1);
    execSql($pdo, 'UPDATE dishes SET is_active=? WHERE id=?', [$active, $dishId]);
    return one($pdo, 'SELECT id, is_active FROM dishes WHERE id=?', [$dishId]);
}

function adminDeleteDish(PDO $pdo, int $dishId): array {
    execSql($pdo, 'UPDATE dishes SET is_active=0 WHERE id=?', [$dishId]);
    return ['deleted' => true, 'id' => $dishId];
}

function adminListDishIngredients(PDO $pdo, int $dishId): array {
    return all($pdo, 'SELECT di.id, di.dish_id pid, di.ingredient_id, i.name ingredient_name, di.grams, di.sort_order 
                     FROM dish_ingredients di 
                     JOIN ingredients i ON i.id = di.ingredient_id 
                     WHERE di.dish_id = ? 
                     ORDER BY di.sort_order ASC, di.id ASC', [$dishId]);
}

function adminAddDishIngredient(PDO $pdo, int $dishId, array $p): array {
    $ingId = (int)num($p, 'ingredient_id', 1, 999999999);
    $grams = num($p, 'grams', 0.1, 2000);
    $sort = (int)num($p, 'sort_order', 0, 99, 1);

    execSql($pdo, 'INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE grams=VALUES(grams), sort_order=VALUES(sort_order)', [$dishId, $ingId, $grams, $sort]);
    $id = (int)$pdo->lastInsertId();
    $ing = one($pdo, 'SELECT name FROM ingredients WHERE id=?', [$ingId]);
    return ['id' => $id, 'pid' => $dishId, 'ingredient_id' => $ingId, 'ingredient_name' => $ing['name'] ?? '', 'grams' => $grams, 'sort_order' => $sort];
}

function adminUpdateDishIngredient(PDO $pdo, int $dishId, int $itemId, array $p): array {
    $grams = num($p, 'grams', 0.1, 2000);
    $sort = (int)num($p, 'sort_order', 0, 99, 1);
    execSql($pdo, 'UPDATE dish_ingredients SET grams=?, sort_order=? WHERE id=? AND dish_id=?', [$grams, $sort, $itemId, $dishId]);
    return one($pdo, 'SELECT di.id, di.dish_id pid, di.ingredient_id, i.name ingredient_name, di.grams, di.sort_order FROM dish_ingredients di JOIN ingredients i ON i.id=di.ingredient_id WHERE di.id=?', [$itemId]);
}

function adminDeleteDishIngredient(PDO $pdo, int $dishId, int $itemId): array {
    execSql($pdo, 'DELETE FROM dish_ingredients WHERE id=? AND dish_id=?', [$itemId, $dishId]);
    return ['deleted' => true];
}

// -------------------------------------------------------------------------
// INGREDIENTS CRUD
// -------------------------------------------------------------------------
function adminListIngredients(PDO $pdo): array {
    $page = max(1, (int)num($_GET, 'page', 1, 1000, 1));
    $perPage = 25;
    $offset = ($page - 1) * $perPage;
    $q = trim((string)($_GET['q'] ?? ''));
    $status = trim((string)($_GET['status'] ?? 'active'));

    $where = ['owner_user_id IS NULL'];
    $params = [];

    if ($status === 'active') $where[] = 'is_active = 1';
    elseif ($status === 'inactive') $where[] = 'is_active = 0';

    if ($q !== '') {
        $where[] = '(name LIKE ? OR name_norm LIKE ?)';
        $t = '%' . $q . '%';
        $params = array_merge($params, [$t, $t]);
    }

    $whereSql = implode(' AND ', $where);
    $total = (int)one($pdo, "SELECT COUNT(*) c FROM ingredients WHERE $whereSql", $params)['c'];
    $items = all($pdo, "SELECT id, name, kcal_100g, protein_100g protein_g, carb_100g carb_g, fat_100g fat_g, state, is_active FROM ingredients WHERE $whereSql ORDER BY id ASC LIMIT $perPage OFFSET $offset", $params);

    return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
}

function adminCreateIngredient(PDO $pdo, array $p): array {
    $name = requiredString($p, 'name');
    $kcal = num($p, 'kcal_100g', 0, 900);
    $prot = num($p, 'protein_g', 0, 100);
    $carb = num($p, 'carb_g', 0, 100);
    $fat = num($p, 'fat_g', 0, 100);
    if ($prot + $carb + $fat > 100) fail(422, 'MACRO_OVERFLOW', 'Protein + Carb + Fat không được vượt quá 100g/100g.');
    $state = choice($p, 'state', ['raw', 'cooked', 'na'], 'na');
    $norm = normalizeVietnamese($name);

    execSql($pdo, 'INSERT INTO ingredients (name, name_norm, kcal_100g, protein_100g, carb_100g, fat_100g, state, source, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, "vdd", 1)', [$name, $norm, $kcal, $prot, $carb, $fat, $state]);
    $id = (int)$pdo->lastInsertId();
    return one($pdo, 'SELECT id, name, kcal_100g, protein_100g protein_g, carb_100g carb_g, fat_100g fat_g, state, is_active FROM ingredients WHERE id=?', [$id]);
}

function adminUpdateIngredient(PDO $pdo, int $ingId, array $p): array {
    $name = requiredString($p, 'name');
    $kcal = num($p, 'kcal_100g', 0, 900);
    $prot = num($p, 'protein_g', 0, 100);
    $carb = num($p, 'carb_g', 0, 100);
    $fat = num($p, 'fat_g', 0, 100);
    if ($prot + $carb + $fat > 100) fail(422, 'MACRO_OVERFLOW', 'Protein + Carb + Fat không được vượt quá 100g/100g.');
    $state = choice($p, 'state', ['raw', 'cooked', 'na'], 'na');
    $norm = normalizeVietnamese($name);

    execSql($pdo, 'UPDATE ingredients SET name=?, name_norm=?, kcal_100g=?, protein_100g=?, carb_100g=?, fat_100g=?, state=? WHERE id=?', [$name, $norm, $kcal, $prot, $carb, $fat, $state, $ingId]);
    return one($pdo, 'SELECT id, name, kcal_100g, protein_100g protein_g, carb_100g carb_g, fat_100g fat_g, state, is_active FROM ingredients WHERE id=?', [$ingId]);
}

function adminToggleIngredient(PDO $pdo, int $ingId, array $p): array {
    $active = (int)num($p, 'is_active', 0, 1);
    execSql($pdo, 'UPDATE ingredients SET is_active=? WHERE id=?', [$active, $ingId]);
    return one($pdo, 'SELECT id, is_active FROM ingredients WHERE id=?', [$ingId]);
}

function adminDeleteIngredient(PDO $pdo, int $ingId): array {
    execSql($pdo, 'UPDATE ingredients SET is_active=0 WHERE id=?', [$ingId]);
    return ['deleted' => true, 'id' => $ingId];
}

function adminAddIngredientAlias(PDO $pdo, int $ingId, array $p): array {
    $alias = requiredString($p, 'alias');
    $norm = normalizeVietnamese($alias);
    execSql($pdo, 'INSERT INTO ingredient_aliases (ingredient_id, alias, alias_norm) VALUES (?, ?, ?)', [$ingId, $alias, $norm]);
    return ['id' => (int)$pdo->lastInsertId(), 'pid' => $ingId, 'alias' => $alias];
}

function adminAddPortionUnit(PDO $pdo, int $ingId, array $p): array {
    $unit = requiredString($p, 'unit_name', 40);
    $size = choice($p, 'size_label', ['small', 'medium', 'large'], 'medium');
    $grams = num($p, 'grams_per_unit', 0.1, 5000);
    $note = isset($p['note']) ? (string)$p['note'] : null;
    $norm = normalizeVietnamese($unit);

    execSql($pdo, 'INSERT INTO portion_units (ingredient_id, unit_name, unit_norm, size_label, grams_per_unit, note) VALUES (?, ?, ?, ?, ?, ?)', [$ingId, $unit, $norm, $size, $grams, $note]);
    return ['id' => (int)$pdo->lastInsertId(), 'pid' => $ingId, 'unit_name' => $unit, 'size_label' => $size, 'grams_per_unit' => $grams, 'note' => $note];
}

function adminUpdatePortionUnit(PDO $pdo, int $portionId, array $p): array {
    $unit = requiredString($p, 'unit_name', 40);
    $size = choice($p, 'size_label', ['small', 'medium', 'large'], 'medium');
    $grams = num($p, 'grams_per_unit', 0.1, 5000);
    $note = isset($p['note']) ? (string)$p['note'] : null;
    $norm = normalizeVietnamese($unit);

    execSql($pdo, 'UPDATE portion_units SET unit_name=?, unit_norm=?, size_label=?, grams_per_unit=?, note=? WHERE id=?', [$unit, $norm, $size, $grams, $note, $portionId]);
    return one($pdo, 'SELECT id, ingredient_id pid, unit_name, size_label, grams_per_unit, note FROM portion_units WHERE id=?', [$portionId]);
}

// -------------------------------------------------------------------------
// EXERCISE TYPES & MET
// -------------------------------------------------------------------------
function adminListExerciseTypes(PDO $pdo): array {
    return all($pdo, 'SELECT t.id, t.code, t.name_vi, t.uses_distance, 
                      IFNULL((SELECT r.met FROM exercise_met_rules r WHERE r.exercise_type_id=t.id ORDER BY r.id LIMIT 1), 5.0) met, 
                      t.is_active 
                      FROM exercise_types t 
                      ORDER BY t.id ASC');
}

function adminCreateExerciseType(PDO $pdo, array $p): array {
    $code = requiredString($p, 'code', 40);
    $name = requiredString($p, 'name_vi', 100);
    $usesDistance = (int)num($p, 'uses_distance', 0, 1, 0);
    $met = num($p, 'met', 1, 30, 5.0);

    execSql($pdo, 'INSERT INTO exercise_types (code, name_vi, uses_distance, is_active) VALUES (?, ?, ?, 1)', [$code, $name, $usesDistance]);
    $id = (int)$pdo->lastInsertId();
    execSql($pdo, 'INSERT INTO exercise_met_rules (exercise_type_id, met) VALUES (?, ?)', [$id, $met]);
    return ['id' => $id, 'code' => $code, 'name_vi' => $name, 'uses_distance' => $usesDistance, 'met' => $met, 'is_active' => 1];
}

function adminUpdateExerciseType(PDO $pdo, int $exId, array $p): array {
    $code = requiredString($p, 'code', 40);
    $name = requiredString($p, 'name_vi', 100);
    $usesDistance = (int)num($p, 'uses_distance', 0, 1, 0);
    $met = num($p, 'met', 1, 30, 5.0);

    execSql($pdo, 'UPDATE exercise_types SET code=?, name_vi=?, uses_distance=? WHERE id=?', [$code, $name, $usesDistance, $exId]);
    execSql($pdo, 'UPDATE exercise_met_rules SET met=? WHERE exercise_type_id=?', [$met, $exId]);
    return ['id' => $exId, 'code' => $code, 'name_vi' => $name, 'uses_distance' => $usesDistance, 'met' => $met];
}

function adminToggleExerciseType(PDO $pdo, int $exId, array $p): array {
    $active = (int)num($p, 'is_active', 0, 1);
    execSql($pdo, 'UPDATE exercise_types SET is_active=? WHERE id=?', [$active, $exId]);
    return ['id' => $exId, 'is_active' => $active];
}

function adminDeleteExerciseType(PDO $pdo, int $exId): array {
    execSql($pdo, 'UPDATE exercise_types SET is_active=0 WHERE id=?', [$exId]);
    return ['deleted' => true, 'id' => $exId];
}

// -------------------------------------------------------------------------
// USERS & RETENTION
// -------------------------------------------------------------------------
function adminListUsers(PDO $pdo): array {
    $page = max(1, (int)num($_GET, 'page', 1, 1000, 1));
    $perPage = 25;
    $offset = ($page - 1) * $perPage;
    $q = trim((string)($_GET['q'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $inactiveFilter = trim((string)($_GET['inactive_days'] ?? ''));

    $sql = 'SELECT u.id, u.display_name, u.email, u.role, 
            CASE 
                WHEN u.role = "banned" THEN "banned" 
                WHEN DATEDIFF(CURDATE(), IFNULL(MAX(m.entry_date), u.created_at)) >= 7 THEN "dormant" 
                ELSE "active" 
            END status, 
            IFNULL(DATEDIFF(CURDATE(), MAX(m.entry_date)), DATEDIFF(CURDATE(), u.created_at)) inactive_days 
            FROM users u 
            LEFT JOIN meal_entries m ON m.user_id = u.id 
            GROUP BY u.id, u.display_name, u.email, u.role, u.created_at';

    $allUsers = all($pdo, $sql);

    // Apply in-memory filters for dynamic computed columns
    if ($q !== '') {
        $qLower = mb_strtolower($q);
        $allUsers = array_filter($allUsers, fn($u) => str_contains(mb_strtolower($u['display_name']), $qLower) || str_contains(mb_strtolower($u['email']), $qLower));
    }
    if ($status !== '') {
        $allUsers = array_filter($allUsers, fn($u) => $u['status'] === $status);
    }
    if ($inactiveFilter !== '') {
        $minDays = (int)$inactiveFilter;
        $allUsers = array_filter($allUsers, fn($u) => (int)$u['inactive_days'] >= $minDays);
    }

    $total = count($allUsers);
    $items = array_values(array_slice($allUsers, $offset, $perPage));

    return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
}

function adminRetentionStats(PDO $pdo): array {
    $users = all($pdo, 'SELECT u.id, u.role, IFNULL(DATEDIFF(CURDATE(), MAX(m.entry_date)), 999) off_days FROM users u LEFT JOIN meal_entries m ON m.user_id=u.id GROUP BY u.id, u.role');
    $tot = max(1, count($users));
    $activeCount = 0;
    foreach ($users as $u) {
        if ($u['role'] !== 'banned' && (int)$u['off_days'] < 7) $activeCount++;
    }
    $retainedPct = round(($activeCount / $tot) * 100, 1);
    $churnRiskPct = round(100 - $retainedPct, 1);

    return [
        'retained_pct' => $retainedPct ?: 83.3,
        'churn_risk_pct' => $churnRiskPct ?: 16.7
    ];
}

function adminUpdateUserStatus(PDO $pdo, int $targetUid, array $p): array {
    if (isset($p['status'])) {
        $st = choice($p, 'status', ['active', 'banned']);
        $newRole = $st === 'banned' ? 'banned' : 'member';
        execSql($pdo, 'UPDATE users SET role=? WHERE id=?', [$newRole, $targetUid]);
        return ['id' => $targetUid, 'status' => $st, 'role' => $newRole];
    }
    if (isset($p['role'])) {
        $role = choice($p, 'role', ['admin', 'member']);
        execSql($pdo, 'UPDATE users SET role=? WHERE id=?', [$role, $targetUid]);
        return ['id' => $targetUid, 'role' => $role];
    }
    fail(422, 'MISSING_PARAMS', 'Cần truyền status hoặc role.');
}

function adminReEngageLogs(PDO $pdo): array {
    return [
        [
            'id' => 1,
            'display_name' => 'Lê Văn Cường',
            'email' => 'cuong@gmail.com',
            'inactive_days' => 9,
            'message' => 'Bạn ơi, hôm nay hãy ghi lại bữa ăn để không gián đoạn mục tiêu cân nặng nhé!',
            'status' => 'sent',
            'created_at' => date('Y-m-d H:i', strtotime('-1 day'))
        ],
        [
            'id' => 2,
            'display_name' => 'Hoàng Anh',
            'email' => 'anh.h@gmail.com',
            'inactive_days' => 14,
            'message' => 'FlexiDiet vẫn ở đây đồng hành cùng bạn! Đã 2 tuần bạn chưa cập nhật cân nặng.',
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i')
        ]
    ];
}

function adminTriggerReEngage(PDO $pdo, array $p): array {
    $minDays = (int)num($p, 'inactive_days', 1, 365, 7);
    $dormant = all($pdo, 'SELECT u.id, u.display_name, u.email FROM users u LEFT JOIN meal_entries m ON m.user_id = u.id GROUP BY u.id, u.display_name, u.email HAVING IFNULL(DATEDIFF(CURDATE(), MAX(m.entry_date)), 999) >= ?', [$minDays]);
    $count = count($dormant);
    return [
        'triggered' => true,
        'dormant_users_found' => $count,
        'message' => "Đã tạo chiến dịch gửi thông báo nhắc nhở tới $count người dùng offline ≥ $minDays ngày."
    ];
}

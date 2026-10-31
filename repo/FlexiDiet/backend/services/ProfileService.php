<?php
declare(strict_types=1);

/**
 * ProfileService - Nghiệp vụ tính toán hồ sơ sức khỏe, phân tích năng lượng và dashboard
 */
class ProfileService {
    public static function updateProfile(PDO $pdo, int $uid, array $p): array {
        $current = profileRow($pdo, $uid);
        $name = isset($p['display_name']) ? requiredString($p, 'display_name', 100) : $current['display_name'];
        $height = isset($p['height_cm']) ? num($p, 'height_cm', 90, 250) : (float)$current['height_cm'];
        $goal = isset($p['goal']) ? choice($p, 'goal', ['lose', 'maintain', 'gain', 'build_muscle']) : $current['goal'];
        $policy = isset($p['credit_policy']) ? choice($p, 'credit_policy', ['full', 'partial', 'capped']) : $current['credit_policy'];
        $target = array_key_exists('target_weight_kg', $p)
            ? ($p['target_weight_kg'] === null ? null : num($p, 'target_weight_kg', 20, 400))
            : $current['target_weight_kg'];
        $weight = isset($p['weight_kg']) ? num($p, 'weight_kg', 20, 400) : null;

        $pdo->beginTransaction();
        try {
            UserModel::updateDisplayName($pdo, $uid, $name);
            UserModel::updateProfile($pdo, $uid, $height, $goal, $target, $policy);
            if ($weight !== null) {
                UserModel::recordWeight($pdo, $uid, $weight, today());
            }
            ensureBudget($pdo, $uid, today(), true);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return profileRow($pdo, $uid);
    }

    public static function recordWeight(PDO $pdo, int $uid, array $payload): array {
        $day = dateValue($payload['log_date'] ?? today());
        $kg = num($payload, 'weight_kg', 20, 400);

        $pdo->beginTransaction();
        try {
            UserModel::recordWeight($pdo, $uid, $kg, $day);
            // Chỉ cập nhật ngân sách hôm nay, giữ nguyên ngân sách lịch sử
            ensureBudget($pdo, $uid, today(), true);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return ['date' => $day, 'weight_kg' => $kg, 'profile' => profileRow($pdo, $uid)];
    }

    public static function getInsights(PDO $pdo, int $uid, int $weightDays, int $energyDays): array {
        $end = today();
        $weightStart = (new DateTimeImmutable($end))->modify('-' . ($weightDays - 1) . ' days')->format('Y-m-d');
        $energyStart = (new DateTimeImmutable($end))->modify('-' . ($energyDays - 1) . ' days')->format('Y-m-d');
        $profile = profileRow($pdo, $uid);

        $weightRows = all($pdo, 'SELECT log_date date, weight_kg kg FROM weight_logs WHERE user_id=? AND log_date BETWEEN ? AND ? ORDER BY log_date', [$uid, $weightStart, $end]);
        $budgetRows = all($pdo, 'SELECT budget_date date, target_kcal target, exercise_raw_kcal raw, exercise_credit_kcal credit FROM daily_budgets WHERE user_id=? AND budget_date BETWEEN ? AND ?', [$uid, $energyStart, $end]);
        $meals = all($pdo, 'SELECT entry_date date, COUNT(*) count_entries, ROUND(SUM(total_kcal), 1) kcal FROM meal_entries WHERE user_id=? AND entry_date BETWEEN ? AND ? GROUP BY entry_date', [$uid, $energyStart, $end]);
        $workouts = all($pdo, 'SELECT workout_date date, COUNT(*) count_entries, ROUND(SUM(raw_kcal), 1) raw FROM workouts WHERE user_id=? AND workout_date BETWEEN ? AND ? GROUP BY workout_date', [$uid, $energyStart, $end]);

        $map = [];
        foreach ($budgetRows as $r) {
            $map[$r['date']] = ['target' => (float)$r['target'], 'credit' => (float)$r['credit'], 'raw' => (float)$r['raw']];
        }

        $mealMap = [];
        foreach ($meals as $r) $mealMap[$r['date']] = (float)$r['kcal'];

        $workoutMap = [];
        foreach ($workouts as $r) $workoutMap[$r['date']] = (float)$r['raw'];

        $energy = [];
        $cursor = new DateTimeImmutable($energyStart);
        $last = new DateTimeImmutable($end);

        while ($cursor <= $last) {
            $d = $cursor->format('Y-m-d');
            $budget = $map[$d] ?? null;
            $hasMeal = array_key_exists($d, $mealMap);
            $intake = $hasMeal ? $mealMap[$d] : null;
            $dynamic = $budget ? round($budget['target'] + $budget['credit'], 1) : null;

            $energy[] = [
                'date' => $d,
                'has_meals' => $hasMeal,
                'has_budget' => $budget !== null,
                'intake_kcal' => $intake,
                'base_target_kcal' => $budget['target'] ?? null,
                'exercise_raw_kcal' => $workoutMap[$d] ?? ($budget['raw'] ?? 0),
                'exercise_credit_kcal' => $budget['credit'] ?? null,
                'dynamic_budget_kcal' => $dynamic,
                'difference_kcal' => ($hasMeal && $budget) ? round($dynamic - $intake, 1) : null,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        $weight = (float)($profile['weight_kg'] ?? 0);
        $height = (float)$profile['height_cm'];
        $bmi = $weight > 0 && $height > 0 ? round($weight / (($height / 100) ** 2), 1) : null;
        $age = (new DateTimeImmutable($profile['birth_date']))->diff(new DateTimeImmutable($end))->y;
        $category = 'Chưa có dữ liệu';
        if ($bmi !== null) {
            $category = $age < 18 ? 'Cần đánh giá BMI theo tuổi và giới' :
                ($bmi < 18.5 ? 'Dưới khoảng tham khảo' : ($bmi < 25 ? 'Trong khoảng tham khảo' : ($bmi < 30 ? 'Trên khoảng tham khảo' : 'Cao hơn khoảng tham khảo')));
        }

        return [
            'profile' => [
                'bmi' => $bmi,
                'bmi_category' => $category,
                'adult_bmi' => $age >= 18,
                'weight_kg' => $weight ?: null,
                'height_cm' => $height,
                'target_weight_kg' => $profile['target_weight_kg'] === null ? null : (float)$profile['target_weight_kg']
            ],
            'weights' => array_map(static fn($r) => ['date' => $r['date'], 'kg' => (float)$r['kg']], $weightRows),
            'energy' => $energy,
            'weight_days' => $weightDays,
            'energy_days' => $energyDays,
            'notes' => [
                'difference' => 'Ngân sách động − calo đã ăn. Không đồng nghĩa với thâm hụt năng lượng sinh lý.',
                'missing' => 'Ngày chưa ghi bữa ăn không được xem là 0 kcal.'
            ]
        ];
    }

    public static function getDashboard(PDO $pdo, int $uid, ?string $rawDate = null): array {
        $day = dateValue($rawDate ?? today());
        $pdo->beginTransaction();
        try {
            ensureBudget($pdo, $uid, $day);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return [
            'budget' => budgetView($pdo, $uid, $day),
            'meals' => FoodService::listMeals($pdo, $uid, $day),
            'workouts' => WorkoutService::listWorkouts($pdo, $uid, $day),
            'profile' => profileRow($pdo, $uid)
        ];
    }
}

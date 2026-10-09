<?php
declare(strict_types=1);

/** FlexiDiet v1 business rules. Values are product assumptions, not medical advice. */
const ACTIVITY_FACTOR = 1.2;
const GOAL_PERCENT = ['lose' => -0.15, 'maintain' => 0.0, 'gain' => 0.10, 'build_muscle' => 0.10];
const CALORIE_FLOOR = ['male' => 1500.0, 'female' => 1200.0];
function bmrFor(float $weight, float $height, int $age, string $sex, string $formula = 'mifflin_st_jeor', ?float $fat = null): float {
    if ($formula === 'katch_mcardle') {
        if ($fat === null || $fat < 2 || $fat > 70) fail(422, 'BODY_FAT_REQUIRED', 'Cần nhập phần trăm mỡ cơ thể hợp lệ.');
        return round(370 + 21.6 * ($weight * (1 - $fat / 100)), 1);
    }
    return round(10 * $weight + 6.25 * $height - 5 * $age + ($sex === 'male' ? 5 : -161), 1);
}
function targetFor(float $bmr, string $goal, string $sex): array {
    $baseline = round($bmr * ACTIVITY_FACTOR, 1);
    $adjust = round($baseline * GOAL_PERCENT[$goal], 1);
    $floor = CALORIE_FLOOR[$sex];
    return ['baseline' => $baseline, 'adjust' => $adjust, 'target' => max($floor, round($baseline + $adjust, 1)), 'floor_applied' => $baseline + $adjust < $floor ? 1 : 0];
}
function exerciseCredit(float $raw, string $policy): float {
    return round(match ($policy) { 'full' => $raw, 'partial' => $raw * 0.5, 'capped' => min($raw, 500.0) }, 1);
}
function workoutKcal(float $met, float $kg, float $minutes): float { return round($met * $kg * $minutes / 60, 1); }

function profileRow(PDO $pdo, int $uid): array {
    $profile = one($pdo, 'SELECT u.id, u.email, u.display_name, p.*, (SELECT w.weight_kg FROM weight_logs w WHERE w.user_id=u.id AND w.log_date<=CURDATE() ORDER BY w.log_date DESC LIMIT 1) weight_kg FROM users u JOIN user_profiles p ON p.user_id=u.id WHERE u.id=?', [$uid]);
    if (!$profile) fail(404, 'NO_PROFILE', 'Không tìm thấy hồ sơ.');
    return $profile;
}
function weightOn(PDO $pdo, int $uid, string $date): float {
    $row = one($pdo, 'SELECT weight_kg FROM weight_logs WHERE user_id=? AND log_date <= ? ORDER BY log_date DESC LIMIT 1', [$uid, $date]);
    if (!$row) fail(422, 'MISSING_WEIGHT', 'Bạn cần ghi cân nặng trước khi tính calo.');
    return (float)$row['weight_kg'];
}
function makeBudget(PDO $pdo, int $uid, string $date): array {
    $profile = profileRow($pdo, $uid);
    $weight = weightOn($pdo, $uid, $date);
    $birth = new DateTimeImmutable($profile['birth_date']);
    $age = $birth->diff(new DateTimeImmutable($date))->y;
    $bmr = bmrFor($weight, (float)$profile['height_cm'], $age, $profile['sex'], $profile['bmr_formula'], $profile['body_fat_pct'] === null ? null : (float)$profile['body_fat_pct']);
    $values = targetFor($bmr, $profile['goal'], $profile['sex']);
    $policy = $profile['credit_policy'];
    $factor = $policy === 'partial' ? 0.5 : 1.0;
    $cap = $policy === 'capped' ? 500.0 : null;
    return [$weight, $profile['bmr_formula'], $bmr, $values['baseline'], $values['adjust'], $values['floor_applied'], $values['target'], $policy, $factor, $cap];
}
function ensureBudget(PDO $pdo, int $uid, string $date, bool $refreshBase = false): void {
    $existing = one($pdo, 'SELECT id FROM daily_budgets WHERE user_id=? AND budget_date=?', [$uid, $date]);
    if (!$existing || $refreshBase) {
        if ($date !== today()) fail(404, 'NO_HISTORICAL_BUDGET', 'Không có ngân sách lịch sử cho ngày này.');
        $values = makeBudget($pdo, $uid, $date);
        execSql($pdo, 'INSERT INTO daily_budgets (user_id,budget_date,weight_kg_used,bmr_formula,bmr_kcal,baseline_kcal,goal_adjust_kcal,floor_applied,target_kcal,credit_policy,credit_factor,credit_cap_kcal) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE weight_kg_used=VALUES(weight_kg_used),bmr_formula=VALUES(bmr_formula),bmr_kcal=VALUES(bmr_kcal),baseline_kcal=VALUES(baseline_kcal),goal_adjust_kcal=VALUES(goal_adjust_kcal),floor_applied=VALUES(floor_applied),target_kcal=VALUES(target_kcal),credit_policy=VALUES(credit_policy),credit_factor=VALUES(credit_factor),credit_cap_kcal=VALUES(credit_cap_kcal)', [$uid, $date, ...$values]);
    }
    $locked = one($pdo, 'SELECT credit_policy FROM daily_budgets WHERE user_id=? AND budget_date=? FOR UPDATE', [$uid, $date]);
    if (!$locked) fail(500, 'BUDGET_ERROR', 'Không thể khởi tạo ngân sách.');
    $raw = (float)(one($pdo, 'SELECT COALESCE(SUM(raw_kcal),0) s FROM workouts WHERE user_id=? AND workout_date=?', [$uid, $date])['s']);
    execSql($pdo, 'UPDATE daily_budgets SET exercise_raw_kcal=?, exercise_credit_kcal=? WHERE user_id=? AND budget_date=?', [$raw, exerciseCredit($raw, $locked['credit_policy']), $uid, $date]);
}
function budgetView(PDO $pdo, int $uid, string $date): array {
    $b = one($pdo, 'SELECT * FROM daily_budgets WHERE user_id=? AND budget_date=?', [$uid, $date]);
    if (!$b) fail(404, 'BUDGET_NOT_FOUND', 'Chưa có ngân sách cho ngày này.');
    $food = one($pdo, 'SELECT COALESCE(SUM(total_kcal),0) kcal, COALESCE(SUM(total_protein_g),0) protein, COALESCE(SUM(total_carb_g),0) carb, COALESCE(SUM(total_fat_g),0) fat FROM meal_entries WHERE user_id=? AND entry_date=?', [$uid, $date]);
    $water = one($pdo, 'SELECT COALESCE(SUM(amount_ml),0) ml FROM water_logs WHERE user_id=? AND log_date=?', [$uid, $date]);
    $out = [
        'date' => $date, 'bmr_kcal' => (float)$b['bmr_kcal'], 'baseline_kcal' => (float)$b['baseline_kcal'],
        'goal_adjust_kcal' => (float)$b['goal_adjust_kcal'], 'floor_applied' => (bool)$b['floor_applied'],
        'target_kcal' => (float)$b['target_kcal'], 'exercise_raw_kcal' => (float)$b['exercise_raw_kcal'],
        'exercise_credit_kcal' => (float)$b['exercise_credit_kcal'], 'credit_policy' => $b['credit_policy'],
        'consumed_kcal' => (float)$food['kcal'], 'protein_g' => (float)$food['protein'],
        'carb_g' => (float)$food['carb'], 'fat_g' => (float)$food['fat'], 'water_ml' => (int)$water['ml'],
    ];
    $out['remaining_kcal'] = round($out['target_kcal'] + $out['exercise_credit_kcal'] - $out['consumed_kcal'], 1);
    return $out;
}

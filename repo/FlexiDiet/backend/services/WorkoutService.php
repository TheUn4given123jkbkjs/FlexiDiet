<?php
declare(strict_types=1);

/**
 * WorkoutService - Nghiệp vụ vận động, tính toán calo tiêu thụ và điều chỉnh ngân sách
 */
class WorkoutService {
    public static function listExercises(PDO $pdo): array {
        return WorkoutModel::listExerciseTypes($pdo);
    }

    public static function listWorkouts(PDO $pdo, int $uid, ?string $date = null, ?int $year = null): array {
        return WorkoutModel::listWorkouts($pdo, $uid, $date, $year);
    }

    public static function createWorkout(PDO $pdo, int $uid, array $params): array {
        $day = dateValue($params['workout_date'] ?? today());
        $code = choice($params, 'exercise_code', ['running', 'cycling', 'gym', 'swimming', 'hiit', 'jump_rope', 'walking', 'yoga', 'badminton', 'football']);
        $duration = num($params, 'duration_min', 1, 600);
        $distance = isset($params['distance_km']) && $params['distance_km'] !== '' ? num($params, 'distance_km', 0, 500) : null;
        $source = choice($params, 'calorie_source', ['met', 'device'], 'met');

        $type = WorkoutModel::findExerciseTypeByCode($pdo, $code);
        if (!$type || ($source === 'met' && $type['met'] === null)) {
            fail(422, 'EXERCISE_SEEDS_REQUIRED', 'Cần import database/seeds/06_exercise_types_and_met.sql.');
        }

        $note = isset($params['note']) ? mb_substr(trim((string)$params['note']), 0, 255) : null;
        $weight = $source === 'met' ? weightOn($pdo, $uid, $day) : null;
        $met = $source === 'met' ? (float)$type['met'] : null;
        $raw = $source === 'met' ? workoutKcal($met, $weight, $duration) : num($params, 'raw_kcal', 0, 5000);

        $pdo->beginTransaction();
        try {
            if ($day === today()) {
                ensureBudget($pdo, $uid, $day);
            }
            $id = WorkoutModel::insertWorkout($pdo, $uid, $day, (int)$type['id'], $duration, $distance, $source, $met, $weight, $raw, $note);
            if ($day === today() || one($pdo, 'SELECT id FROM daily_budgets WHERE user_id = ? AND budget_date = ?', [$uid, $day])) {
                ensureBudget($pdo, $uid, $day);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return WorkoutModel::findWorkoutById($pdo, $id, $uid);
    }

    public static function deleteWorkout(PDO $pdo, int $uid, int $id): void {
        $pdo->beginTransaction();
        try {
            $workoutDate = WorkoutModel::deleteWorkout($pdo, $id, $uid);
            if (!$workoutDate) {
                fail(404, 'NOT_FOUND', 'Không tìm thấy buổi tập.');
            }
            if ($workoutDate === today() || one($pdo, 'SELECT id FROM daily_budgets WHERE user_id = ? AND budget_date = ?', [$uid, $workoutDate])) {
                ensureBudget($pdo, $uid, $workoutDate);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function getHeatmap(PDO $pdo, int $uid, int $year): array {
        return WorkoutModel::getHeatmap($pdo, $uid, $year);
    }

    public static function recordWater(PDO $pdo, int $uid, array $params): array {
        $amount = (int)num($params, 'amount_ml', 1, 3000);
        $day = dateValue($params['log_date'] ?? today());
        WorkoutModel::recordWater($pdo, $uid, $day, $amount);
        $total = (int)one($pdo, 'SELECT SUM(amount_ml) total FROM water_logs WHERE user_id = ? AND log_date = ?', [$uid, $day])['total'];
        return ['amount_ml' => $amount, 'water_total_ml' => $total];
    }
}

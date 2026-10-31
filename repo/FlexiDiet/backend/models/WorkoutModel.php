<?php
declare(strict_types=1);

/**
 * WorkoutModel - Quản lý truy xuất dữ liệu bài tập, quy tắc MET, calo đốt cháy và nước uống
 */
class WorkoutModel {
    public static function listExerciseTypes(PDO $pdo): array {
        return all($pdo, '
            SELECT 
                t.id, t.code, t.name_vi, t.uses_distance,
                (SELECT r.met FROM exercise_met_rules r WHERE r.exercise_type_id = t.id ORDER BY r.id LIMIT 1) AS default_met 
            FROM exercise_types t 
            WHERE t.is_active = 1 
            ORDER BY t.id'
        );
    }

    public static function findExerciseTypeByCode(PDO $pdo, string $code): ?array {
        return one($pdo, '
            SELECT t.id, r.met 
            FROM exercise_types t 
            LEFT JOIN exercise_met_rules r ON r.exercise_type_id = t.id 
            WHERE t.code = ? AND t.is_active = 1 
            ORDER BY r.id LIMIT 1',
            [$code]
        );
    }

    public static function listWorkouts(PDO $pdo, int $uid, ?string $date = null, ?int $year = null): array {
        if ($year !== null) {
            return all($pdo, '
                SELECT 
                    w.id, w.workout_date, w.duration_min, w.distance_km, w.raw_kcal, 
                    w.calorie_source, w.confidence, w.note, w.created_at, 
                    t.code AS exercise_code, t.name_vi AS exercise_name 
                FROM workouts w 
                JOIN exercise_types t ON t.id = w.exercise_type_id 
                WHERE w.user_id = ? AND w.workout_date BETWEEN ? AND ? 
                ORDER BY w.workout_date DESC, w.id DESC 
                LIMIT 1000',
                [$uid, "$year-01-01", "$year-12-31"]
            );
        }
        return all($pdo, '
            SELECT 
                w.id, w.workout_date, w.duration_min, w.distance_km, w.raw_kcal, 
                w.calorie_source, w.confidence, w.note, w.created_at, 
                t.code AS exercise_code, t.name_vi AS exercise_name 
            FROM workouts w 
            JOIN exercise_types t ON t.id = w.exercise_type_id 
            WHERE w.user_id = ? AND w.workout_date = ? 
            ORDER BY w.id DESC',
            [$uid, $date]
        );
    }

    public static function findWorkoutById(PDO $pdo, int $id, int $uid): ?array {
        return one($pdo, '
            SELECT 
                w.id, w.workout_date, w.duration_min, w.raw_kcal, w.calorie_source, 
                t.code AS exercise_code, t.name_vi AS exercise_name 
            FROM workouts w 
            JOIN exercise_types t ON t.id = w.exercise_type_id 
            WHERE w.id = ? AND w.user_id = ?',
            [$id, $uid]
        );
    }

    public static function insertWorkout(
        PDO $pdo,
        int $uid,
        string $day,
        int $typeId,
        float $duration,
        ?float $distance,
        string $source,
        ?float $met,
        ?float $weight,
        float $raw,
        ?string $note
    ): int {
        execSql($pdo, '
            INSERT INTO workouts (
                user_id, workout_date, exercise_type_id, duration_min, distance_km, 
                calorie_source, confidence, met_value, weight_kg_used, raw_kcal, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $uid, $day, $typeId, $duration, $distance,
                $source, $source === 'met' ? 'medium' : 'high',
                $met, $weight, $raw, $note
            ]
        );
        return (int)$pdo->lastInsertId();
    }

    public static function deleteWorkout(PDO $pdo, int $id, int $uid): ?string {
        $old = one($pdo, 'SELECT workout_date FROM workouts WHERE id = ? AND user_id = ? FOR UPDATE', [$id, $uid]);
        if (!$old) return null;
        execSql($pdo, 'DELETE FROM workouts WHERE id = ? AND user_id = ?', [$id, $uid]);
        return (string)$old['workout_date'];
    }

    public static function getHeatmap(PDO $pdo, int $uid, int $year): array {
        return all($pdo, '
            SELECT 
                workout_date AS date, COUNT(*) AS sessions, 
                SUM(duration_min) AS minutes, ROUND(SUM(raw_kcal), 1) AS raw_kcal 
            FROM workouts 
            WHERE user_id = ? AND workout_date BETWEEN ? AND ? 
            GROUP BY workout_date 
            ORDER BY workout_date',
            [$uid, "$year-01-01", "$year-12-31"]
        );
    }

    public static function recordWater(PDO $pdo, int $uid, string $day, int $amountMl): void {
        execSql($pdo, '
            INSERT INTO water_logs (user_id, log_date, amount_ml) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE amount_ml = amount_ml + VALUES(amount_ml)',
            [$uid, $day, $amountMl]
        );
    }
}

<?php
declare(strict_types=1);

/**
 * WorkoutController - Tiếp nhận và xử lý HTTP requests cho bài tập, calo tiêu thụ và nước uống
 */
class WorkoutController {
    public static function listExercises(PDO $pdo): void {
        response(WorkoutService::listExercises($pdo));
    }

    public static function listWorkouts(PDO $pdo, int $uid): void {
        if (isset($_GET['year'])) {
            $year = (int)num($_GET, 'year', 2000, (int)date('Y'));
            response(WorkoutService::listWorkouts($pdo, $uid, null, $year));
        }
        $date = dateValue($_GET['date'] ?? today());
        response(WorkoutService::listWorkouts($pdo, $uid, $date));
    }

    public static function createWorkout(PDO $pdo, int $uid, array $params): void {
        response(WorkoutService::createWorkout($pdo, $uid, $params), 201);
    }

    public static function deleteWorkout(PDO $pdo, int $uid, int $id): void {
        WorkoutService::deleteWorkout($pdo, $uid, $id);
        response(['deleted' => true]);
    }

    public static function getHeatmap(PDO $pdo, int $uid): void {
        $year = (int)num($_GET, 'year', 2000, (int)date('Y'), (float)date('Y'));
        response(WorkoutService::getHeatmap($pdo, $uid, $year));
    }

    public static function recordWater(PDO $pdo, int $uid, array $params): void {
        response(WorkoutService::recordWater($pdo, $uid, $params), 201);
    }
}

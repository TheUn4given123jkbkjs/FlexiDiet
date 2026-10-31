<?php
declare(strict_types=1);

/**
 * FoodController - Bộ điều khiển HTTP cho tìm kiếm món, nguyên liệu, công thức và ghi nhận bữa ăn
 */
class FoodController {
    public static function searchFoods(PDO $pdo, int $uid): void {
        $q = (string)($_GET['q'] ?? '');
        response(FoodService::searchFoods($pdo, $uid, $q));
    }

    public static function getRecipe(PDO $pdo, int $uid, int $dishId): void {
        response(FoodService::getRecipe($pdo, $uid, $dishId));
    }

    public static function searchIngredients(PDO $pdo, int $uid): void {
        $q = (string)($_GET['q'] ?? '');
        response(FoodService::searchIngredients($pdo, $uid, $q));
    }

    public static function createCustomDish(PDO $pdo, int $uid, array $params): void {
        response(FoodService::createCustomDish($pdo, $uid, $params), 201);
    }

    public static function listMeals(PDO $pdo, int $uid): void {
        $date = (string)($_GET['date'] ?? today());
        response(FoodService::listMeals($pdo, $uid, $date));
    }

    public static function createMeal(PDO $pdo, int $uid, array $params): void {
        response(FoodService::createMeal($pdo, $uid, $params), 201);
    }

    public static function deleteMeal(PDO $pdo, int $uid, int $mealId): void {
        FoodService::deleteMeal($pdo, $uid, $mealId);
        response(['deleted' => true]);
    }
}

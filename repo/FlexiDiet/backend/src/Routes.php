<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AdminRoutes.php';
require_once __DIR__ . '/ENews.php';

// ============================================================================
// BACKWARD COMPATIBILITY HELPERS (Delegating to MVC Services & Models)
// ============================================================================
function authData(PDO $pdo, int $uid): array {
    return AuthService::authData($pdo, $uid);
}
function registration(PDO $pdo, array $p): array {
    return AuthService::register($pdo, $p);
}
function login(PDO $pdo, array $p): array {
    return AuthService::login($pdo, $p);
}
function ensurePasswordResetTable(PDO $pdo): void {
    UserModel::ensurePasswordResetTable($pdo);
}
function requestPasswordReset(PDO $pdo, array $p): array {
    return AuthService::requestPasswordReset($pdo, $p);
}
function resetPassword(PDO $pdo, array $p): array {
    return AuthService::resetPassword($pdo, $p);
}
function ensureProfileAvatarTable(PDO $pdo): void {
    UserModel::ensureProfileAvatarTable($pdo);
}
function uploadProfileAvatar(PDO $pdo, int $uid): array {
    ProfileController::uploadAvatar($pdo, $uid);
    return ['updated' => true];
}
function serveProfileAvatar(PDO $pdo, int $uid): never {
    ProfileController::getAvatar($pdo, $uid);
}
function deleteProfileAvatar(PDO $pdo, int $uid): array {
    UserModel::deleteAvatar($pdo, $uid);
    return ['deleted' => true];
}
function updateProfile(PDO $pdo, int $uid, array $p): array {
    return ProfileService::updateProfile($pdo, $uid, $p);
}
function recordWeight(PDO $pdo, int $uid, array $payload): array {
    return ProfileService::recordWeight($pdo, $uid, $payload);
}
function profileInsights(PDO $pdo, int $uid, int $weightDays, int $energyDays): array {
    return ProfileService::getInsights($pdo, $uid, $weightDays, $energyDays);
}
function mealList(PDO $pdo, int $uid, string $day): array {
    return FoodService::listMeals($pdo, $uid, $day);
}
function workoutList(PDO $pdo, int $uid, ?string $date = null, ?int $year = null): array {
    return WorkoutService::listWorkouts($pdo, $uid, $date, $year);
}
function createWorkout(PDO $pdo, int $uid, array $p): array {
    return WorkoutService::createWorkout($pdo, $uid, $p);
}
function deleteWorkout(PDO $pdo, int $uid, int $id): void {
    WorkoutService::deleteWorkout($pdo, $uid, $id);
}
function normalizeVietnamese(string $str): string {
    return FoodService::normalizeVietnamese($str);
}
function dishRecipe(PDO $pdo, int $uid, int $dishId): array {
    return FoodService::getRecipe($pdo, $uid, $dishId);
}
function searchIngredients(PDO $pdo, int $uid, string $q): array {
    return FoodService::searchIngredients($pdo, $uid, $q);
}
function createCustomDish(PDO $pdo, int $uid, array $p): array {
    return FoodService::createCustomDish($pdo, $uid, $p);
}
function createMeal(PDO $pdo, int $uid, array $p): array {
    return FoodService::createMeal($pdo, $uid, $p);
}
function requireAdmin(PDO $pdo, int $uid): void {
    $u = one($pdo, 'SELECT role FROM users WHERE id=?', [$uid]);
    if (!$u || $u['role'] !== 'admin') fail(403, 'FORBIDDEN', 'Yêu cầu quyền Quản trị viên.');
}
function proxyAiGet(string $path, int $timeout = 3): ?array {
    return AiService::proxyGet($path, $timeout);
}
function recognizeFood(PDO $pdo, int $uid): array {
    return AiService::recognize($pdo, $uid);
}
function ensureEnNewsTables(PDO $pdo): void {
    ENewsModel::ensureTables($pdo);
}
function enewsUploadMedia(PDO $pdo, int $uid): array {
    return enewsInteractionUpload($pdo, $uid);
}
function enewsPostDetail(PDO $pdo, int $uid, int $postId): array {
    return enewsInteractionPostDetail($pdo, $uid, $postId);
}
function enewsFeed(PDO $pdo, int $uid, int $limit = 12, int $offset = 0): array {
    return enewsInteractionFeed($pdo, $uid, $limit, $offset);
}
function enewsCreatePost(PDO $pdo, int $uid, array $p): array {
    return enewsInteractionCreatePost($pdo, $uid, $p);
}
function enewsUpdatePost(PDO $pdo, int $uid, int $postId, array $p): array {
    return enewsInteractionUpdatePost($pdo, $uid, $postId, $p);
}
function enewsDeletePost(PDO $pdo, int $uid, int $postId): array {
    return enewsInteractionDeletePost($pdo, $uid, $postId);
}
function enewsToggleLike(PDO $pdo, int $uid, int $postId, bool $liked): array {
    return enewsInteractionToggleLike($pdo, $uid, $postId, $liked);
}

// ============================================================================
// MAIN HTTP REQUEST DISPATCHER (Router)
// ============================================================================
function executeRoute(string $route, string $method): never {
    // 1. System Health & CSRF
    if ($route === '/ping' && $method === 'GET') response(['status' => 'ok', 'service' => 'FlexiDiet PHP API']);
    if ($route === '/health' && $method === 'GET') { db()->query('SELECT 1'); response(['status' => 'ok', 'database' => 'connected']); }
    if ($route === '/auth/csrf' && $method === 'GET') response(['csrf_token' => sessionCsrf()]);
    if ($method !== 'GET') verifyCsrf();

    // 2. Authentication & Password Reset
    if ($route === '/auth/register' && $method === 'POST') AuthController::register(db(), body());
    if ($route === '/auth/login' && $method === 'POST') AuthController::login(db(), body());
    if ($route === '/auth/forgot-password' && $method === 'POST') AuthController::requestPasswordReset(db(), body());
    if ($route === '/auth/reset-password' && $method === 'POST') AuthController::resetPassword(db(), body());
    if ($route === '/auth/logout' && $method === 'POST') AuthController::logout();
    if ($route === '/auth/me' && $method === 'GET') AuthController::me(db(), currentUserId());

    $uid = currentUserId();
    $pdo = db();

    // 3. User Profile, Weight & Analytics
    if (preg_match('~^/profile/avatar/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
        enewsInteractionAvatar($pdo, $uid, (int)$m[1]);
    }
    if ($route === '/profile/avatar' && $method === 'GET') ProfileController::getAvatar($pdo, $uid);
    if ($route === '/profile/avatar' && $method === 'POST') ProfileController::uploadAvatar($pdo, $uid);
    if ($route === '/profile/avatar' && $method === 'DELETE') ProfileController::deleteAvatar($pdo, $uid);
    if ($route === '/profile' && $method === 'GET') ProfileController::getProfile($pdo, $uid);
    if ($route === '/profile' && $method === 'PATCH') ProfileController::updateProfile($pdo, $uid, body());
    if ($route === '/weight-logs' && $method === 'POST') ProfileController::recordWeight($pdo, $uid, body());
    if ($route === '/profile/insights' && $method === 'GET') {
        $w = (int)num($_GET, 'weight_days', 30, 1825, 365);
        $e = (int)num($_GET, 'energy_days', 7, 365, 7);
        if (!in_array($w, [30, 90, 365, 1825], true) || !in_array($e, [7, 30, 365], true)) {
            fail(422, 'INVALID_PERIOD', 'Khoảng thời gian không được hỗ trợ.');
        }
        ProfileController::getInsights($pdo, $uid, $w, $e);
    }
    if ($route === '/dashboard' && $method === 'GET') {
        ProfileController::getDashboard($pdo, $uid, $_GET['date'] ?? null);
    }

    // 4. Social Network (FlexiDiet eNews)
    if (str_starts_with($route, '/enews/')) {
        if (ENewsController::handle($pdo, $uid, $route, $method)) exit;
    }

    // 5. Foods, Recipes & Meals
    if ($route === '/foods/suggestions' && $method === 'GET') FoodController::getSuggestions($pdo, $uid);
    if ($route === '/foods' && $method === 'GET') FoodController::searchFoods($pdo, $uid);
    if (preg_match('~^/foods/([1-9][0-9]*)/recipe$~D', $route, $m) && $method === 'GET') {
        FoodController::getRecipe($pdo, $uid, (int)$m[1]);
    }
    if ($route === '/ingredients' && $method === 'GET') FoodController::searchIngredients($pdo, $uid);
    if ($route === '/dishes' && $method === 'POST') FoodController::createCustomDish($pdo, $uid, body());
    if ($route === '/meals' && $method === 'GET') FoodController::listMeals($pdo, $uid);
    if ($route === '/meals' && $method === 'POST') FoodController::createMeal($pdo, $uid, body());
    if (preg_match('~^/meals/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        FoodController::deleteMeal($pdo, $uid, (int)$m[1]);
    }

    // 6. Workouts, Water & Activity Heatmap
    if ($route === '/exercises' && $method === 'GET') WorkoutController::listExercises($pdo);
    if ($route === '/workouts' && $method === 'GET') WorkoutController::listWorkouts($pdo, $uid);
    if ($route === '/workouts/heatmap' && $method === 'GET') WorkoutController::getHeatmap($pdo, $uid);
    if ($route === '/workouts' && $method === 'POST') WorkoutController::createWorkout($pdo, $uid, body());
    if (preg_match('~^/workouts/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        WorkoutController::deleteWorkout($pdo, $uid, (int)$m[1]);
    }
    if ($route === '/water' && $method === 'POST') WorkoutController::recordWater($pdo, $uid, body());

    // 7. AI Food Recognition & Feedback
    if ($route === '/ai/recognize' && $method === 'POST') AiController::recognize($pdo, $uid);
    if ($route === '/ai/feedback' && $method === 'POST') AiController::feedback($pdo, $uid, body());

    // 8. Admin Studio & Observability
    if (str_starts_with($route, '/admin/')) {
        requireAdmin($pdo, $uid);
        if (AdminController::handle($pdo, $uid, $route, $method)) exit;
    }

    fail(404, 'ENDPOINT_NOT_FOUND', 'API không tồn tại hoặc phương thức không hỗ trợ.');
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AdminRoutes.php';
require_once __DIR__ . '/ENews.php';

function authData(PDO $pdo, int $uid): array {
    $user = one($pdo, 'SELECT id,display_name,email,role FROM users WHERE id=?', [$uid]);
    if (!$user) { unset($_SESSION['uid']); fail(401, 'UNAUTHENTICATED', 'Phiên đăng nhập không tồn tại.'); }
    return ['user' => $user, 'csrf_token' => sessionCsrf()];
}
function registration(PDO $pdo, array $p): array {
    $name = requiredString($p, 'display_name', 100);
    $email = mb_strtolower(requiredString($p, 'email', 255));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Địa chỉ email không hợp lệ.');
    $password = requiredString($p, 'password', 200);
    if (strlen($password) < 8 || strlen($password) > 72) fail(422, 'INVALID_PASSWORD', 'Mật khẩu phải từ 8–72 byte.');
    $sex = choice($p, 'sex', ['male', 'female']);
    $birth = dateValue($p['birth_date'] ?? null);
    $age = (new DateTimeImmutable($birth))->diff(new DateTimeImmutable(today()))->y;
    if ($age < 13 || $age > 110) fail(422, 'INVALID_AGE', 'Tuổi phải từ 13 đến 110.');
    $height = num($p, 'height_cm', 90, 250);
    $weight = num($p, 'weight_kg', 20, 400);
    $goal = choice($p, 'goal', ['lose', 'maintain', 'gain', 'build_muscle']);
    $target = isset($p['target_weight_kg']) && $p['target_weight_kg'] !== '' ? num($p, 'target_weight_kg', 20, 400) : null;
    $weekly = (int)num($p, 'weekly_workout_goal', 0, 7, 3);
    $pdo->beginTransaction();
    try {
        if (one($pdo, 'SELECT id FROM users WHERE email=?', [$email])) fail(409, 'EMAIL_EXISTS', 'Email này đã được đăng ký.');
        execSql($pdo, 'INSERT INTO users (email,password_hash,display_name) VALUES (?,?,?)', [$email, password_hash($password, PASSWORD_DEFAULT), $name]);
        $uid = (int)$pdo->lastInsertId();
        execSql($pdo, 'INSERT INTO user_profiles (user_id,sex,birth_date,height_cm,goal,target_weight_kg,weekly_workout_goal) VALUES (?,?,?,?,?,?,?)', [$uid,$sex,$birth,$height,$goal,$target,$weekly]);
        execSql($pdo, 'INSERT INTO weight_logs (user_id,log_date,weight_kg) VALUES (?,?,?)', [$uid,today(),$weight]);
        ensureBudget($pdo, $uid, today());
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return authData($pdo, $uid);
}
function login(PDO $pdo, array $p): array {
    $email = mb_strtolower(requiredString($p, 'email', 255));
    $password = requiredString($p, 'password', 200);
    $user = one($pdo, 'SELECT id,password_hash FROM users WHERE email=?', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) fail(401, 'INVALID_CREDENTIALS', 'Email hoặc mật khẩu không đúng.');
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return authData($pdo, (int)$user['id']);
}
function ensurePasswordResetTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_otps (
        email VARCHAR(255) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        sent_at DATETIME NOT NULL,
        PRIMARY KEY (email),
        KEY idx_password_reset_user_id (user_id),
        CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function requestPasswordReset(PDO $pdo, array $p): array {
    $email = mb_strtolower(requiredString($p, 'email', 255));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Địa chỉ email không hợp lệ.');
    ensurePasswordResetTable($pdo);
    $genericResponse = ['message' => 'Nếu email đã đăng ký, mã OTP sẽ được gửi trong ít phút.'];
    $user = one($pdo, 'SELECT id,display_name FROM users WHERE email=?', [$email]);
    if (!$user) return $genericResponse;

    $existing = one($pdo, 'SELECT sent_at FROM password_reset_otps WHERE email=?', [$email]);
    if ($existing && strtotime($existing['sent_at']) > time() - 60) return $genericResponse;

    $otp = (string)random_int(100000, 999999);
    $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))
        ->modify('+10 minutes')->format('Y-m-d H:i:s');
    $message = "Xin chào {$user['display_name']},\n\n"
        . "Mã OTP đặt lại mật khẩu FlexiDiet của bạn là: $otp\n"
        . "Mã có hiệu lực trong 10 phút và chỉ sử dụng được một lần.\n\n"
        . "Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.";
    try {
        sendFlexiDietEmail($email, 'Ma OTP dat lai mat khau FlexiDiet', $message);
    } catch (Throwable $error) {
        error_log('FlexiDiet password reset email failed: ' . $error->getMessage());
        fail(503, 'EMAIL_UNAVAILABLE', 'Chưa thể gửi email lúc này. Vui lòng thử lại sau.');
    }

    execSql($pdo, 'INSERT INTO password_reset_otps (email,user_id,otp_hash,expires_at,attempts,sent_at)
        VALUES (?,?,?,?,0,NOW()) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),otp_hash=VALUES(otp_hash),
        expires_at=VALUES(expires_at),attempts=0,sent_at=NOW()',
        [$email, (int)$user['id'], password_hash($otp, PASSWORD_DEFAULT), $expiresAt]);
    return $genericResponse;
}
function resetPassword(PDO $pdo, array $p): array {
    $email = mb_strtolower(requiredString($p, 'email', 255));
    $otp = requiredString($p, 'otp', 6);
    $password = requiredString($p, 'password', 200);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
    if (!preg_match('/^\d{6}$/D', $otp)) fail(422, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
    if (strlen($password) < 8 || strlen($password) > 72) fail(422, 'INVALID_PASSWORD', 'Mật khẩu phải từ 8–72 byte.');

    ensurePasswordResetTable($pdo);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT user_id,otp_hash,expires_at,attempts FROM password_reset_otps WHERE email=? FOR UPDATE');
        $stmt->execute([$email]);
        $reset = $stmt->fetch();
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (!$reset || (int)$reset['attempts'] >= 5 || new DateTimeImmutable($reset['expires_at']) <= $now) {
            if ($reset) execSql($pdo, 'DELETE FROM password_reset_otps WHERE email=?', [$email]);
            $pdo->commit();
            fail(400, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
        }
        if (!password_verify($otp, $reset['otp_hash'])) {
            execSql($pdo, 'UPDATE password_reset_otps SET attempts=attempts+1 WHERE email=?', [$email]);
            $pdo->commit();
            fail(400, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
        }
        execSql($pdo, 'UPDATE users SET password_hash=? WHERE id=?',
            [password_hash($password, PASSWORD_DEFAULT), (int)$reset['user_id']]);
        execSql($pdo, 'DELETE FROM password_reset_otps WHERE email=?', [$email]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    return ['message' => 'Mật khẩu đã được đặt lại. Bạn có thể đăng nhập bằng mật khẩu mới.'];
}
function ensureProfileAvatarTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile_avatars (
        user_id BIGINT UNSIGNED NOT NULL,
        mime_type VARCHAR(32) NOT NULL,
        image_data MEDIUMBLOB NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_profile_avatars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function uploadProfileAvatar(PDO $pdo, int $uid): array {
    ensureProfileAvatarTable($pdo);
    $file = $_FILES['avatar'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        fail(422, 'AVATAR_UPLOAD_FAILED', 'Không nhận được ảnh. Vui lòng chọn ảnh và thử lại.');
    }
    if (!isset($file['tmp_name'], $file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > 2 * 1024 * 1024) {
        fail(422, 'AVATAR_SIZE_INVALID', 'Ảnh phải có dung lượng tối đa 2 MB.');
    }
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) fail(422, 'AVATAR_UPLOAD_FAILED', 'Không thể đọc ảnh đã tải lên.');
    $imageInfo = @getimagesizefromstring($imageData);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($imageData);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!$imageInfo || !in_array($mime, $allowed, true) || $imageInfo['mime'] !== $mime) {
        fail(422, 'AVATAR_TYPE_INVALID', 'Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP hợp lệ.');
    }
    if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000 || $imageInfo[0] * $imageInfo[1] > 20000000) {
        fail(422, 'AVATAR_DIMENSIONS_INVALID', 'Kích thước ảnh quá lớn. Hãy chọn ảnh tối đa 6000 × 6000 pixel.');
    }
    $stmt = $pdo->prepare('INSERT INTO profile_avatars (user_id,mime_type,image_data) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE mime_type=VALUES(mime_type),image_data=VALUES(image_data),updated_at=CURRENT_TIMESTAMP');
    $stmt->bindValue(1, $uid, PDO::PARAM_INT);
    $stmt->bindValue(2, $mime, PDO::PARAM_STR);
    $stmt->bindValue(3, $imageData, PDO::PARAM_LOB);
    $stmt->execute();
    return ['updated' => true];
}
function serveProfileAvatar(PDO $pdo, int $uid): never {
    ensureProfileAvatarTable($pdo);
    $avatar = one($pdo, 'SELECT mime_type,image_data FROM profile_avatars WHERE user_id=?', [$uid]);
    if (!$avatar) fail(404, 'AVATAR_NOT_FOUND', 'Bạn chưa cài ảnh đại diện.');
    header('Content-Type: ' . $avatar['mime_type']);
    header('Content-Length: ' . strlen($avatar['image_data']));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    echo $avatar['image_data'];
    exit;
}
function deleteProfileAvatar(PDO $pdo, int $uid): array {
    ensureProfileAvatarTable($pdo);
    execSql($pdo, 'DELETE FROM profile_avatars WHERE user_id=?', [$uid]);
    return ['deleted' => true];
}
function updateProfile(PDO $pdo, int $uid, array $p): array {
    $current = profileRow($pdo, $uid);
    $name = isset($p['display_name']) ? requiredString($p, 'display_name', 100) : $current['display_name'];
    $height = isset($p['height_cm']) ? num($p, 'height_cm', 90, 250) : (float)$current['height_cm'];
    $goal = isset($p['goal']) ? choice($p, 'goal', ['lose','maintain','gain','build_muscle']) : $current['goal'];
    $policy = isset($p['credit_policy']) ? choice($p, 'credit_policy', ['full','partial','capped']) : $current['credit_policy'];
    $target = array_key_exists('target_weight_kg', $p) ? ($p['target_weight_kg'] === null ? null : num($p,'target_weight_kg',20,400)) : $current['target_weight_kg'];
    $weight = isset($p['weight_kg']) ? num($p, 'weight_kg', 20, 400) : null;
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'UPDATE users SET display_name=? WHERE id=?', [$name,$uid]);
        execSql($pdo, 'UPDATE user_profiles SET height_cm=?, goal=?, target_weight_kg=?, credit_policy=? WHERE user_id=?', [$height,$goal,$target,$policy,$uid]);
        if ($weight !== null) execSql($pdo, 'INSERT INTO weight_logs (user_id, log_date, weight_kg) VALUES (?,?,?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg)', [$uid,today(),$weight]);
        ensureBudget($pdo, $uid, today(), true);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return profileRow($pdo, $uid);
}
/** Record one measured weight per day. Existing weight_logs UNIQUE(user_id,log_date)
 * gives idempotent edits, and historical daily budgets remain frozen. */
function recordWeight(PDO $pdo, int $uid, array $payload): array {
    $day = dateValue($payload['log_date'] ?? today());
    $kg = num($payload, 'weight_kg', 20, 400);
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'INSERT INTO weight_logs (user_id,log_date,weight_kg) VALUES (?,?,?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg)', [$uid,$day,$kg]);
        // Only today's snapshot changes. Never re-write an older daily budget.
        ensureBudget($pdo,$uid,today(),true);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return ['date'=>$day,'weight_kg'=>$kg,'profile'=>profileRow($pdo,$uid)];
}

/** Read-only analytics: no fictitious activity, no zero-intake assumption.
 * Missing food logs make the comparison unavailable even when a budget exists. */
function profileInsights(PDO $pdo, int $uid, int $weightDays, int $energyDays): array {
    $end=today();
    $weightStart=(new DateTimeImmutable($end))->modify('-'.($weightDays-1).' days')->format('Y-m-d');
    $energyStart=(new DateTimeImmutable($end))->modify('-'.($energyDays-1).' days')->format('Y-m-d');
    $profile=profileRow($pdo,$uid);
    $weightRows=all($pdo,'SELECT log_date date,weight_kg kg FROM weight_logs WHERE user_id=? AND log_date BETWEEN ? AND ? ORDER BY log_date',[$uid,$weightStart,$end]);
    $budgetRows=all($pdo,'SELECT budget_date date,target_kcal target,exercise_raw_kcal raw,exercise_credit_kcal credit FROM daily_budgets WHERE user_id=? AND budget_date BETWEEN ? AND ?',[$uid,$energyStart,$end]);
    $meals=all($pdo,'SELECT entry_date date,COUNT(*) count_entries,ROUND(SUM(total_kcal),1) kcal FROM meal_entries WHERE user_id=? AND entry_date BETWEEN ? AND ? GROUP BY entry_date',[$uid,$energyStart,$end]);
    $workouts=all($pdo,'SELECT workout_date date,COUNT(*) count_entries,ROUND(SUM(raw_kcal),1) raw FROM workouts WHERE user_id=? AND workout_date BETWEEN ? AND ? GROUP BY workout_date',[$uid,$energyStart,$end]);
    $map=[];
    foreach ($budgetRows as $r) { $d=$r['date']; $map[$d]=['target'=>(float)$r['target'],'credit'=>(float)$r['credit'],'raw'=>(float)$r['raw']]; }
    $mealMap=[];
    foreach ($meals as $r) $mealMap[$r['date']]=(float)$r['kcal'];
    $workoutMap=[];
    foreach ($workouts as $r) $workoutMap[$r['date']]=(float)$r['raw'];
    $energy=[];
    $cursor=new DateTimeImmutable($energyStart); $last=new DateTimeImmutable($end);
    while ($cursor <= $last) {
        $d=$cursor->format('Y-m-d'); $budget=$map[$d] ?? null;
        $hasMeal=array_key_exists($d,$mealMap);
        $intake=$hasMeal?$mealMap[$d]:null;
        $dynamic=$budget ? round($budget['target']+$budget['credit'],1):null;
        $energy[]=[
            'date'=>$d,'has_meals'=>$hasMeal,'has_budget'=>$budget!==null,
            'intake_kcal'=>$intake,'base_target_kcal'=>$budget['target'] ?? null,
            'exercise_raw_kcal'=>$workoutMap[$d] ?? ($budget['raw'] ?? 0),
            'exercise_credit_kcal'=>$budget['credit'] ?? null,
            'dynamic_budget_kcal'=>$dynamic,
            'difference_kcal'=>($hasMeal && $budget) ? round($dynamic-$intake,1):null,
        ];
        $cursor=$cursor->modify('+1 day');
    }
    $weight=(float)($profile['weight_kg'] ?? 0);
    $height=(float)$profile['height_cm'];
    $bmi=$weight>0 && $height>0 ? round($weight/(($height/100)**2),1):null;
    $age=(new DateTimeImmutable($profile['birth_date']))->diff(new DateTimeImmutable($end))->y;
    $category='Chưa có dữ liệu';
    if ($bmi!==null) {
        $category=$age<18 ? 'Cần đánh giá BMI theo tuổi và giới' :
          ($bmi<18.5?'Dưới khoảng tham khảo':($bmi<25?'Trong khoảng tham khảo':($bmi<30?'Trên khoảng tham khảo':'Cao hơn khoảng tham khảo')));
    }
    return [
        'profile'=>['bmi'=>$bmi,'bmi_category'=>$category,'adult_bmi'=>$age>=18,'weight_kg'=>$weight ?: null,'height_cm'=>$height,'target_weight_kg'=>$profile['target_weight_kg']===null?null:(float)$profile['target_weight_kg']],
        'weights'=>array_map(static fn($r)=>['date'=>$r['date'],'kg'=>(float)$r['kg']],$weightRows),
        'energy'=>$energy,'weight_days'=>$weightDays,'energy_days'=>$energyDays,
        'notes'=>['difference'=>'Ngân sách động − calo đã ăn. Không đồng nghĩa với thâm hụt năng lượng sinh lý.','missing'=>'Ngày chưa ghi bữa ăn không được xem là 0 kcal.']
    ];
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

function executeRoute(string $route,string $method): never {
    if ($route === '/ping' && $method === 'GET') response(['status'=>'ok','service'=>'FlexiDiet PHP API']);
    if ($route === '/health' && $method === 'GET') { db()->query('SELECT 1'); response(['status'=>'ok','database'=>'connected']); }
    if ($route === '/auth/csrf' && $method === 'GET') response(['csrf_token'=>sessionCsrf()]);
    if ($method !== 'GET') verifyCsrf();
    if ($route === '/auth/register' && $method === 'POST') AuthController::register(db(), body());
    if ($route === '/auth/login' && $method === 'POST') AuthController::login(db(), body());
    if ($route === '/auth/forgot-password' && $method === 'POST') AuthController::requestPasswordReset(db(), body());
    if ($route === '/auth/reset-password' && $method === 'POST') AuthController::resetPassword(db(), body());
    if ($route === '/auth/logout' && $method === 'POST') AuthController::logout();
    if ($route === '/auth/me' && $method === 'GET') AuthController::me(db(), currentUserId());
    $uid = currentUserId(); $pdo = db();
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
        $w=(int)num($_GET,'weight_days',30,1825,365);
        $e=(int)num($_GET,'energy_days',7,365,7);
        if (!in_array($w,[30,90,365,1825],true) || !in_array($e,[7,30,365],true)) fail(422,'INVALID_PERIOD','Khoảng thời gian không được hỗ trợ.');
        ProfileController::getInsights($pdo, $uid, $w, $e);
    }
    if ($route === '/dashboard' && $method === 'GET') {
        $day = dateValue($_GET['date'] ?? today());
        $pdo->beginTransaction();
        try { ensureBudget($pdo,$uid,$day); $pdo->commit(); } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        response(['budget'=>budgetView($pdo,$uid,$day),'meals'=>mealList($pdo,$uid,$day),'workouts'=>workoutList($pdo,$uid,$day),'profile'=>profileRow($pdo,$uid)]);
    }
    if (str_starts_with($route, '/enews/')) {
        if (ENewsController::handle($pdo, $uid, $route, $method)) exit;
    }
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
    if ($route === '/exercises' && $method === 'GET') WorkoutController::listExercises($pdo);
    if ($route === '/workouts' && $method === 'GET') WorkoutController::listWorkouts($pdo, $uid);
    if ($route === '/workouts/heatmap' && $method === 'GET') WorkoutController::getHeatmap($pdo, $uid);
    if ($route === '/workouts' && $method === 'POST') WorkoutController::createWorkout($pdo, $uid, body());
    if (preg_match('~^/workouts/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        WorkoutController::deleteWorkout($pdo, $uid, (int)$m[1]);
    }
    if ($route === '/water' && $method === 'POST') WorkoutController::recordWater($pdo, $uid, body());
    if ($route === '/ai/recognize' && $method === 'POST') AiController::recognize($pdo, $uid);
    if ($route === '/ai/feedback' && $method === 'POST') AiController::feedback($pdo, $uid, body());
    if (str_starts_with($route, '/admin/')) {
        requireAdmin($pdo, $uid);
        if (AdminController::handle($pdo, $uid, $route, $method)) exit;
    }
    fail(404,'ENDPOINT_NOT_FOUND','API không tồn tại hoặc phương thức không hỗ trợ.');
}

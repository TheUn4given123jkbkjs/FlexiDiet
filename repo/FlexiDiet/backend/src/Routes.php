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
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_posts_user_created (user_id, created_at DESC),
        KEY idx_posts_created (created_at DESC),
        CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_media (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        mime_type VARCHAR(32) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_post_media_post (post_id, sort_order),
        CONSTRAINT fk_post_media_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_post_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_post_likes (post_id, user_id),
        KEY idx_post_likes_post (post_id),
        CONSTRAINT fk_post_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_post_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function enewsUploadMedia(PDO $pdo, int $uid): array {
    ensureEnNewsTables($pdo);
    if (!isset($_FILES['media'])) fail(422, 'INVALID_UPLOAD', 'Không có tệp ảnh nào được gửi lên.');

    $fileGroups = $_FILES['media'];
    $items = [];
    if (isset($fileGroups['tmp_name']) && is_array($fileGroups['tmp_name'])) {
        foreach ($fileGroups['tmp_name'] as $index => $tmpName) {
            if (!is_string($tmpName) || $tmpName === '') continue;
            $items[] = [
                'tmp_name' => $tmpName,
                'name' => (string)($fileGroups['name'][$index] ?? 'upload.jpg'),
                'type' => (string)($fileGroups['type'][$index] ?? 'image/jpeg'),
                'size' => (int)($fileGroups['size'][$index] ?? 0),
            ];
        }
    } else {
        $items[] = [
            'tmp_name' => (string)($fileGroups['tmp_name'] ?? ''),
            'name' => (string)($fileGroups['name'] ?? 'upload.jpg'),
            'type' => (string)($fileGroups['type'] ?? 'image/jpeg'),
            'size' => (int)($fileGroups['size'] ?? 0),
        ];
    }

    if ($items === []) fail(422, 'INVALID_UPLOAD', 'Không có tệp ảnh nào được gửi lên.');
    if (count($items) > 6) fail(422, 'TOO_MANY_IMAGES', 'Bạn có thể đăng tối đa 6 ảnh trong một bài viết.');

    $directory = __DIR__ . '/../../public/uploads/enews';
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        fail(500, 'UPLOAD_DIR_FAILED', 'Không thể tạo thư mục lưu ảnh bài viết.');
    }

    $uploaded = [];
    foreach ($items as $item) {
        $tmpName = (string)($item['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) continue;
        $size = (int)($item['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) fail(422, 'INVALID_IMAGE_SIZE', 'Mỗi ảnh tối đa 5 MB.');

        $mime = mime_content_type($tmpName) ?: (string)($item['type'] ?? 'image/jpeg');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            fail(422, 'INVALID_FILE_TYPE', 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.');
        }

        $base = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', basename((string)($item['name'] ?? 'upload')));
        $base = trim($base, '.-');
        if ($base === '') $base = 'upload';
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'webp',
        };
        $targetName = sprintf('%s_%s_%s.%s', date('YmdHis'), $uid, bin2hex(random_bytes(4)), $ext);
        $targetPath = $directory . '/' . $targetName;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            fail(500, 'UPLOAD_FAILED', 'Không thể lưu ảnh lên máy chủ.');
        }

        $uploaded[] = [
            'path' => '/uploads/enews/' . $targetName,
            'name' => $base,
            'mime_type' => $mime,
            'size' => $size,
        ];
    }

    if ($uploaded === []) fail(422, 'INVALID_UPLOAD', 'Không có ảnh hợp lệ để tải lên.');
    return ['files' => $uploaded];
}

function enewsPostDetail(PDO $pdo, int $uid, int $postId): array {
    $post = one($pdo, 'SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at, u.display_name
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.id = ?', [$postId]);
    if (!$post) fail(404, 'POST_NOT_FOUND', 'Không tìm thấy bài viết.');

    $images = all($pdo, 'SELECT id, file_path, mime_type, file_name, sort_order FROM post_media WHERE post_id=? ORDER BY sort_order, id', [$postId]);
    $likesCount = (int)(one($pdo, 'SELECT COUNT(*) c FROM post_likes WHERE post_id=?', [$postId])['c'] ?? 0);
    $likedByMe = (bool)one($pdo, 'SELECT 1 FROM post_likes WHERE post_id=? AND user_id=? LIMIT 1', [$postId, $uid]);

    return [
        'id' => (int)$post['id'],
        'user_id' => (int)$post['user_id'],
        'content' => (string)$post['content'],
        'created_at' => $post['created_at'],
        'updated_at' => $post['updated_at'],
        'author' => [
            'id' => (int)$post['user_id'],
            'display_name' => $post['display_name'],
        ],
        'images' => array_map(static fn($image) => [
            'id' => (int)$image['id'],
            'path' => $image['file_path'],
            'mime_type' => $image['mime_type'],
            'file_name' => $image['file_name'],
            'sort_order' => (int)$image['sort_order'],
        ], $images),
        'likes_count' => $likesCount,
        'liked_by_me' => $likedByMe,
        'comments_count' => 0,
        'can_edit' => ((int)$post['user_id'] === $uid),
        'can_delete' => ((int)$post['user_id'] === $uid),
    ];
}

function enewsFeed(PDO $pdo, int $uid, int $limit = 12, int $offset = 0): array {
    $limit = max(1, min(25, $limit));
    $offset = max(0, $offset);
    $rows = all($pdo, 'SELECT p.id
        FROM posts p
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT ? OFFSET ?', [$limit, $offset]);

    $posts = [];
    foreach ($rows as $row) {
        $posts[] = enewsPostDetail($pdo, $uid, (int)$row['id']);
    }

    return ['posts' => $posts, 'count' => count($posts), 'limit' => $limit, 'offset' => $offset];
}

function enewsCreatePost(PDO $pdo, int $uid, array $p): array {
    ensureEnNewsTables($pdo);
    $content = trim((string)($p['content'] ?? ''));
    $images = $p['images'] ?? [];
    if (!is_array($images)) fail(422, 'INVALID_IMAGES', 'Ảnh bài viết không hợp lệ.');
    if ($content === '' && count($images) === 0) fail(422, 'EMPTY_POST', 'Bài viết phải có nội dung hoặc ít nhất một ảnh.');
    if (mb_strlen($content) > 2000) fail(422, 'CONTENT_TOO_LONG', 'Nội dung bài viết tối đa 2000 ký tự.');
    if (count($images) > 6) fail(422, 'TOO_MANY_IMAGES', 'Bạn có thể đăng tối đa 6 ảnh trong một bài viết.');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)');
        $stmt->execute([$uid, $content]);
        $postId = (int)$pdo->lastInsertId();

        foreach ($images as $index => $rawPath) {
            $path = is_string($rawPath) ? trim($rawPath) : '';
            if ($path === '') continue;
            if (!preg_match('#^/uploads/enews/[A-Za-z0-9._/-]+\.(jpg|jpeg|png|webp)$#i', $path)) {
                fail(422, 'INVALID_IMAGE_PATH', 'Đường dẫn ảnh không hợp lệ.');
            }
            $storagePath = __DIR__ . '/../../public' . $path;
            if (!is_file($storagePath)) fail(422, 'INVALID_IMAGE_PATH', 'File ảnh không tồn tại trên máy chủ.');
            $mime = mime_content_type($storagePath) ?: 'image/jpeg';
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                fail(422, 'INVALID_FILE_TYPE', 'File ảnh không hợp lệ.');
            }
            $fileName = basename($storagePath);
            execSql($pdo, 'INSERT INTO post_media (post_id, user_id, mime_type, file_name, file_path, file_size, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)', [$postId, $uid, $mime, $fileName, $path, (int)filesize($storagePath), $index]);
        }

        $pdo->commit();
        return enewsPostDetail($pdo, $uid, $postId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function enewsUpdatePost(PDO $pdo, int $uid, int $postId, array $p): array {
    $post = one($pdo, 'SELECT user_id, content FROM posts WHERE id=?', [$postId]);
    if (!$post) fail(404, 'POST_NOT_FOUND', 'Không tìm thấy bài viết.');
    if ((int)$post['user_id'] !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền sửa bài viết này.');

    $content = trim((string)($p['content'] ?? $post['content']));
    if ($content === '') fail(422, 'EMPTY_POST', 'Bài viết không được để trống.');
    if (mb_strlen($content) > 2000) fail(422, 'CONTENT_TOO_LONG', 'Nội dung bài viết tối đa 2000 ký tự.');

    execSql($pdo, 'UPDATE posts SET content=? WHERE id=? AND user_id=?', [$content, $postId, $uid]);
    return enewsPostDetail($pdo, $uid, $postId);
}

function enewsDeletePost(PDO $pdo, int $uid, int $postId): array {
    $post = one($pdo, 'SELECT user_id FROM posts WHERE id=?', [$postId]);
    if (!$post) fail(404, 'POST_NOT_FOUND', 'Không tìm thấy bài viết.');
    if ((int)$post['user_id'] !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền xóa bài viết này.');

    execSql($pdo, 'DELETE FROM posts WHERE id=? AND user_id=?', [$postId, $uid]);
    return ['deleted' => true, 'post_id' => $postId];
}

function enewsToggleLike(PDO $pdo, int $uid, int $postId, bool $liked): array {
    $post = one($pdo, 'SELECT id FROM posts WHERE id=?', [$postId]);
    if (!$post) fail(404, 'POST_NOT_FOUND', 'Không tìm thấy bài viết.');

    if ($liked) {
        $exists = one($pdo, 'SELECT 1 FROM post_likes WHERE post_id=? AND user_id=? LIMIT 1', [$postId, $uid]);
        if (!$exists) {
            execSql($pdo, 'INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)', [$postId, $uid]);
        }
        return ['liked' => true, 'likes_count' => (int)(one($pdo, 'SELECT COUNT(*) c FROM post_likes WHERE post_id=?', [$postId])['c'] ?? 0)];
    }

    execSql($pdo, 'DELETE FROM post_likes WHERE post_id=? AND user_id=?', [$postId, $uid]);
    return ['liked' => false, 'likes_count' => (int)(one($pdo, 'SELECT COUNT(*) c FROM post_likes WHERE post_id=?', [$postId])['c'] ?? 0)];
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
    if ($route === '/enews/feed' && $method === 'GET') {
        $limit = max(1, min(25, (int)num($_GET, 'limit', 1, 25, 12)));
        $offset = max(0, (int)num($_GET, 'offset', 0, 5000, 0));
        response(enewsInteractionFeed($pdo, $uid, $limit, $offset));
    }
    if ($route === '/enews/profile' && $method === 'GET') {
        $limit = (int)num($_GET, 'limit', 1, 25, 12);
        $offset = (int)num($_GET, 'offset', 0, 100000, 0);
        response(enewsInteractionProfile($pdo, $uid, $uid, $limit, $offset));
    }
    if (preg_match('~^/enews/profile/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
        $limit = (int)num($_GET, 'limit', 1, 25, 12);
        $offset = (int)num($_GET, 'offset', 0, 100000, 0);
        response(enewsInteractionProfile($pdo, $uid, (int)$m[1], $limit, $offset));
    }
    if ($route === '/enews/profile' && $method === 'PATCH') {
        response(enewsInteractionUpdatePublicProfile($pdo, $uid, body()));
    }
    if ($route === '/enews/friends/requests' && $method === 'GET') {
        response(enewsFriendshipRequests($pdo, $uid));
    }
    if ($route === '/enews/friends' && $method === 'GET') {
        response(enewsFriendshipList($pdo, $uid));
    }
    if (preg_match('~^/enews/friends/([1-9][0-9]*)/request$~D', $route, $m) && $method === 'POST') {
        response(enewsFriendshipRequest($pdo, $uid, (int)$m[1]), 201);
    }
    if (preg_match('~^/enews/friends/([1-9][0-9]*)/request$~D', $route, $m) && $method === 'DELETE') {
        response(enewsFriendshipCancel($pdo, $uid, (int)$m[1]));
    }
    if (preg_match('~^/enews/friends/requests/([1-9][0-9]*)/(accept|decline)$~D', $route, $m) && $method === 'POST') {
        response(enewsFriendshipRespond($pdo, $uid, (int)$m[1], $m[2] === 'accept'));
    }
    if (preg_match('~^/enews/friends/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        response(enewsFriendshipRemove($pdo, $uid, (int)$m[1]));
    }
    if (preg_match('~^/enews/media/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
        enewsInteractionServeMedia($pdo, $uid, (int)$m[1]);
    }
    if ($route === '/enews/media' && $method === 'POST') response(enewsInteractionUpload($pdo, $uid));
    if ($route === '/enews/posts' && $method === 'POST') response(enewsInteractionCreatePost($pdo, $uid, body()), 201);
    if (preg_match('~^/enews/posts/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
        response(enewsInteractionPostDetail($pdo, $uid, (int)$m[1]));
    }
    if ($route === '/enews/notifications' && $method === 'GET') {
        $limit = (int)num($_GET, 'limit', 1, 50, 20);
        $offset = (int)num($_GET, 'offset', 0, 5000, 0);
        response(enewsInteractionNotifications($pdo, $uid, $limit, $offset));
    }
    if (preg_match('~^/enews/notifications/([1-9][0-9]*)$~D', $route, $m) && $method === 'PATCH') {
        response(enewsInteractionReadNotification($pdo, $uid, (int)$m[1]));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)/comments$~D', $route, $m) && $method === 'GET') {
        $limit = (int)num($_GET, 'limit', 1, 50, 30);
        $offset = (int)num($_GET, 'offset', 0, 100000, 0);
        response(enewsInteractionCommentList($pdo, $uid, (int)$m[1], $limit, $offset));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)/comments$~D', $route, $m) && $method === 'POST') {
        response(enewsInteractionCreateComment($pdo, $uid, (int)$m[1], body()), 201);
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)/share$~D', $route, $m) && $method === 'POST') {
        response(enewsInteractionShare($pdo, $uid, (int)$m[1], body()), 201);
    }
    if (preg_match('~^/enews/comments/([1-9][0-9]*)$~D', $route, $m) && $method === 'PATCH') {
        response(enewsInteractionUpdateComment($pdo, $uid, (int)$m[1], body()));
    }
    if (preg_match('~^/enews/comments/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        response(enewsInteractionDeleteComment($pdo, $uid, (int)$m[1]));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)$~D', $route, $m) && $method === 'PATCH') {
        response(enewsInteractionUpdatePost($pdo, $uid, (int)$m[1], body()));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)$~D', $route, $m) && $method === 'DELETE') {
        response(enewsInteractionDeletePost($pdo, $uid, (int)$m[1]));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)/like$~D', $route, $m) && $method === 'POST') {
        response(enewsInteractionToggleLike($pdo, $uid, (int)$m[1], true));
    }
    if (preg_match('~^/enews/posts/([1-9][0-9]*)/like$~D', $route, $m) && $method === 'DELETE') {
        response(enewsInteractionToggleLike($pdo, $uid, (int)$m[1], false));
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
        if ($route === '/admin/ai/health' && $method === 'GET') {
            $h = proxyAiGet('/health') ?: [
                'status' => 'Offline', 'service' => 'FlexiDiet AI Microservice',
                'model' => 'YOLOv10m-VietFood67', 'uptime' => '0 phút', 'cpu' => 0, 'ram' => 0
            ];
            response($h);
        }
        if ($route === '/admin/ai/metrics' && $method === 'GET') {
            $m = proxyAiGet('/v1/metrics') ?: [
                'volume_today' => 0, 'volume_week' => 0, 'volume_month' => 0,
                'in_flight' => 0, 'error_rate' => 0, 'p50_ms' => 180, 'p95_ms' => 340, 'unknown_detections' => 0
            ];
            $dbVol = one($pdo, 'SELECT 
                COUNT(CASE WHEN hit_at >= CURDATE() THEN 1 END) AS vol_today,
                COUNT(CASE WHEN hit_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS vol_week,
                COUNT(CASE WHEN hit_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) AS vol_month
                FROM rate_limit_hits WHERE bucket = "analyze"');
            if ($dbVol) {
                $m['volume_today'] = max((int)$dbVol['vol_today'], (int)($m['volume_today'] ?? 0));
                $m['volume_week'] = max((int)$dbVol['vol_week'], (int)($m['volume_week'] ?? 0));
                $m['volume_month'] = max((int)$dbVol['vol_month'], (int)($m['volume_month'] ?? 0));
            }
            response($m);
        }
        if ($route === '/admin/ai/labels' && $method === 'GET') {
            $res = proxyAiGet('/v1/labels');
            $usedCodes = array_column(all($pdo, 'SELECT dish_code FROM dishes WHERE dish_code IS NOT NULL AND is_active=1'), 'dish_code');
            $usedMap = array_fill_keys($usedCodes, true);
            $items = [];
            if ($res && !empty($res['labels'])) {
                foreach ($res['labels'] as $l) {
                    $code = $l['dish_code'] ?? '';
                    $items[] = [
                        'dish_code' => $code,
                        'name_full' => $l['name_full'] ?? $code,
                        'name_vi' => $l['name_vi'] ?? $code,
                        'used' => isset($usedMap[$code]) ? 1 : 0
                    ];
                }
            }
            response($items);
        }
        if ($route === '/admin/ai/throughput' && $method === 'GET') {
            $period = (string)($_GET['period'] ?? 'hour');
            $bars = [];

            if ($period === 'month') {
                $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
                for ($i = 5; $i >= 0; $i--) {
                    $m = $now->sub(new DateInterval("P{$i}M"));
                    $ym = $m->format('Y-m');
                    $c = (int)(one($pdo, 'SELECT COUNT(*) c FROM rate_limit_hits WHERE bucket="analyze" AND DATE_FORMAT(hit_at, "%Y-%m")=?', [$ym])['c'] ?? 0);
                    $bars[] = ['label' => 'T' . $m->format('n'), 'count' => $c];
                }
            } elseif ($period === 'day') {
                $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
                $daysVi = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
                $rows = all($pdo, 'SELECT DATE(hit_at) dt, COUNT(*) c 
                    FROM rate_limit_hits 
                    WHERE bucket="analyze" AND hit_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                    GROUP BY DATE(hit_at)');
                $map = [];
                foreach ($rows as $r) $map[$r['dt']] = (int)$r['c'];

                for ($i = 6; $i >= 0; $i--) {
                    $d = $now->sub(new DateInterval("P{$i}D"));
                    $dt = $d->format('Y-m-d');
                    $idx = ((int)$d->format('N')) - 1;
                    $bars[] = ['label' => $daysVi[$idx] ?? $d->format('d/m'), 'count' => $map[$dt] ?? 0];
                }
            } else { // 'hour' (default): 00h to 23h today
                $rows = all($pdo, 'SELECT DATE_FORMAT(hit_at, "%H") hr, COUNT(*) c 
                    FROM rate_limit_hits 
                    WHERE bucket="analyze" AND DATE(hit_at) = CURDATE() 
                    GROUP BY DATE_FORMAT(hit_at, "%H")');
                $countsByHour = [];
                foreach ($rows as $r) {
                    $countsByHour[(int)$r['hr']] = (int)$r['c'];
                }
                for ($hr = 0; $hr < 24; $hr++) {
                    $bars[] = ['label' => sprintf('%02dh', $hr), 'count' => $countsByHour[$hr] ?? 0];
                }
            }
            response($bars);
        }
        if ($route === '/admin/ai/http-distribution' && $method === 'GET') {
            $period = (string)($_GET['period'] ?? 'hour');
            $timeSql = match($period) {
                'month' => 'hit_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)',
                'day' => 'hit_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)',
                default => 'DATE(hit_at) = CURDATE()'
            };

            $rows = all($pdo, "SELECT bucket, COUNT(*) c FROM rate_limit_hits WHERE bucket LIKE 'analyze:%' AND $timeSql GROUP BY bucket");
            $bMap = [];
            foreach ($rows as $r) {
                $code = substr($r['bucket'], strlen('analyze:'));
                $bMap[$code] = (int)$r['c'];
            }

            // Also check runtime metrics if database has no detailed hits yet
            $m = proxyAiGet('/v1/metrics') ?: [];
            $pyTotal = (int)($m['volume_today'] ?? 0);
            $pyUnknown = (int)($m['unknown_detections'] ?? 0);
            $pyErr = (int)round(($pyTotal * (float)($m['error_rate'] ?? 0)) / 100);
            $pySuccess = max(0, $pyTotal - $pyUnknown - $pyErr);

            $c200 = max($bMap['200'] ?? 0, $pySuccess);
            $c422 = max($bMap['422'] ?? 0, $pyUnknown);
            $c500 = max($bMap['500'] ?? ($bMap['503'] ?? 0), $pyErr);
            $c429 = (int)($bMap['429'] ?? 0);
            $total = $c200 + $c422 + $c500 + $c429;

            $items = [
                [
                    'code' => '200',
                    'label' => '200 OK',
                    'badge' => 'Thành công',
                    'desc' => 'Ảnh phân tích & nhận diện hợp lệ',
                    'count' => $c200,
                    'pct' => $total > 0 ? round(($c200 / $total) * 100, 1) : 100.0,
                    'color' => 'var(--fd-green)'
                ],
                [
                    'code' => '422',
                    'label' => '422 Unprocessable',
                    'badge' => 'Không rõ món',
                    'desc' => 'Chưa có trong 67 nhãn hoặc ảnh mờ',
                    'count' => $c422,
                    'pct' => $total > 0 ? round(($c422 / $total) * 100, 1) : 0.0,
                    'color' => '#f59e0b'
                ],
                [
                    'code' => '500',
                    'label' => '500 / 503 Server Error',
                    'badge' => 'Lỗi máy chủ',
                    'desc' => 'Lỗi kết nối mô hình hoặc timeout',
                    'count' => $c500,
                    'pct' => $total > 0 ? round(($c500 / $total) * 100, 1) : 0.0,
                    'color' => '#ef4444'
                ],
                [
                    'code' => '429',
                    'label' => '429 Too Many Requests',
                    'badge' => 'Vượt giới hạn',
                    'desc' => 'Yêu cầu vượt ngưỡng tần suất',
                    'count' => $c429,
                    'pct' => $total > 0 ? round(($c429 / $total) * 100, 1) : 0.0,
                    'color' => '#3b82f6'
                ]
            ];

            response([
                'period' => $period,
                'total' => $total,
                'items' => $items
            ]);
        }
        if (handleAdminRoute($pdo, $uid, $route, $method)) exit;
    }
    fail(404,'ENDPOINT_NOT_FOUND','API không tồn tại hoặc phương thức không hỗ trợ.');
}

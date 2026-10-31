<?php
declare(strict_types=1);

/**
 * AuthService - Nghiệp vụ xác thực người dùng, phiên đăng nhập và khôi phục mật khẩu OTP
 */
class AuthService {
    public static function authData(PDO $pdo, int $uid): array {
        $user = UserModel::findById($pdo, $uid);
        if (!$user) {
            unset($_SESSION['uid']);
            fail(401, 'UNAUTHENTICATED', 'Phiên đăng nhập không tồn tại.');
        }
        return ['user' => $user, 'csrf_token' => sessionCsrf()];
    }

    public static function register(PDO $pdo, array $params): array {
        $name = requiredString($params, 'display_name', 100);
        $email = mb_strtolower(requiredString($params, 'email', 255));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Địa chỉ email không hợp lệ.');
        $password = requiredString($params, 'password', 200);
        if (strlen($password) < 8 || strlen($password) > 72) fail(422, 'INVALID_PASSWORD', 'Mật khẩu phải từ 8–72 byte.');
        $sex = choice($params, 'sex', ['male', 'female']);
        $birth = dateValue($params['birth_date'] ?? null);
        $age = (new DateTimeImmutable($birth))->diff(new DateTimeImmutable(today()))->y;
        if ($age < 13 || $age > 110) fail(422, 'INVALID_AGE', 'Tuổi phải từ 13 đến 110.');
        $height = num($params, 'height_cm', 90, 250);
        $weight = num($params, 'weight_kg', 20, 400);
        $goal = choice($params, 'goal', ['lose', 'maintain', 'gain', 'build_muscle']);
        $target = isset($params['target_weight_kg']) && $params['target_weight_kg'] !== '' ? num($params, 'target_weight_kg', 20, 400) : null;
        $weekly = (int)num($params, 'weekly_workout_goal', 0, 7, 3);

        $pdo->beginTransaction();
        try {
            if (UserModel::findByEmail($pdo, $email)) {
                fail(409, 'EMAIL_EXISTS', 'Email này đã được đăng ký.');
            }
            $uid = UserModel::create($pdo, $email, password_hash($password, PASSWORD_DEFAULT), $name);
            execSql($pdo, 'INSERT INTO user_profiles (user_id, sex, birth_date, height_cm, goal, target_weight_kg, weekly_workout_goal) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$uid, $sex, $birth, $height, $goal, $target, $weekly]);
            UserModel::recordWeight($pdo, $uid, $weight, today());
            ensureBudget($pdo, $uid, today());
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return self::authData($pdo, $uid);
    }

    public static function login(PDO $pdo, array $params): array {
        $email = mb_strtolower(requiredString($params, 'email', 255));
        $password = requiredString($params, 'password', 200);
        $user = UserModel::findByEmail($pdo, $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            fail(401, 'INVALID_CREDENTIALS', 'Email hoặc mật khẩu không đúng.');
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return self::authData($pdo, (int)$user['id']);
    }

    public static function logout(): array {
        currentUserId();
        unset($_SESSION['uid']);
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return ['logged_out' => true];
    }

    public static function requestPasswordReset(PDO $pdo, array $params): array {
        $email = mb_strtolower(requiredString($params, 'email', 255));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Địa chỉ email không hợp lệ.');
        $genericResponse = ['message' => 'Nếu email đã đăng ký, mã OTP sẽ được gửi trong ít phút.'];
        $user = UserModel::findByEmail($pdo, $email);
        if (!$user) return $genericResponse;

        $existing = UserModel::getPasswordResetRecord($pdo, $email);
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

        UserModel::savePasswordResetOtp($pdo, $email, (int)$user['id'], password_hash($otp, PASSWORD_DEFAULT), $expiresAt);
        return $genericResponse;
    }

    public static function resetPassword(PDO $pdo, array $params): array {
        $email = mb_strtolower(requiredString($params, 'email', 255));
        $otp = requiredString($params, 'otp', 6);
        $password = requiredString($params, 'password', 200);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
        if (!preg_match('/^\d{6}$/D', $otp)) fail(422, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
        if (strlen($password) < 8 || strlen($password) > 72) fail(422, 'INVALID_PASSWORD', 'Mật khẩu phải từ 8–72 byte.');

        UserModel::ensurePasswordResetTable($pdo);
        $pdo->beginTransaction();
        try {
            $reset = UserModel::getPasswordResetRecord($pdo, $email, true);
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
            if (!$reset || (int)$reset['attempts'] >= 5 || new DateTimeImmutable($reset['expires_at']) <= $now) {
                if ($reset) UserModel::deletePasswordReset($pdo, $email);
                $pdo->commit();
                fail(400, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
            }
            if (!password_verify($otp, $reset['otp_hash'])) {
                UserModel::incrementResetAttempts($pdo, $email);
                $pdo->commit();
                fail(400, 'INVALID_OTP', 'Email hoặc mã OTP không hợp lệ hoặc đã hết hạn.');
            }
            UserModel::updatePassword($pdo, (int)$reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
            UserModel::deletePasswordReset($pdo, $email);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
        return ['message' => 'Mật khẩu đã được đặt lại. Bạn có thể đăng nhập bằng mật khẩu mới.'];
    }
}

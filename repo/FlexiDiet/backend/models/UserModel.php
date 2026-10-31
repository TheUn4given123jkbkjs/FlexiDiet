<?php
declare(strict_types=1);

/**
 * UserModel - Quản lý truy xuất dữ liệu liên quan đến Users, Profiles, Avatars, OTP
 */
class UserModel {
    public static function findByEmail(PDO $pdo, string $email): ?array {
        return one($pdo, 'SELECT id, email, password_hash, display_name, role FROM users WHERE email = ?', [$email]);
    }

    public static function findById(PDO $pdo, int $id): ?array {
        return one($pdo, 'SELECT id, display_name, email, role FROM users WHERE id = ?', [$id]);
    }

    public static function create(PDO $pdo, string $email, string $passwordHash, string $displayName): int {
        execSql($pdo, 'INSERT INTO users (email, password_hash, display_name, role) VALUES (?, ?, ?, "member")', [
            $email,
            $passwordHash,
            $displayName
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updatePassword(PDO $pdo, int $userId, string $passwordHash): void {
        execSql($pdo, 'UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $userId]);
    }

    public static function updateDisplayName(PDO $pdo, int $userId, string $displayName): void {
        execSql($pdo, 'UPDATE users SET display_name = ? WHERE id = ?', [$displayName, $userId]);
    }

    public static function getProfile(PDO $pdo, int $userId): ?array {
        return profileRow($pdo, $userId);
    }

    public static function updateProfile(PDO $pdo, int $userId, float $height, string $goal, ?float $targetWeight, string $policy): void {
        execSql($pdo, 'UPDATE user_profiles SET height_cm = ?, goal = ?, target_weight_kg = ?, credit_policy = ? WHERE user_id = ?', [
            $height,
            $goal,
            $targetWeight,
            $policy,
            $userId
        ]);
    }

    public static function recordWeight(PDO $pdo, int $userId, float $weightKg, string $date): void {
        execSql($pdo, 'INSERT INTO weight_logs (user_id, log_date, weight_kg) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE weight_kg = VALUES(weight_kg)', [$userId, $date, $weightKg]);
    }

    public static function ensureAvatarTable(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS profile_avatars (
            user_id BIGINT UNSIGNED NOT NULL,
            mime_type VARCHAR(32) NOT NULL,
            image_data MEDIUMBLOB NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            CONSTRAINT fk_profile_avatars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function getAvatar(PDO $pdo, int $userId): ?array {
        self::ensureAvatarTable($pdo);
        return one($pdo, 'SELECT mime_type, image_data FROM profile_avatars WHERE user_id = ?', [$userId]);
    }

    public static function saveAvatar(PDO $pdo, int $userId, string $mimeType, string $imageData): void {
        self::ensureAvatarTable($pdo);
        $stmt = $pdo->prepare('INSERT INTO profile_avatars (user_id, mime_type, image_data) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE mime_type = VALUES(mime_type), image_data = VALUES(image_data), updated_at = CURRENT_TIMESTAMP');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $mimeType, PDO::PARAM_STR);
        $stmt->bindValue(3, $imageData, PDO::PARAM_LOB);
        $stmt->execute();
    }

    public static function deleteAvatar(PDO $pdo, int $userId): void {
        self::ensureAvatarTable($pdo);
        execSql($pdo, 'DELETE FROM profile_avatars WHERE user_id = ?', [$userId]);
    }

    public static function ensurePasswordResetTable(PDO $pdo): void {
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

    public static function getPasswordResetRecord(PDO $pdo, string $email, bool $forUpdate = false): ?array {
        self::ensurePasswordResetTable($pdo);
        $sql = 'SELECT user_id, otp_hash, expires_at, attempts, sent_at FROM password_reset_otps WHERE email = ?';
        if ($forUpdate) $sql .= ' FOR UPDATE';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function savePasswordResetOtp(PDO $pdo, string $email, int $userId, string $otpHash, string $expiresAt): void {
        self::ensurePasswordResetTable($pdo);
        execSql($pdo, 'INSERT INTO password_reset_otps (email, user_id, otp_hash, expires_at, attempts, sent_at)
            VALUES (?, ?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), otp_hash = VALUES(otp_hash),
            expires_at = VALUES(expires_at), attempts = 0, sent_at = NOW()',
            [$email, $userId, $otpHash, $expiresAt]);
    }

    public static function incrementResetAttempts(PDO $pdo, string $email): void {
        execSql($pdo, 'UPDATE password_reset_otps SET attempts = attempts + 1 WHERE email = ?', [$email]);
    }

    public static function deletePasswordReset(PDO $pdo, string $email): void {
        execSql($pdo, 'DELETE FROM password_reset_otps WHERE email = ?', [$email]);
    }
}

<?php
declare(strict_types=1);

/**
 * ProfileController - Bộ điều khiển tiếp nhận các yêu cầu HTTP hồ sơ, avatar, cân nặng, dashboard
 */
class ProfileController {
    public static function getProfile(PDO $pdo, int $uid): void {
        response(profileRow($pdo, $uid));
    }

    public static function updateProfile(PDO $pdo, int $uid, array $params): void {
        response(ProfileService::updateProfile($pdo, $uid, $params));
    }

    public static function recordWeight(PDO $pdo, int $uid, array $params): void {
        response(ProfileService::recordWeight($pdo, $uid, $params), 201);
    }

    public static function getAvatar(PDO $pdo, int $uid): never {
        $avatar = UserModel::getAvatar($pdo, $uid);
        if (!$avatar) fail(404, 'AVATAR_NOT_FOUND', 'Bạn chưa cài ảnh đại diện.');
        header('Content-Type: ' . $avatar['mime_type']);
        header('Content-Length: ' . strlen($avatar['image_data']));
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        echo $avatar['image_data'];
        exit;
    }

    public static function uploadAvatar(PDO $pdo, int $uid): void {
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

        UserModel::saveAvatar($pdo, $uid, $mime, $imageData);
        response(['updated' => true]);
    }

    public static function deleteAvatar(PDO $pdo, int $uid): void {
        UserModel::deleteAvatar($pdo, $uid);
        response(['deleted' => true]);
    }

    public static function getInsights(PDO $pdo, int $uid, int $weightDays, int $energyDays): void {
        response(ProfileService::getInsights($pdo, $uid, $weightDays, $energyDays));
    }

    public static function getDashboard(PDO $pdo, int $uid, ?string $rawDate = null): void {
        response(ProfileService::getDashboard($pdo, $uid, $rawDate));
    }
}

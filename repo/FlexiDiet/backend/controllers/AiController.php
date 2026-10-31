<?php
declare(strict_types=1);

/**
 * AiController - Tiếp nhận và xử lý HTTP requests nhận diện ảnh món ăn và lưu feedback
 */
class AiController {
    public static function recognize(PDO $pdo, int $uid): void {
        response(AiService::recognize($pdo, $uid));
    }

    public static function feedback(PDO $pdo, int $uid, array $params): void {
        response(AiService::saveFeedback($pdo, $uid, $params), 201);
    }
}

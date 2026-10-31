<?php
declare(strict_types=1);

/**
 * AiModel - Quản lý truy xuất dữ liệu phản hồi AI, rate-limit hits và ánh xạ món nhận diện
 */
class AiModel {
    public static function recordRateLimitHit(PDO $pdo, int $uid, string $bucket = 'analyze'): void {
        try {
            execSql($pdo, 'INSERT INTO rate_limit_hits (user_id, bucket, hit_at) VALUES (?, ?, NOW(3))', [$uid, $bucket]);
        } catch (Throwable) {
            // Ignore optional rate limit hit insertion failures
        }
    }

    public static function recordFeedback(
        PDO $pdo,
        int $uid,
        ?string $predCode,
        ?float $predConf,
        ?string $correctedCode,
        ?string $itemsJson,
        ?string $imagePath
    ): int {
        execSql($pdo, '
            INSERT INTO ai_feedback (
                user_id, model_version, predicted_dish_code, 
                predicted_confidence, corrected_dish_code, 
                corrected_items, image_path, created_at
            ) VALUES (?, "YOLOv10m-VietFood67", ?, ?, ?, ?, ?, NOW())',
            [$uid, $predCode, $predConf, $correctedCode, $itemsJson, $imagePath]
        );
        return (int)$pdo->lastInsertId();
    }

    public static function findDishByCodeOrName(PDO $pdo, string $dishCode, string $nameVi): ?array {
        $dish = one($pdo, '
            SELECT id, name, name_norm, serving_label, dish_code, origin 
            FROM dishes 
            WHERE dish_code = ? AND is_active = 1 
            LIMIT 1',
            [$dishCode]
        );
        if (!$dish) {
            $norm = FoodService::normalizeVietnamese($nameVi);
            $dish = one($pdo, '
                SELECT id, name, name_norm, serving_label, dish_code, origin 
                FROM dishes 
                WHERE (name LIKE ? OR name_norm LIKE ?) AND is_active = 1 
                LIMIT 1',
                ['%' . $nameVi . '%', '%' . $norm . '%']
            );
        }
        return $dish;
    }
}


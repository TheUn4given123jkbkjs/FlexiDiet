<?php
declare(strict_types=1);

/**
 * ENewsModel - Quản lý cơ sở dữ liệu và truy vấn dữ liệu cho FlexiDiet eNews
 */
class ENewsModel {
    /**
     * Khởi tạo và bảo đảm cấu trúc bảng cho bài viết, tệp đính kèm và lượt thích
     */
    public static function ensureTables(PDO $pdo): void {
        static $ready = false;
        if ($ready) return;

        $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            privacy ENUM('public','private') NOT NULL DEFAULT 'public',
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

        $ready = true;
    }

    public static function findPostById(PDO $pdo, int $postId): ?array {
        return one($pdo, 'SELECT p.*, u.display_name 
            FROM posts p 
            JOIN users u ON u.id = p.user_id 
            WHERE p.id = ?', [$postId]);
    }

    public static function getPostMedia(PDO $pdo, int $postId): array {
        return all($pdo, 'SELECT id, file_path, mime_type, file_name, sort_order 
            FROM post_media 
            WHERE post_id = ? 
            ORDER BY sort_order, id', [$postId]);
    }

    public static function countLikes(PDO $pdo, int $postId): int {
        return (int)(one($pdo, 'SELECT COUNT(*) c FROM post_likes WHERE post_id = ?', [$postId])['c'] ?? 0);
    }

    public static function isLikedBy(PDO $pdo, int $postId, int $userId): bool {
        return (bool)one($pdo, 'SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ? LIMIT 1', [$postId, $userId]);
    }
}

<?php
declare(strict_types=1);

function enewsEnsureInteractionSchema(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;

    ensureEnNewsTables($pdo);
    if (!one($pdo, "SHOW COLUMNS FROM posts LIKE 'privacy'")) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN privacy ENUM('public','private') NOT NULL DEFAULT 'public' AFTER content");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        parent_id BIGINT UNSIGNED NULL,
        content VARCHAR(2000) NOT NULL,
        deleted_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_comments_post_created (post_id, created_at, id),
        KEY idx_comments_parent (parent_id, created_at, id),
        KEY idx_comments_user_created (user_id, created_at),
        CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_shares (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        source_post_id BIGINT UNSIGNED NOT NULL,
        shared_by_user_id BIGINT UNSIGNED NOT NULL,
        caption VARCHAR(1000) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_post_shares_source (source_post_id, created_at),
        KEY idx_post_shares_user_created (shared_by_user_id, created_at),
        CONSTRAINT fk_post_shares_source FOREIGN KEY (source_post_id) REFERENCES posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_post_shares_user FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_id BIGINT UNSIGNED NOT NULL,
        actor_id BIGINT UNSIGNED NOT NULL,
        type ENUM('post_like','post_comment','comment_reply') NOT NULL,
        post_id BIGINT UNSIGNED NOT NULL,
        comment_id BIGINT UNSIGNED NULL,
        dedupe_key CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_notifications_dedupe (dedupe_key),
        KEY idx_notifications_recipient_created (recipient_id, created_at, id),
        KEY idx_notifications_recipient_unread (recipient_id, read_at, created_at),
        CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_notifications_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_notifications_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS enews_public_profiles (
        user_id BIGINT UNSIGNED NOT NULL,
        bio VARCHAR(500) NOT NULL DEFAULT '',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_enews_public_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_friendships (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_low_id BIGINT UNSIGNED NOT NULL,
        user_high_id BIGINT UNSIGNED NOT NULL,
        requester_id BIGINT UNSIGNED NOT NULL,
        status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_user_friendships_pair (user_low_id,user_high_id),
        KEY idx_user_friendships_low_status (user_low_id,status,updated_at),
        KEY idx_user_friendships_high_status (user_high_id,status,updated_at),
        CONSTRAINT fk_user_friendships_low FOREIGN KEY (user_low_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_user_friendships_high FOREIGN KEY (user_high_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_user_friendships_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT chk_user_friendships_pair CHECK (user_low_id < user_high_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $friendNoticeColumn = one($pdo, "SHOW COLUMNS FROM notifications LIKE 'target_user_id'");
    if (!$friendNoticeColumn) {
        $pdo->exec('ALTER TABLE notifications ADD COLUMN target_user_id BIGINT UNSIGNED NULL AFTER comment_id,
            ADD CONSTRAINT fk_notifications_target_user FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
    $postColumn = one($pdo, "SHOW COLUMNS FROM notifications LIKE 'post_id'");
    if ($postColumn && $postColumn['Null'] === 'NO') {
        $pdo->exec('ALTER TABLE notifications MODIFY COLUMN post_id BIGINT UNSIGNED NULL');
    }
    $typeColumn = one($pdo, "SHOW COLUMNS FROM notifications LIKE 'type'");
    if ($typeColumn && !str_contains((string)$typeColumn['Type'], 'friend_request')) {
        $pdo->exec("ALTER TABLE notifications MODIFY COLUMN type ENUM('post_like','post_comment','comment_reply','friend_request','friend_accepted') NOT NULL");
    }

    $legacy = all($pdo, "SELECT id, user_id, file_path FROM post_media WHERE file_path LIKE '/uploads/enews/%'");
    $privateDirectory = __DIR__ . '/../var/enews';
    if ($legacy && !is_dir($privateDirectory) && !mkdir($privateDirectory, 0750, true) && !is_dir($privateDirectory)) {
        fail(500, 'ENews_STORAGE_FAILED', 'Không thể khởi tạo vùng lưu ảnh riêng tư.');
    }
    foreach ($legacy as $media) {
        $oldName = basename((string)$media['file_path']);
        if (!preg_match('/^[A-Za-z0-9_.-]+\\.(?:jpg|jpeg|png|webp)$/iD', $oldName)) {
            fail(500, 'ENews_STORAGE_INVALID', 'Đã gặp đường dẫn ảnh eNews không hợp lệ.');
        }
        $oldPath = __DIR__ . '/../../public/uploads/enews/' . $oldName;
        if (!is_file($oldPath)) continue;
        $extension = strtolower(pathinfo($oldName, PATHINFO_EXTENSION));
        $newName = (int)$media['user_id'] . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $newPath = $privateDirectory . '/' . $newName;
        if (!rename($oldPath, $newPath)) {
            error_log('FlexiDiet eNews failed to move legacy media id ' . (int)$media['id']);
            fail(500, 'ENews_STORAGE_MIGRATION_FAILED', 'Không thể chuyển ảnh cũ vào vùng lưu trữ riêng tư.');
        }
        execSql($pdo, 'UPDATE post_media SET file_path=? WHERE file_path=?', ['private/enews/' . $newName, (string)$media['file_path']]);
    }
    $legacyDirectory = __DIR__ . '/../../public/uploads/enews';
    if (is_dir($legacyDirectory)) {
        if (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0750, true) && !is_dir($privateDirectory)) {
            fail(500, 'ENews_STORAGE_FAILED', 'Không thể khởi tạo vùng lưu ảnh riêng tư.');
        }
        foreach (scandir($legacyDirectory) ?: [] as $oldName) {
            if (!preg_match('/^[A-Za-z0-9_.-]+\\.(?:jpg|jpeg|png|webp)$/iD', $oldName)) continue;
            $oldPath = $legacyDirectory . '/' . $oldName;
            if (!is_file($oldPath)) continue;
            $extension = strtolower(pathinfo($oldName, PATHINFO_EXTENSION));
            $orphanName = 'orphan_' . bin2hex(random_bytes(16)) . '.' . $extension;
            if (!rename($oldPath, $privateDirectory . '/' . $orphanName)) {
                error_log('FlexiDiet eNews failed to secure an orphaned legacy image');
                fail(500, 'ENews_STORAGE_MIGRATION_FAILED', 'Không thể bảo vệ ảnh eNews cũ.');
            }
        }
    }

    $ready = true;
}

function enewsInteractionPost(PDO $pdo, int $viewerId, int $postId): array {
    enewsEnsureInteractionSchema($pdo);
    $post = one($pdo, 'SELECT p.id, p.user_id, p.content, p.privacy, p.created_at, p.updated_at, u.display_name
        FROM posts p JOIN users u ON u.id=p.user_id WHERE p.id=?', [$postId]);
    if (!$post || ($post['privacy'] === 'private' && (int)$post['user_id'] !== $viewerId)) {
        fail(404, 'POST_NOT_FOUND', 'Không tìm thấy bài viết.');
    }
    return $post;
}

function enewsInteractionImagePath(string $relativePath): string {
    if (str_starts_with($relativePath, 'private/enews/')) {
        $name = substr($relativePath, strlen('private/enews/'));
        if (!preg_match('/^[1-9][0-9]*_[a-f0-9]{32}\\.(?:jpg|png|webp)$/D', $name)) {
            fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh.');
        }
        return __DIR__ . '/../var/enews/' . $name;
    }
    if (str_starts_with($relativePath, '/uploads/enews/')) {
        $name = basename($relativePath);
        if (!preg_match('/^[A-Za-z0-9_.-]+\\.(?:jpg|jpeg|png|webp)$/iD', $name)) {
            fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh.');
        }
        return __DIR__ . '/../../public/uploads/enews/' . $name;
    }
    fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh.');
}

function enewsInteractionUpload(PDO $pdo, int $uid): array {
    enewsEnsureInteractionSchema($pdo);
    $files = $_FILES['media'] ?? null;
    if (!is_array($files) || !isset($files['tmp_name'])) {
        fail(422, 'INVALID_UPLOAD', 'Không có tệp ảnh nào được gửi lên.');
    }
    $names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    if (count($names) < 1 || count($names) > 6) {
        fail(422, 'TOO_MANY_IMAGES', 'Bạn có thể tải lên từ 1 đến 6 ảnh.');
    }

    $directory = __DIR__ . '/../var/enews';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        fail(500, 'ENews_STORAGE_FAILED', 'Không thể khởi tạo vùng lưu ảnh riêng tư.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $uploaded = [];
    foreach ($names as $index => $tmpPath) {
        $error = is_array($files['error'] ?? null) ? (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) : (int)($files['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK || !is_string($tmpPath) || !is_uploaded_file($tmpPath)) {
            fail(422, 'UPLOAD_FAILED', 'Một hoặc nhiều ảnh không tải lên được.');
        }
        $size = is_array($files['size'] ?? null) ? (int)($files['size'][$index] ?? 0) : (int)($files['size'] ?? 0);
        if ($size < 1 || $size > 5 * 1024 * 1024) {
            fail(422, 'INVALID_IMAGE_SIZE', 'Mỗi ảnh phải có dung lượng tối đa 5 MB.');
        }
        $mime = $finfo->file($tmpPath);
        $image = @getimagesize($tmpPath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !$image || ($image['mime'] ?? '') !== $mime) {
            fail(422, 'INVALID_FILE_TYPE', 'Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP hợp lệ.');
        }
        if ($image[0] > 6000 || $image[1] > 6000 || $image[0] * $image[1] > 20000000) {
            fail(422, 'INVALID_IMAGE_DIMENSIONS', 'Kích thước ảnh vượt quá giới hạn cho phép.');
        }
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'webp',
        };
        $name = $uid . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($tmpPath, $directory . '/' . $name)) {
            fail(500, 'UPLOAD_FAILED', 'Không thể lưu ảnh lên máy chủ.');
        }
        $uploaded[] = ['path' => 'private/enews/' . $name, 'mime_type' => $mime, 'size' => $size];
    }
    return ['files' => $uploaded];
}

function enewsInteractionAuthorizedMedia(PDO $pdo, int $viewerId, int $mediaId): array {
    enewsEnsureInteractionSchema($pdo);
    $media = one($pdo, 'SELECT m.file_path, m.mime_type, m.post_id FROM post_media m WHERE m.id=?', [$mediaId]);
    if (!$media) fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh.');
    enewsInteractionPost($pdo, $viewerId, (int)$media['post_id']);
    $path = enewsInteractionImagePath((string)$media['file_path']);
    if (!is_file($path)) fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        fail(404, 'MEDIA_NOT_FOUND', 'Không tìm thấy ảnh hợp lệ.');
    }
    return ['path' => $path, 'mime_type' => $mime];
}

function enewsInteractionServeMedia(PDO $pdo, int $viewerId, int $mediaId): never {
    $media = enewsInteractionAuthorizedMedia($pdo, $viewerId, $mediaId);
    $path = $media['path'];
    $mime = $media['mime_type'];
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function enewsInteractionPostDetail(PDO $pdo, int $viewerId, int $postId): array {
    $post = enewsInteractionPost($pdo, $viewerId, $postId);
    $media = all($pdo, 'SELECT id, mime_type, sort_order FROM post_media WHERE post_id=? ORDER BY sort_order, id', [$postId]);
    $likes = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM post_likes WHERE post_id=?', [$postId])['c'] ?? 0);
    $comments = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM comments WHERE post_id=? AND deleted_at IS NULL', [$postId])['c'] ?? 0);
    $shares = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM post_shares WHERE source_post_id=?', [$postId])['c'] ?? 0);
    $liked = (bool)one($pdo, 'SELECT 1 FROM post_likes WHERE post_id=? AND user_id=? LIMIT 1', [$postId, $viewerId]);

    return [
        'id' => (int)$post['id'],
        'user_id' => (int)$post['user_id'],
        'content' => (string)$post['content'],
        'privacy' => $post['privacy'],
        'created_at' => $post['created_at'],
        'updated_at' => $post['updated_at'],
        'author' => ['id' => (int)$post['user_id'], 'display_name' => $post['display_name'], 'avatar_url' => '/profile/avatar/' . (int)$post['user_id']],
        'images' => array_map(static fn(array $image): array => [
            'id' => (int)$image['id'],
            'path' => '/enews/media/' . (int)$image['id'],
            'mime_type' => $image['mime_type'],
            'sort_order' => (int)$image['sort_order'],
        ], $media),
        'likes_count' => $likes,
        'liked_by_me' => $liked,
        'comments_count' => $comments,
        'shares_count' => $shares,
        'can_edit' => ((int)$post['user_id'] === $viewerId),
        'can_delete' => ((int)$post['user_id'] === $viewerId),
    ];
}

function enewsInteractionProfile(PDO $pdo, int $viewerId, int $profileId, int $limit = 12, int $offset = 0): array {
    enewsEnsureInteractionSchema($pdo);
    $user = one($pdo, 'SELECT id,display_name,created_at FROM users WHERE id=?', [$profileId]);
    if (!$user) fail(404, 'PROFILE_NOT_FOUND', 'Không tìm thấy hồ sơ.');
    $profile = one($pdo, 'SELECT bio FROM enews_public_profiles WHERE user_id=?', [$profileId]);
    $relationship = ['status' => 'self', 'request_id' => null, 'initiated_by_me' => false];
    if ($viewerId !== $profileId) {
        [$lowId, $highId] = [min($viewerId, $profileId), max($viewerId, $profileId)];
        $friendship = one($pdo, 'SELECT id,requester_id,status FROM user_friendships WHERE user_low_id=? AND user_high_id=?', [$lowId, $highId]);
        if ($friendship) {
            $relationship = [
                'status' => (string)$friendship['status'],
                'request_id' => (int)$friendship['id'],
                'initiated_by_me' => (int)$friendship['requester_id'] === $viewerId,
            ];
        } else {
            $relationship['status'] = 'none';
        }
    }
    $limit = max(1, min(25, $limit));
    $offset = max(0, $offset);
    $isOwner = $viewerId === $profileId;
    $rows = all($pdo, 'SELECT id FROM posts WHERE user_id=? AND (?=1 OR privacy="public")
        ORDER BY created_at DESC,id DESC LIMIT ? OFFSET ?', [$profileId, $isOwner ? 1 : 0, $limit + 1, $offset]);
    $hasMore = count($rows) > $limit;
    if ($hasMore) array_pop($rows);
    $posts = [];
    foreach ($rows as $row) {
        $posts[] = enewsInteractionPostDetail($pdo, $viewerId, (int)$row['id']);
    }
    return [
        'profile' => [
            'id' => (int)$user['id'],
            'display_name' => (string)$user['display_name'],
            'avatar_url' => '/profile/avatar/' . (int)$user['id'],
            'bio' => (string)($profile['bio'] ?? ''),
            'joined_at' => $user['created_at'],
            'is_me' => $isOwner,
            'relationship' => $relationship,
        ],
        'posts' => $posts,
        'count' => count($posts),
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => $hasMore,
        'next_offset' => $offset + count($posts),
    ];
}

function enewsFriendNotify(PDO $pdo, int $recipientId, int $actorId, string $type, int $friendshipId): void {
    if ($recipientId === $actorId) return;
    $dedupeKey = hash('sha256', 'friend:' . $type . ':' . $friendshipId);
    execSql($pdo, 'INSERT IGNORE INTO notifications (recipient_id,actor_id,type,post_id,comment_id,target_user_id,dedupe_key)
        VALUES (?,?,?,NULL,NULL,?,?)', [$recipientId, $actorId, $type, $actorId, $dedupeKey]);
}

function enewsFriendshipPair(PDO $pdo, int $userId, int $otherUserId): ?array {
    if ($otherUserId < 1 || $otherUserId === $userId) fail(422, 'INVALID_FRIEND', 'Không thể gửi lời mời kết bạn cho tài khoản này.');
    [$lowId, $highId] = [min($userId, $otherUserId), max($userId, $otherUserId)];
    return one($pdo, 'SELECT id,user_low_id,user_high_id,requester_id,status FROM user_friendships WHERE user_low_id=? AND user_high_id=?', [$lowId, $highId]);
}

function enewsFriendshipRequest(PDO $pdo, int $uid, int $targetId): array {
    enewsEnsureInteractionSchema($pdo);
    if (!one($pdo, 'SELECT id FROM users WHERE id=?', [$targetId])) fail(404, 'USER_NOT_FOUND', 'Không tìm thấy người dùng.');
    $existing = enewsFriendshipPair($pdo, $uid, $targetId);
    if ($existing) {
        if ($existing['status'] === 'accepted') return ['status' => 'accepted'];
        if ((int)$existing['requester_id'] === $uid) return ['status' => 'pending_sent'];
        fail(409, 'FRIEND_REQUEST_RECEIVED', 'Người này đã gửi lời mời cho bạn. Hãy chấp nhận hoặc từ chối lời mời đó.');
    }
    [$lowId, $highId] = [min($uid, $targetId), max($uid, $targetId)];
    $pdo->beginTransaction();
    try {
        execSql($pdo, "INSERT INTO user_friendships (user_low_id,user_high_id,requester_id,status) VALUES (?,?,?,'pending')", [$lowId, $highId, $uid]);
        $friendshipId = (int)$pdo->lastInsertId();
        enewsFriendNotify($pdo, $targetId, $uid, 'friend_request', $friendshipId);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($error instanceof PDOException && $error->getCode() === '23000') {
            $raced = enewsFriendshipPair($pdo, $uid, $targetId);
            if ($raced && $raced['status'] === 'accepted') return ['status' => 'accepted'];
            if ($raced && (int)$raced['requester_id'] === $uid) return ['status' => 'pending_sent'];
            if ($raced) fail(409, 'FRIEND_REQUEST_RECEIVED', 'Người này đã gửi lời mời cho bạn. Hãy chấp nhận hoặc từ chối lời mời đó.');
        }
        throw $error;
    }
    return ['status' => 'pending_sent', 'request_id' => $friendshipId];
}

function enewsFriendshipRespond(PDO $pdo, int $uid, int $friendshipId, bool $accept): array {
    enewsEnsureInteractionSchema($pdo);
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT id,user_low_id,user_high_id,requester_id,status FROM user_friendships WHERE id=? FOR UPDATE');
        $statement->execute([$friendshipId]);
        $friendship = $statement->fetch();
        if (!$friendship || $friendship['status'] !== 'pending') fail(404, 'FRIEND_REQUEST_NOT_FOUND', 'Không tìm thấy lời mời đang chờ.');
        $recipientId = (int)$friendship['requester_id'] === (int)$friendship['user_low_id']
            ? (int)$friendship['user_high_id'] : (int)$friendship['user_low_id'];
        if ($recipientId !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền xử lý lời mời này.');
        if ($accept) {
            execSql($pdo, "UPDATE user_friendships SET status='accepted' WHERE id=? AND status='pending'", [$friendshipId]);
            execSql($pdo, "UPDATE notifications SET read_at=COALESCE(read_at,NOW())
                WHERE recipient_id=? AND actor_id=? AND type='friend_request' AND target_user_id=?",
                [$uid, (int)$friendship['requester_id'], (int)$friendship['requester_id']]);
            enewsFriendNotify($pdo, (int)$friendship['requester_id'], $uid, 'friend_accepted', $friendshipId);
        } else {
            execSql($pdo, 'DELETE FROM user_friendships WHERE id=? AND status=?', [$friendshipId, 'pending']);
            execSql($pdo, "DELETE FROM notifications
                WHERE recipient_id=? AND actor_id=? AND type='friend_request' AND target_user_id=?",
                [(int)$friendship['requester_id'], $uid, $uid]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    return ['status' => $accept ? 'accepted' : 'declined'];
}

function enewsFriendshipCancel(PDO $pdo, int $uid, int $otherUserId): array {
    enewsEnsureInteractionSchema($pdo);
    $friendship = enewsFriendshipPair($pdo, $uid, $otherUserId);
    if (!$friendship || $friendship['status'] !== 'pending' || (int)$friendship['requester_id'] !== $uid) {
        fail(404, 'FRIEND_REQUEST_NOT_FOUND', 'Không tìm thấy lời mời bạn có thể hủy.');
    }
    execSql($pdo, 'DELETE FROM user_friendships WHERE id=? AND requester_id=? AND status=?',
        [(int)$friendship['id'], $uid, 'pending']);
    $otherId = (int)$friendship['user_low_id'] === $uid
        ? (int)$friendship['user_high_id'] : (int)$friendship['user_low_id'];
    execSql($pdo, "DELETE FROM notifications
        WHERE recipient_id=? AND actor_id=? AND type='friend_request' AND target_user_id=?",
        [$otherId, $uid, $uid]);
    return ['status' => 'none'];
}

function enewsFriendshipRemove(PDO $pdo, int $uid, int $otherUserId): array {
    enewsEnsureInteractionSchema($pdo);
    $friendship = enewsFriendshipPair($pdo, $uid, $otherUserId);
    if (!$friendship || $friendship['status'] !== 'accepted') fail(404, 'FRIENDSHIP_NOT_FOUND', 'Hai tài khoản hiện không phải bạn bè.');
    execSql($pdo, "DELETE FROM user_friendships WHERE id=? AND status='accepted'", [(int)$friendship['id']]);
    return ['status' => 'none'];
}

function enewsFriendshipRequests(PDO $pdo, int $uid): array {
    enewsEnsureInteractionSchema($pdo);
    $rows = all($pdo, "SELECT f.id,f.requester_id,f.created_at,
        CASE WHEN f.requester_id=? THEN f.user_high_id ELSE f.requester_id END AS other_user_id,
        u.display_name
        FROM user_friendships f
        JOIN users u ON u.id=CASE WHEN f.requester_id=? THEN f.user_high_id ELSE f.requester_id END
        WHERE (f.user_low_id=? OR f.user_high_id=?) AND f.status='pending'
        ORDER BY f.created_at DESC,f.id DESC", [$uid, $uid, $uid, $uid]);
    return ['requests' => array_map(static fn(array $row): array => [
        'id' => (int)$row['id'],
        'user_id' => (int)$row['other_user_id'],
        'display_name' => (string)$row['display_name'],
        'direction' => (int)$row['requester_id'] === $uid ? 'sent' : 'received',
        'created_at' => $row['created_at'],
    ], $rows)];
}

function enewsFriendshipList(PDO $pdo, int $uid): array {
    enewsEnsureInteractionSchema($pdo);
    $rows = all($pdo, "SELECT CASE WHEN f.user_low_id=? THEN f.user_high_id ELSE f.user_low_id END AS user_id,
        u.display_name
        FROM user_friendships f
        JOIN users u ON u.id=CASE WHEN f.user_low_id=? THEN f.user_high_id ELSE f.user_low_id END
        WHERE (f.user_low_id=? OR f.user_high_id=?) AND f.status='accepted'
        ORDER BY u.display_name,u.id", [$uid, $uid, $uid, $uid]);
    return ['friends' => array_map(static fn(array $row): array => [
        'user_id' => (int)$row['user_id'],
        'display_name' => (string)$row['display_name'],
    ], $rows)];
}

function enewsInteractionUpdatePublicProfile(PDO $pdo, int $uid, array $payload): array {
    enewsEnsureInteractionSchema($pdo);
    $rawBio = $payload['bio'] ?? null;
    if (!is_string($rawBio)) fail(422, 'INVALID_BIO', 'Thông tin giới thiệu không hợp lệ.');
    $bio = trim($rawBio);
    if (mb_strlen($bio) > 500) fail(422, 'BIO_TOO_LONG', 'Thông tin giới thiệu tối đa 500 ký tự.');
    execSql($pdo, 'INSERT INTO enews_public_profiles (user_id,bio) VALUES (?,?)
        ON DUPLICATE KEY UPDATE bio=VALUES(bio)', [$uid, $bio]);
    return ['bio' => $bio];
}

function enewsInteractionFeed(PDO $pdo, int $viewerId, int $limit = 12, int $offset = 0): array {
    enewsEnsureInteractionSchema($pdo);
    $limit = max(1, min(25, $limit));
    $offset = max(0, $offset);
    $rows = all($pdo, "SELECT f.post_id, f.entry_type, f.entry_at, f.share_id, f.shared_by_user_id, f.share_caption, su.display_name AS shared_by_name
        FROM (
            SELECT p.id AS post_id, 'post' AS entry_type, p.created_at AS entry_at,
                   NULL AS share_id, NULL AS shared_by_user_id, '' AS share_caption
            FROM posts p WHERE p.privacy='public' OR p.user_id=?
            UNION ALL
            SELECT p.id AS post_id, 'share' AS entry_type, s.created_at AS entry_at,
                   s.id AS share_id, s.shared_by_user_id, s.caption AS share_caption
            FROM post_shares s JOIN posts p ON p.id=s.source_post_id
            WHERE p.privacy='public'
        ) f
        LEFT JOIN users su ON su.id=f.shared_by_user_id
        ORDER BY f.entry_at DESC, f.share_id DESC, f.post_id DESC
        LIMIT ? OFFSET ?", [$viewerId, $limit + 1, $offset]);

    $hasMore = count($rows) > $limit;
    if ($hasMore) array_pop($rows);
    $feed = [];
    foreach ($rows as $row) {
        $post = enewsInteractionPostDetail($pdo, $viewerId, (int)$row['post_id']);
        $post['entry_type'] = $row['entry_type'];
        $post['entry_at'] = $row['entry_at'];
        if ($row['entry_type'] === 'share') {
            $post['share'] = [
                'id' => (int)$row['shared_by_user_id'],
                'share_id' => (int)$row['share_id'],
                'user_id' => (int)$row['shared_by_user_id'],
                'display_name' => $row['shared_by_name'],
                'avatar_url' => '/profile/avatar/' . (int)$row['shared_by_user_id'],
                'caption' => (string)$row['share_caption'],
            ];
        }
        $feed[] = $post;
    }
    return ['posts' => $feed, 'count' => count($feed), 'limit' => $limit, 'offset' => $offset, 'has_more' => $hasMore, 'next_offset' => $offset + count($feed)];
}

function enewsInteractionCreatePost(PDO $pdo, int $uid, array $payload): array {
    enewsEnsureInteractionSchema($pdo);
    $rawContent = $payload['content'] ?? '';
    if (!is_string($rawContent)) fail(422, 'INVALID_CONTENT', 'Nội dung bài viết không hợp lệ.');
    $content = trim($rawContent);
    $privacy = choice($payload, 'privacy', ['public', 'private'], 'public');
    $images = $payload['images'] ?? [];
    if (!is_array($images) || count($images) > 6) fail(422, 'INVALID_IMAGES', 'Danh sách ảnh không hợp lệ.');
    if ($content === '' && $images === []) fail(422, 'EMPTY_POST', 'Bài viết phải có nội dung hoặc ít nhất một ảnh.');
    if (mb_strlen($content) > 2000) fail(422, 'CONTENT_TOO_LONG', 'Nội dung bài viết tối đa 2000 ký tự.');

    $pdo->beginTransaction();
    try {
        execSql($pdo, 'INSERT INTO posts (user_id, content, privacy) VALUES (?, ?, ?)', [$uid, $content, $privacy]);
        $postId = (int)$pdo->lastInsertId();
        foreach ($images as $index => $rawPath) {
            if (!is_string($rawPath)) fail(422, 'INVALID_IMAGE_PATH', 'Ảnh bài viết không hợp lệ.');
            $path = trim($rawPath);
            if (!preg_match('/^private\\/enews\\/' . $uid . '_[a-f0-9]{32}\\.(?:jpg|png|webp)$/D', $path)) {
                fail(422, 'INVALID_IMAGE_PATH', 'Ảnh không hợp lệ hoặc không thuộc tài khoản hiện tại.');
            }
            $diskPath = enewsInteractionImagePath($path);
            if (!is_file($diskPath)) fail(422, 'INVALID_IMAGE_PATH', 'File ảnh không tồn tại trên máy chủ.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($diskPath);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) fail(422, 'INVALID_FILE_TYPE', 'File ảnh không hợp lệ.');
            execSql($pdo, 'INSERT INTO post_media (post_id,user_id,mime_type,file_name,file_path,file_size,sort_order) VALUES (?,?,?,?,?,?,?)',
                [$postId, $uid, $mime, basename($diskPath), $path, (int)filesize($diskPath), $index]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    return enewsInteractionPostDetail($pdo, $uid, $postId);
}

function enewsInteractionUpdatePost(PDO $pdo, int $uid, int $postId, array $payload): array {
    $post = enewsInteractionPost($pdo, $uid, $postId);
    if ((int)$post['user_id'] !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền sửa bài viết này.');
    $rawContent = $payload['content'] ?? $post['content'];
    if (!is_string($rawContent)) fail(422, 'INVALID_CONTENT', 'Nội dung bài viết không hợp lệ.');
    $content = trim($rawContent);
    $privacy = array_key_exists('privacy', $payload) ? choice($payload, 'privacy', ['public', 'private']) : $post['privacy'];
    $currentMedia = all($pdo, 'SELECT id,file_path FROM post_media WHERE post_id=? ORDER BY sort_order,id', [$postId]);
    $keepIds = array_column($currentMedia, 'id');
    if (array_key_exists('keep_image_ids', $payload)) {
        if (!is_array($payload['keep_image_ids'])) fail(422, 'INVALID_IMAGES', 'Danh sách ảnh cần giữ không hợp lệ.');
        $keepIds = [];
        foreach ($payload['keep_image_ids'] as $rawId) {
            $imageId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($imageId === false || !in_array((string)$imageId, array_map('strval', array_column($currentMedia, 'id')), true)) {
                fail(422, 'INVALID_IMAGES', 'Một hoặc nhiều ảnh không thuộc bài viết này.');
            }
            $keepIds[] = (int)$imageId;
        }
        if (count(array_unique($keepIds)) !== count($keepIds)) fail(422, 'INVALID_IMAGES', 'Danh sách ảnh bị trùng.');
    }
    $newImages = $payload['images'] ?? [];
    if (!is_array($newImages)) fail(422, 'INVALID_IMAGES', 'Danh sách ảnh không hợp lệ.');
    if (count($keepIds) + count($newImages) > 6) fail(422, 'TOO_MANY_IMAGES', 'Bài viết tối đa 6 ảnh.');
    if ($content === '' && count($keepIds) + count($newImages) === 0) fail(422, 'EMPTY_POST', 'Bài viết phải có nội dung hoặc ít nhất một ảnh.');
    if (mb_strlen($content) > 2000) fail(422, 'CONTENT_TOO_LONG', 'Nội dung bài viết tối đa 2000 ký tự.');
    $uploadedRows = [];
    foreach ($newImages as $rawPath) {
        if (!is_string($rawPath)) fail(422, 'INVALID_IMAGE_PATH', 'Ảnh bài viết không hợp lệ.');
        $path = trim($rawPath);
        if (!preg_match('/^private\\/enews\\/' . $uid . '_[a-f0-9]{32}\\.(?:jpg|png|webp)$/D', $path)) {
            fail(422, 'INVALID_IMAGE_PATH', 'Ảnh không hợp lệ hoặc không thuộc tài khoản hiện tại.');
        }
        $diskPath = enewsInteractionImagePath($path);
        if (!is_file($diskPath)) fail(422, 'INVALID_IMAGE_PATH', 'File ảnh không tồn tại trên máy chủ.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($diskPath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) fail(422, 'INVALID_FILE_TYPE', 'File ảnh không hợp lệ.');
        $uploadedRows[] = ['path' => $path, 'disk_path' => $diskPath, 'mime' => $mime];
    }

    $keepSet = array_fill_keys(array_map('strval', $keepIds), true);
    $removed = array_values(array_filter($currentMedia, static fn(array $media): bool => !isset($keepSet[(string)$media['id']])));
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'UPDATE posts SET content=?, privacy=? WHERE id=? AND user_id=?', [$content, $privacy, $postId, $uid]);
        foreach ($removed as $media) {
            execSql($pdo, 'DELETE FROM post_media WHERE id=? AND post_id=?', [(int)$media['id'], $postId]);
        }
        $sortOrder = count($keepIds);
        foreach ($uploadedRows as $image) {
            execSql($pdo, 'INSERT INTO post_media (post_id,user_id,mime_type,file_name,file_path,file_size,sort_order) VALUES (?,?,?,?,?,?,?)',
                [$postId, $uid, $image['mime'], basename($image['disk_path']), $image['path'], (int)filesize($image['disk_path']), $sortOrder++]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    foreach ($removed as $media) {
        $path = (string)$media['file_path'];
        if (str_starts_with($path, 'private/enews/')) {
            $diskPath = enewsInteractionImagePath($path);
            if (!one($pdo, 'SELECT id FROM post_media WHERE file_path=? LIMIT 1', [$path]) && is_file($diskPath) && !unlink($diskPath)) {
                error_log('FlexiDiet eNews could not remove private media after editing post ' . $postId);
            }
        }
    }
    return enewsInteractionPostDetail($pdo, $uid, $postId);
}

function enewsInteractionDeletePost(PDO $pdo, int $uid, int $postId): array {
    $post = enewsInteractionPost($pdo, $uid, $postId);
    if ((int)$post['user_id'] !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền xóa bài viết này.');
    $paths = all($pdo, 'SELECT file_path FROM post_media WHERE post_id=?', [$postId]);
    execSql($pdo, 'DELETE FROM posts WHERE id=? AND user_id=?', [$postId, $uid]);
    foreach ($paths as $media) {
        $relativePath = (string)$media['file_path'];
        if (str_starts_with($relativePath, 'private/enews/')) {
            $diskPath = enewsInteractionImagePath($relativePath);
            if (!one($pdo, 'SELECT id FROM post_media WHERE file_path=? LIMIT 1', [$relativePath]) && is_file($diskPath) && !unlink($diskPath)) {
                error_log('FlexiDiet eNews could not remove private media after deleting post ' . $postId);
            }
        }
    }
    return ['deleted' => true, 'post_id' => $postId];
}

function enewsInteractionNotify(PDO $pdo, int $recipientId, int $actorId, string $type, int $postId, ?int $commentId, string $dedupe): void {
    if ($recipientId === $actorId) return;
    execSql($pdo, 'INSERT IGNORE INTO notifications (recipient_id,actor_id,type,post_id,comment_id,dedupe_key) VALUES (?,?,?,?,?,?)',
        [$recipientId, $actorId, $type, $postId, $commentId, hash('sha256', $dedupe)]);
}

function enewsInteractionToggleLike(PDO $pdo, int $uid, int $postId, bool $like): array {
    $post = enewsInteractionPost($pdo, $uid, $postId);
    $pdo->beginTransaction();
    try {
        if ($like) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO post_likes (post_id,user_id) VALUES (?,?)');
            $stmt->execute([$postId, $uid]);
            if ($stmt->rowCount() === 1) {
                enewsInteractionNotify($pdo, (int)$post['user_id'], $uid, 'post_like', $postId, null, "like:$postId:$uid");
            }
        } else {
            execSql($pdo, 'DELETE FROM post_likes WHERE post_id=? AND user_id=?', [$postId, $uid]);
        }
        $isLiked = (bool)one($pdo, 'SELECT 1 FROM post_likes WHERE post_id=? AND user_id=? LIMIT 1', [$postId, $uid]);
        $count = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM post_likes WHERE post_id=?', [$postId])['c'] ?? 0);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    return [
        'liked' => $isLiked,
        'likes_count' => $count,
    ];
}

function enewsInteractionCommentList(PDO $pdo, int $uid, int $postId, int $limit = 30, int $offset = 0): array {
    enewsInteractionPost($pdo, $uid, $postId);
    $limit = max(1, min(50, $limit));
    $offset = max(0, $offset);
    $rows = all($pdo, 'SELECT c.id,c.post_id,c.user_id,c.parent_id,c.content,c.deleted_at,c.created_at,c.updated_at,u.display_name
        FROM comments c JOIN users u ON u.id=c.user_id
        WHERE c.post_id=?
        ORDER BY COALESCE(c.parent_id,c.id) DESC, c.parent_id IS NOT NULL, c.id
        LIMIT ? OFFSET ?', [$postId, $limit + 1, $offset]);
    $hasMore = count($rows) > $limit;
    if ($hasMore) array_pop($rows);
    $admin = (one($pdo, 'SELECT role FROM users WHERE id=?', [$uid])['role'] ?? '') === 'admin';
    $items = array_map(static function (array $comment) use ($uid, $admin): array {
        $deleted = $comment['deleted_at'] !== null;
        return [
            'id' => (int)$comment['id'],
            'post_id' => (int)$comment['post_id'],
            'user_id' => (int)$comment['user_id'],
            'parent_id' => $comment['parent_id'] === null ? null : (int)$comment['parent_id'],
            'content' => $deleted ? 'Bình luận đã được gỡ.' : (string)$comment['content'],
            'deleted' => $deleted,
            'created_at' => $comment['created_at'],
            'updated_at' => $comment['updated_at'],
            'author' => ['id' => (int)$comment['user_id'], 'display_name' => $comment['display_name'], 'avatar_url' => '/profile/avatar/' . (int)$comment['user_id']],
            'can_edit' => !$deleted && (int)$comment['user_id'] === $uid,
            'can_delete' => !$deleted && ((int)$comment['user_id'] === $uid || $admin),
        ];
    }, $rows);
    $total = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM comments WHERE post_id=? AND deleted_at IS NULL', [$postId])['c'] ?? 0);
    return ['comments' => $items, 'count' => count($items), 'total_count' => $total, 'limit' => $limit, 'offset' => $offset, 'has_more' => $hasMore, 'next_offset' => $offset + count($items)];
}

function enewsInteractionCreateComment(PDO $pdo, int $uid, int $postId, array $payload): array {
    $post = enewsInteractionPost($pdo, $uid, $postId);
    $rawContent = $payload['content'] ?? null;
    if (!is_string($rawContent)) fail(422, 'INVALID_COMMENT', 'Nội dung bình luận không hợp lệ.');
    $content = trim($rawContent);
    if ($content === '' || mb_strlen($content) > 2000) fail(422, 'INVALID_COMMENT', 'Bình luận phải có từ 1 đến 2000 ký tự.');
    $parentId = null;
    if (isset($payload['parent_id']) && $payload['parent_id'] !== null) {
        $parentId = filter_var($payload['parent_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($parentId === false) fail(422, 'INVALID_REPLY', 'Bình luận trả lời không hợp lệ.');
    }
    $parent = null;
    if ($parentId !== null) {
        $parent = one($pdo, 'SELECT id,user_id,parent_id FROM comments WHERE id=? AND post_id=? AND deleted_at IS NULL', [$parentId, $postId]);
        if (!$parent || $parent['parent_id'] !== null) fail(422, 'INVALID_REPLY', 'Chỉ có thể trả lời bình luận gốc còn hiệu lực.');
    }
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'INSERT INTO comments (post_id,user_id,parent_id,content) VALUES (?,?,?,?)', [$postId, $uid, $parentId, $content]);
        $commentId = (int)$pdo->lastInsertId();
        if ($parent !== null) {
            enewsInteractionNotify($pdo, (int)$parent['user_id'], $uid, 'comment_reply', $postId, $commentId, "reply:$commentId");
        } else {
            enewsInteractionNotify($pdo, (int)$post['user_id'], $uid, 'post_comment', $postId, $commentId, "comment:$commentId");
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    $comment = one($pdo, 'SELECT c.id,c.post_id,c.user_id,c.parent_id,c.content,c.created_at,c.updated_at,u.display_name
        FROM comments c JOIN users u ON u.id=c.user_id WHERE c.id=?', [$commentId]);
    if (!$comment) fail(500, 'COMMENT_CREATE_FAILED', 'Không thể tải bình luận vừa tạo.');
    $comment['id'] = (int)$comment['id'];
    $comment['post_id'] = (int)$comment['post_id'];
    $comment['user_id'] = (int)$comment['user_id'];
    $comment['parent_id'] = $comment['parent_id'] === null ? null : (int)$comment['parent_id'];
    $comment['author'] = ['id' => $comment['user_id'], 'display_name' => $comment['display_name'], 'avatar_url' => '/profile/avatar/' . $comment['user_id']];
    unset($comment['display_name']);
    $comment['deleted'] = false;
    $comment['can_edit'] = true;
    $comment['can_delete'] = true;
    $comment['comments_count'] = (int)(one($pdo, 'SELECT COUNT(*) AS c FROM comments WHERE post_id=? AND deleted_at IS NULL', [$postId])['c'] ?? 0);
    return $comment;
}

function enewsInteractionUpdateComment(PDO $pdo, int $uid, int $commentId, array $payload): array {
    $comment = one($pdo, 'SELECT id,post_id,user_id,deleted_at FROM comments WHERE id=?', [$commentId]);
    if (!$comment || $comment['deleted_at'] !== null) fail(404, 'COMMENT_NOT_FOUND', 'Không tìm thấy bình luận.');
    enewsInteractionPost($pdo, $uid, (int)$comment['post_id']);
    if ((int)$comment['user_id'] !== $uid) fail(403, 'FORBIDDEN', 'Bạn không có quyền sửa bình luận này.');
    $rawContent = $payload['content'] ?? null;
    if (!is_string($rawContent)) fail(422, 'INVALID_COMMENT', 'Nội dung bình luận không hợp lệ.');
    $content = trim($rawContent);
    if ($content === '' || mb_strlen($content) > 2000) fail(422, 'INVALID_COMMENT', 'Bình luận phải có từ 1 đến 2000 ký tự.');
    execSql($pdo, 'UPDATE comments SET content=? WHERE id=? AND user_id=? AND deleted_at IS NULL', [$content, $commentId, $uid]);
    $row = one($pdo, 'SELECT c.id,c.post_id,c.user_id,c.parent_id,c.content,c.created_at,c.updated_at,u.display_name
        FROM comments c JOIN users u ON u.id=c.user_id WHERE c.id=?', [$commentId]);
    return [
        'id' => (int)$row['id'], 'post_id' => (int)$row['post_id'], 'user_id' => (int)$row['user_id'],
        'parent_id' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
        'content' => $row['content'], 'deleted' => false, 'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'author' => ['id' => (int)$row['user_id'], 'display_name' => $row['display_name'], 'avatar_url' => '/profile/avatar/' . (int)$row['user_id']],
        'can_edit' => true, 'can_delete' => true,
    ];
}

function enewsInteractionDeleteComment(PDO $pdo, int $uid, int $commentId): array {
    $comment = one($pdo, 'SELECT id,post_id,user_id,deleted_at FROM comments WHERE id=?', [$commentId]);
    if (!$comment || $comment['deleted_at'] !== null) fail(404, 'COMMENT_NOT_FOUND', 'Không tìm thấy bình luận.');
    enewsInteractionPost($pdo, $uid, (int)$comment['post_id']);
    $user = one($pdo, 'SELECT role FROM users WHERE id=?', [$uid]);
    $isOwner = (int)$comment['user_id'] === $uid;
    $isAdmin = ($user['role'] ?? '') === 'admin';
    if (!$isOwner && !$isAdmin) fail(403, 'FORBIDDEN', 'Bạn không có quyền xóa bình luận này.');
    execSql($pdo, "UPDATE comments SET deleted_at=NOW(),content='' WHERE id=? AND deleted_at IS NULL", [$commentId]);
    return ['deleted' => true, 'comment_id' => $commentId, 'moderated_by_admin' => $isAdmin && !$isOwner];
}

function enewsInteractionShare(PDO $pdo, int $uid, int $postId, array $payload): array {
    $post = enewsInteractionPost($pdo, $uid, $postId);
    if ($post['privacy'] !== 'public') fail(403, 'POST_NOT_SHAREABLE', 'Chỉ có thể chia sẻ bài viết công khai.');
    $rawCaption = $payload['caption'] ?? '';
    if (!is_string($rawCaption)) fail(422, 'INVALID_CAPTION', 'Lời dẫn chia sẻ không hợp lệ.');
    $caption = trim($rawCaption);
    if (mb_strlen($caption) > 1000) fail(422, 'CAPTION_TOO_LONG', 'Lời dẫn chia sẻ tối đa 1000 ký tự.');
    execSql($pdo, 'INSERT INTO post_shares (source_post_id,shared_by_user_id,caption) VALUES (?,?,?)', [$postId, $uid, $caption]);
    return [
        'id' => (int)$pdo->lastInsertId(),
        'source_post_id' => $postId,
        'shares_count' => (int)(one($pdo, 'SELECT COUNT(*) AS c FROM post_shares WHERE source_post_id=?', [$postId])['c'] ?? 0),
    ];
}

function enewsInteractionNotifications(PDO $pdo, int $uid, int $limit = 20, int $offset = 0): array {
    enewsEnsureInteractionSchema($pdo);
    $limit = max(1, min(50, $limit));
    $offset = max(0, $offset);
    $rows = all($pdo, "SELECT n.id,n.type,n.post_id,n.comment_id,n.target_user_id,n.created_at,n.read_at,
        a.display_name AS actor_name,p.content AS post_content
        FROM notifications n
        JOIN users a ON a.id=n.actor_id
        LEFT JOIN posts p ON p.id=n.post_id
        WHERE n.recipient_id=? AND (
            (n.type IN ('friend_request','friend_accepted') AND n.target_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users tu WHERE tu.id=n.target_user_id))
            OR (p.id IS NOT NULL AND (p.privacy='public' OR p.user_id=?))
        )
        ORDER BY n.created_at DESC,n.id DESC LIMIT ? OFFSET ?", [$uid, $uid, $limit + 1, $offset]);
    $hasMore = count($rows) > $limit;
    if ($hasMore) array_pop($rows);
    $unread = (int)(one($pdo, "SELECT COUNT(*) AS c FROM notifications n LEFT JOIN posts p ON p.id=n.post_id
        WHERE n.recipient_id=? AND n.read_at IS NULL AND (
            (n.type IN ('friend_request','friend_accepted') AND n.target_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users tu WHERE tu.id=n.target_user_id))
            OR (p.id IS NOT NULL AND (p.privacy='public' OR p.user_id=?))
        )", [$uid, $uid])['c'] ?? 0);
    $items = array_map(static function (array $row): array {
        $message = match ($row['type']) {
            'post_like' => 'đã thích bài viết của bạn',
            'post_comment' => 'đã bình luận về bài viết của bạn',
            'friend_request' => 'đã gửi lời mời kết bạn cho bạn',
            'friend_accepted' => 'đã chấp nhận lời mời kết bạn của bạn',
            default => 'đã trả lời bình luận của bạn',
        };
        return [
            'id' => (int)$row['id'],
            'type' => $row['type'],
            'post_id' => $row['post_id'] === null ? null : (int)$row['post_id'],
            'target_user_id' => $row['target_user_id'] === null ? null : (int)$row['target_user_id'],
            'comment_id' => $row['comment_id'] === null ? null : (int)$row['comment_id'],
            'actor_name' => $row['actor_name'],
            'message' => $message,
            'post_excerpt' => mb_substr((string)$row['post_content'], 0, 120),
            'created_at' => $row['created_at'],
            'read_at' => $row['read_at'],
        ];
    }, $rows);
    return ['notifications' => $items, 'unread_count' => $unread, 'has_more' => $hasMore, 'next_offset' => $offset + count($items)];
}

function enewsInteractionReadNotification(PDO $pdo, int $uid, int $notificationId): array {
    $note = one($pdo, 'SELECT n.id,n.post_id,n.type,n.target_user_id FROM notifications n WHERE n.id=? AND n.recipient_id=?', [$notificationId, $uid]);
    if (!$note) fail(404, 'NOTIFICATION_NOT_FOUND', 'Không tìm thấy thông báo.');
    if ($note['post_id'] !== null) {
        enewsInteractionPost($pdo, $uid, (int)$note['post_id']);
    } elseif (!in_array($note['type'], ['friend_request', 'friend_accepted'], true)
        || !one($pdo, 'SELECT id FROM users WHERE id=?', [(int)$note['target_user_id']])) {
        fail(404, 'NOTIFICATION_NOT_FOUND', 'Không tìm thấy thông báo.');
    }
    execSql($pdo, 'UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND recipient_id=?', [$notificationId, $uid]);
    return ['read' => true, 'notification_id' => $notificationId];
}

function enewsInteractionAvatar(PDO $pdo, int $viewerId, int $avatarUserId): never {
    if ($avatarUserId < 1) fail(404, 'AVATAR_NOT_FOUND', 'Không tìm thấy ảnh đại diện.');
    ensureProfileAvatarTable($pdo);
    $avatar = one($pdo, 'SELECT mime_type,image_data FROM profile_avatars WHERE user_id=?', [$avatarUserId]);
    if (!$avatar) fail(404, 'AVATAR_NOT_FOUND', 'Người dùng chưa cài ảnh đại diện.');
    header('Content-Type: ' . $avatar['mime_type']);
    header('Content-Length: ' . strlen($avatar['image_data']));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    echo $avatar['image_data'];
    exit;
}

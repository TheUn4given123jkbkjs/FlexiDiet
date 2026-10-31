<?php
declare(strict_types=1);

/**
 * ENewsController - Tiếp nhận và điều hướng toàn bộ yêu cầu mạng xã hội FlexiDiet eNews (/enews/*)
 */
class ENewsController {
    /**
     * Xử lý endpoint /enews/* và trả về true nếu route được khớp
     */
    public static function handle(PDO $pdo, int $uid, string $route, string $method): bool {
        // 1. Dòng thời gian (Feed) & Hồ sơ công khai (Profile)
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

        // 2. Bạn bè & Lời mời kết bạn (Friendships)
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

        // 3. Quản lý Tệp đa phương tiện (Media)
        if (preg_match('~^/enews/media/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
            enewsInteractionServeMedia($pdo, $uid, (int)$m[1]);
        }

        if ($route === '/enews/media' && $method === 'POST') {
            response(enewsInteractionUpload($pdo, $uid));
        }

        // 4. Bài viết (Posts) & Tương tác Lượt thích (Likes)
        if ($route === '/enews/posts' && $method === 'POST') {
            response(enewsInteractionCreatePost($pdo, $uid, body()), 201);
        }

        if (preg_match('~^/enews/posts/([1-9][0-9]*)$~D', $route, $m) && $method === 'GET') {
            response(enewsInteractionPostDetail($pdo, $uid, (int)$m[1]));
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

        // 5. Bình luận (Comments) & Chia sẻ (Shares)
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

        // 6. Thông báo (Notifications)
        if ($route === '/enews/notifications' && $method === 'GET') {
            $limit = (int)num($_GET, 'limit', 1, 50, 20);
            $offset = (int)num($_GET, 'offset', 0, 5000, 0);
            response(enewsInteractionNotifications($pdo, $uid, $limit, $offset));
        }

        if (preg_match('~^/enews/notifications/([1-9][0-9]*)$~D', $route, $m) && $method === 'PATCH') {
            response(enewsInteractionReadNotification($pdo, $uid, (int)$m[1]));
        }

        return false;
    }
}

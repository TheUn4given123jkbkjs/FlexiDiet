<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Http.php';
require_once __DIR__ . '/../src/Energy.php';
require_once __DIR__ . '/../src/Mailer.php';
require_once __DIR__ . '/../src/Routes.php';

function enewsTestAssert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

function enewsTestError(callable $action, int $status, string $message): void {
    try {
        $action();
    } catch (ApiError $error) {
        enewsTestAssert($error->status === $status, $message . ': wrong HTTP status ' . $error->status);
        return;
    }
    throw new RuntimeException($message . ': expected API error');
}

$pdo = db();
$suffix = bin2hex(random_bytes(6));
$uids = [];
$testMediaPath = null;
try {
    foreach (['owner', 'member', 'admin'] as $name) {
        execSql($pdo, 'INSERT INTO users (email,password_hash,display_name,role) VALUES (?,?,?,?)', [
            "enews-$name-$suffix@example.test",
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'eNews ' . $name,
            $name === 'admin' ? 'admin' : 'member',
        ]);
        $uids[$name] = (int)$pdo->lastInsertId();
    }

    enewsEnsureInteractionSchema($pdo);
    $ownerId = $uids['owner'];
    $memberId = $uids['member'];
    $adminId = $uids['admin'];
    $public = enewsInteractionCreatePost($pdo, $ownerId, ['content' => 'Public post', 'privacy' => 'public']);
    $private = enewsInteractionCreatePost($pdo, $ownerId, ['content' => 'Private post', 'privacy' => 'private']);

    $liked = enewsInteractionToggleLike($pdo, $memberId, (int)$public['id'], true);
    enewsTestAssert($liked['liked'] && $liked['likes_count'] === 1, 'first like increments count');
    $likedAgain = enewsInteractionToggleLike($pdo, $memberId, (int)$public['id'], true);
    enewsTestAssert($likedAgain['likes_count'] === 1, 'duplicate like is idempotent');
    $unliked = enewsInteractionToggleLike($pdo, $memberId, (int)$public['id'], false);
    enewsTestAssert(!$unliked['liked'] && $unliked['likes_count'] === 0, 'unlike decrements count');
    $likedAgainAfterUnlike = enewsInteractionToggleLike($pdo, $memberId, (int)$public['id'], true);
    enewsTestAssert($likedAgainAfterUnlike['likes_count'] === 1, 're-like keeps the database count exact');
    enewsTestAssert((int)(one($pdo, 'SELECT COUNT(*) AS c FROM notifications WHERE recipient_id=? AND actor_id=? AND type=? AND post_id=?', [
        $ownerId, $memberId, 'post_like', (int)$public['id'],
    ])['c'] ?? 0) === 1, 'repeated like events are deduplicated');

    $privateDirectory = __DIR__ . '/../var/enews';
    if (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0750, true) && !is_dir($privateDirectory)) {
        throw new RuntimeException('Could not create the private media test directory');
    }
    $testMediaName = $ownerId . '_' . bin2hex(random_bytes(16)) . '.png';
    $testMediaPath = $privateDirectory . '/' . $testMediaName;
    $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r9sAAAAASUVORK5CYII=', true);
    if ($imageBytes === false || file_put_contents($testMediaPath, $imageBytes) === false) {
        throw new RuntimeException('Could not create the private media test image');
    }
    $privateWithImage = enewsInteractionCreatePost($pdo, $ownerId, [
        'content' => 'Private image',
        'privacy' => 'private',
        'images' => ['private/enews/' . $testMediaName],
    ]);
    $mediaId = (int)$privateWithImage['images'][0]['id'];
    $authorizedMediaPath = enewsInteractionAuthorizedMedia($pdo, $ownerId, $mediaId)['path'];
    enewsTestAssert(realpath($authorizedMediaPath) === realpath($testMediaPath), 'post owner can access private media');
    $imageRemoved = enewsInteractionUpdatePost($pdo, $ownerId, (int)$privateWithImage['id'], [
        'content' => 'Private image removed',
        'keep_image_ids' => [],
        'images' => [],
    ]);
    enewsTestAssert(count($imageRemoved['images']) === 0 && !is_file($testMediaPath), 'post owner can remove existing media');
    $testMediaPath = null;
    enewsTestError(
        static fn() => enewsInteractionAuthorizedMedia($pdo, $memberId, $mediaId),
        404,
        'another account cannot access private media directly'
    );

    enewsTestError(
        static fn() => enewsInteractionPost($pdo, $memberId, (int)$private['id']),
        404,
        'private post is hidden from another account'
    );
    enewsTestError(
        static fn() => enewsInteractionToggleLike($pdo, $memberId, (int)$private['id'], true),
        404,
        'private post cannot be liked by another account'
    );
    $memberFeed = enewsInteractionFeed($pdo, $memberId);
    enewsTestAssert(!in_array((int)$private['id'], array_column($memberFeed['posts'], 'id'), true), 'private post is absent from another account feed');
    $publicProfile = enewsInteractionProfile($pdo, $memberId, $ownerId);
    enewsTestAssert(count($publicProfile['posts']) === 1 && (int)$publicProfile['posts'][0]['id'] === (int)$public['id'], 'other viewers see only public profile posts');
    enewsTestAssert(!array_key_exists('email', $publicProfile['profile']) && !array_key_exists('health', $publicProfile['profile']), 'social profile excludes account and health data');
    enewsTestAssert($publicProfile['profile']['relationship']['status'] === 'none', 'unrelated accounts have no friendship');
    $ownerProfile = enewsInteractionProfile($pdo, $ownerId, $ownerId);
    enewsTestAssert(count($ownerProfile['posts']) === 3, 'owner can see private posts on own profile');
    enewsInteractionUpdatePublicProfile($pdo, $ownerId, ['bio' => 'Public profile bio']);
    enewsTestAssert(enewsInteractionProfile($pdo, $memberId, $ownerId)['profile']['bio'] === 'Public profile bio', 'owner can update public bio');

    $friendRequest = enewsFriendshipRequest($pdo, $ownerId, $memberId);
    enewsTestAssert($friendRequest['status'] === 'pending_sent', 'user can send friend request');
    enewsTestAssert(enewsFriendshipRequest($pdo, $ownerId, $memberId)['status'] === 'pending_sent', 'repeated friend request is idempotent');
    enewsTestAssert(enewsInteractionProfile($pdo, $ownerId, $memberId)['profile']['relationship']['status'] === 'pending', 'sender sees pending relationship');
    enewsTestAssert(enewsInteractionProfile($pdo, $ownerId, $memberId)['profile']['relationship']['initiated_by_me'], 'sender owns pending request state');
    $incomingRequests = enewsFriendshipRequests($pdo, $memberId)['requests'];
    enewsTestAssert(count($incomingRequests) === 1 && $incomingRequests[0]['direction'] === 'received', 'recipient sees incoming request');
    enewsTestError(
        static fn() => enewsFriendshipRespond($pdo, $adminId, (int)$friendRequest['request_id'], true),
        403,
        'unrelated account cannot accept another user request'
    );
    enewsTestAssert(enewsFriendshipRespond($pdo, $memberId, (int)$friendRequest['request_id'], true)['status'] === 'accepted', 'recipient can accept request');
    enewsTestAssert(enewsInteractionProfile($pdo, $memberId, $ownerId)['profile']['relationship']['status'] === 'accepted', 'accepted relationship appears on profile');
    enewsTestAssert(count(enewsFriendshipList($pdo, $ownerId)['friends']) === 1, 'accepted friend appears in the friends list');
    $friendNotice = array_values(array_filter(
        enewsInteractionNotifications($pdo, $ownerId)['notifications'],
        static fn(array $note): bool => $note['type'] === 'friend_accepted'
    ))[0] ?? null;
    enewsTestAssert($friendNotice && $friendNotice['target_user_id'] === $memberId, 'friend acceptance creates a profile notification');
    enewsTestAssert(enewsFriendshipRemove($pdo, $ownerId, $memberId)['status'] === 'none', 'either friend can unfriend');
    enewsTestAssert(count(enewsFriendshipList($pdo, $ownerId)['friends']) === 0, 'unfriending removes friend from list');
    $secondRequest = enewsFriendshipRequest($pdo, $memberId, $ownerId);
    enewsTestError(
        static fn() => enewsFriendshipRequest($pdo, $ownerId, $memberId),
        409,
        'cannot create a duplicate reverse request while an invitation is pending'
    );
    enewsTestAssert(enewsFriendshipCancel($pdo, $memberId, $ownerId)['status'] === 'none', 'request sender can cancel pending request');
    enewsTestError(
        static fn() => enewsFriendshipCancel($pdo, $ownerId, $memberId),
        404,
        'other account cannot cancel someone else request'
    );
    $declinedRequest = enewsFriendshipRequest($pdo, $memberId, $ownerId);
    enewsTestAssert(enewsFriendshipRespond($pdo, $ownerId, (int)$declinedRequest['request_id'], false)['status'] === 'declined', 'recipient can decline friend request');
    enewsTestAssert(enewsInteractionProfile($pdo, $ownerId, $memberId)['profile']['relationship']['status'] === 'none', 'declined request does not remain pending');

    $comment = enewsInteractionCreateComment($pdo, $memberId, (int)$public['id'], ['content' => 'Root comment']);
    enewsTestAssert($comment['comments_count'] === 1, 'comment count increments');
    $reply = enewsInteractionCreateComment($pdo, $ownerId, (int)$public['id'], [
        'content' => 'Thread reply',
        'parent_id' => $comment['id'],
    ]);
    enewsTestAssert($reply['parent_id'] === $comment['id'], 'reply references root comment');
    enewsTestError(
        static fn() => enewsInteractionUpdateComment($pdo, $ownerId, (int)$comment['id'], ['content' => 'unauthorized']),
        403,
        'non-owner cannot edit a comment'
    );
    $edited = enewsInteractionUpdateComment($pdo, $memberId, (int)$comment['id'], ['content' => 'Edited root']);
    enewsTestAssert($edited['content'] === 'Edited root', 'comment owner can edit');

    $adminComment = enewsInteractionCreateComment($pdo, $memberId, (int)$public['id'], ['content' => 'Needs moderation']);
    $removed = enewsInteractionDeleteComment($pdo, $adminId, (int)$adminComment['id']);
    enewsTestAssert($removed['moderated_by_admin'], 'admin can moderate comment');

    $share = enewsInteractionShare($pdo, $memberId, (int)$public['id'], ['caption' => 'Sharing']);
    enewsTestAssert($share['shares_count'] === 1, 'share count is database-backed');
    enewsTestError(
        static fn() => enewsInteractionShare($pdo, $memberId, (int)$private['id'], ['caption' => 'No']),
        404,
        'private post cannot be shared when not visible'
    );
    $oldShare = array_filter($memberFeed['posts'], static fn(array $post): bool =>
        $post['entry_type'] === 'share' && (int)$post['id'] === (int)$public['id']);
    enewsTestAssert(count($oldShare) === 0, 'old feed snapshot contains no later share');
    $updatedFeed = enewsInteractionFeed($pdo, $memberId);
    $shareEntry = array_values(array_filter($updatedFeed['posts'], static fn(array $post): bool =>
        $post['entry_type'] === 'share' && (int)$post['id'] === (int)$public['id']))[0] ?? null;
    enewsTestAssert($shareEntry && $shareEntry['author']['id'] === $ownerId, 'share retains original author');
    enewsTestAssert($shareEntry['share']['id'] === $memberId && $shareEntry['share']['user_id'] === $memberId, 'share avatar points to the sharing account');

    $notifications = enewsInteractionNotifications($pdo, $ownerId);
    enewsTestAssert($notifications['unread_count'] >= 2, 'likes and comments notify post owner');
    $notificationId = $notifications['notifications'][0]['id'];
    enewsInteractionReadNotification($pdo, $ownerId, $notificationId);
    $afterRead = enewsInteractionNotifications($pdo, $ownerId);
    enewsTestAssert($afterRead['unread_count'] === $notifications['unread_count'] - 1, 'notification can be marked read');

    enewsInteractionDeletePost($pdo, $ownerId, (int)$public['id']);
    enewsTestError(
        static fn() => enewsInteractionPost($pdo, $memberId, (int)$public['id']),
        404,
        'deleted source post is no longer available'
    );
    enewsTestAssert((int)(one($pdo, 'SELECT COUNT(*) AS c FROM post_shares WHERE source_post_id=?', [(int)$public['id']])['c'] ?? 0) === 0, 'deleting source removes shares');
    enewsInteractionDeletePost($pdo, $ownerId, (int)$privateWithImage['id']);

    echo "PASS: eNews likes, privacy, threaded comments, moderation, shares, notifications, and cascades\n";
} finally {
    if ($testMediaPath !== null && is_file($testMediaPath)) unlink($testMediaPath);
    foreach ($uids as $uid) {
        execSql($pdo, 'DELETE FROM users WHERE id=?', [$uid]);
    }
}

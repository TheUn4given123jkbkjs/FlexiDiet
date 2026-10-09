<?php
declare(strict_types=1);

final class ApiError extends RuntimeException {
    public function __construct(public readonly int $status, public readonly string $errorCode, string $message) {
        parent::__construct($message);
    }
}

function response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}
function fail(int $status, string $code, string $message): never { throw new ApiError($status, $code, $message); }
function body(): array {
    $content = file_get_contents('php://input');
    if ($content === false || strlen($content) > 65536) fail(413, 'BODY_TOO_LARGE', 'Dữ liệu gửi lên quá lớn.');
    try { $input = json_decode($content, true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException) { fail(400, 'INVALID_JSON', 'JSON không hợp lệ.'); }
    if (!is_array($input) || array_is_list($input)) fail(400, 'INVALID_JSON', 'Cần gửi đối tượng JSON.');
    return $input;
}
function requiredString(array $p, string $key, int $max = 150): string {
    $v = $p[$key] ?? null;
    if (!is_string($v) || trim($v) === '' || mb_strlen(trim($v)) > $max) fail(422, 'VALIDATION_ERROR', "Trường $key không hợp lệ.");
    return trim($v);
}
function choice(array $p, string $key, array $allowed, ?string $default = null): string {
    $v = $p[$key] ?? $default;
    if (!is_string($v) || !in_array($v, $allowed, true)) fail(422, 'VALIDATION_ERROR', "Trường $key không hợp lệ.");
    return $v;
}
function num(array $p, string $key, float $min, float $max, ?float $default = null): float {
    $v = $p[$key] ?? $default;
    if (!is_numeric($v) || !is_finite((float)$v) || (float)$v < $min || (float)$v > $max) fail(422, 'VALIDATION_ERROR', "Trường $key cần nằm trong khoảng $min–$max.");
    return (float)$v;
}
function dateValue(mixed $value, bool $canBePast = true): string {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) fail(422, 'INVALID_DATE', 'Ngày phải có định dạng YYYY-MM-DD.');
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
    if (!$d || $d->format('Y-m-d') !== $value) fail(422, 'INVALID_DATE', 'Ngày không tồn tại.');
    if ($value > today() || (!$canBePast && $value !== today())) fail(422, 'INVALID_DATE', 'Không được chọn ngày tương lai.');
    return $value;
}
function today(): string { return (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d'); }
function jsonError(Throwable $error): never {
    $status = $error instanceof ApiError ? $error->status : 500;
    $code = $error instanceof ApiError ? $error->errorCode : 'INTERNAL_ERROR';
    if (!($error instanceof ApiError)) error_log((string)$error);
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['error' => ['code' => $code, 'message' => $error instanceof ApiError ? $error->getMessage() : 'Lỗi máy chủ. Vui lòng thử lại.']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
function verifyCsrf(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $originPort = parse_url($origin, PHP_URL_PORT);
        $targetPort = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_PORT);
        if ($originHost !== $host || $originPort !== $targetPort) fail(403, 'ORIGIN_REJECTED', 'Origin không hợp lệ.');
    }
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || $sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) fail(419, 'CSRF_INVALID', 'Mã bảo vệ phiên không hợp lệ. Tải lại trang.');
}
function sessionCsrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function currentUserId(): int {
    $id = (int)($_SESSION['uid'] ?? 0);
    if ($id <= 0) fail(401, 'UNAUTHENTICATED', 'Bạn cần đăng nhập.');
    return $id;
}
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = require __DIR__ . '/../config.php';
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']), $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");
    return $pdo;
}
function one(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return $stmt->fetch() ?: null;
}
function all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return $stmt->fetchAll();
}
function execSql(PDO $pdo, string $sql, array $params = []): void { $stmt = $pdo->prepare($sql); $stmt->execute($params); }

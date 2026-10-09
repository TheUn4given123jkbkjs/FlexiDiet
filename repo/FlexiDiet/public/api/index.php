<?php
declare(strict_types=1);
// API front controller: compatible with PHP built-in server and XAMPP/Apache
require_once __DIR__ . '/../../backend/src/Http.php';
require_once __DIR__ . '/../../backend/src/Energy.php';
require_once __DIR__ . '/../../backend/src/Routes.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies','1');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('fd_session');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
try {
    $route=(string)($_GET['route'] ?? '/ping');
    if (strlen($route)>150 || !preg_match('~^/[a-z0-9/_-]*$~D', $route)) fail(400,'INVALID_ROUTE','Đường dẫn API không hợp lệ.');
    executeRoute($route, $_SERVER['REQUEST_METHOD'] ?? 'GET');
} catch (Throwable $e) { jsonError($e); }

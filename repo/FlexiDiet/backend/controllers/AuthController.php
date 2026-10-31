<?php
declare(strict_types=1);

/**
 * AuthController - Bộ điều khiển tiếp nhận các yêu cầu HTTP xác thực tài khoản
 */
class AuthController {
    public static function register(PDO $pdo, array $params): void {
        response(AuthService::register($pdo, $params), 201);
    }

    public static function login(PDO $pdo, array $params): void {
        response(AuthService::login($pdo, $params));
    }

    public static function logout(): void {
        response(AuthService::logout());
    }

    public static function me(PDO $pdo, int $uid): void {
        response(AuthService::authData($pdo, $uid));
    }

    public static function requestPasswordReset(PDO $pdo, array $params): void {
        response(AuthService::requestPasswordReset($pdo, $params));
    }

    public static function resetPassword(PDO $pdo, array $params): void {
        response(AuthService::resetPassword($pdo, $params));
    }
}


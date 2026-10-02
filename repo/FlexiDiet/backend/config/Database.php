<?php
/**
 * FlexiDiet - Database Connection Manager (PDO)
 * Quản lý kết nối MySQL qua PDO với Prepared Statements bảo mật.
 */

namespace Backend\Config;

use PDO;
use PDOException;

class Database {
    private static $host = '127.0.0.1';
    private static $db_name = 'flexidiet_db';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    private static $instance = null;

    /**
     * Lấy kết nối PDO Singleton
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Trong môi trường development có thể ghi log chi tiết
                error_log("Database Connection Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Không thể kết nối cơ sở dữ liệu.'
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}

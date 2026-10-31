<?php
declare(strict_types=1);

/**
 * AiService - Nghiệp vụ kết nối microservice AI Vision YOLOv10m và xử lý kết quả nhận diện
 */
class AiService {
    public static function proxyGet(string $path, int $timeout = 3): ?array {
        $ch = curl_init('http://127.0.0.1:8001' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $httpCode !== 200) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function recognize(PDO $pdo, int $uid): array {
        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            fail(422, 'NO_IMAGE', 'Vui lòng cung cấp tệp ảnh món ăn để nhận diện.');
        }
        $file = $_FILES['image'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            fail(422, 'UPLOAD_ERROR', 'Tải ảnh lên thất bại. Vui lòng thử lại.');
        }
        if ((int)$file['size'] > 10 * 1024 * 1024) {
            fail(422, 'FILE_TOO_LARGE', 'Kích thước ảnh không được vượt quá 10MB.');
        }
        $tmpPath = (string)$file['tmp_name'];
        $mime = mime_content_type($tmpPath) ?: ($file['type'] ?? 'image/jpeg');
        if (!str_starts_with($mime, 'image/')) {
            fail(422, 'INVALID_FILE_TYPE', 'Tệp tải lên phải là hình ảnh (JPEG, PNG, WebP).');
        }

        AiModel::recordRateLimitHit($pdo, $uid, 'analyze');

        $ch = curl_init('http://127.0.0.1:8001/v1/recognize');
        $cfile = new CURLFile($tmpPath, $mime, $file['name'] ?? 'image.jpg');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['image' => $cfile, 'top_k' => 3],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $httpCode !== 200) {
            return [
                'status' => 'service_unavailable',
                'is_unknown' => true,
                'message' => 'Dịch vụ AI hiện không khả dụng. Bạn có thể tra cứu và ghi món thủ công.',
                'error' => $curlErr ?: ('HTTP ' . $httpCode),
                'predictions' => []
            ];
        }

        $aiData = json_decode($raw, true);
        if (!is_array($aiData)) {
            return [
                'status' => 'service_unavailable',
                'is_unknown' => true,
                'message' => 'Dữ liệu trả về từ dịch vụ AI không hợp lệ.',
                'predictions' => []
            ];
        }

        $isUnknown = !empty($aiData['is_unknown']);
        $predictions = $aiData['predictions'] ?? [];
        $top = $predictions[0] ?? null;

        if ($isUnknown || !$top || (float)($top['confidence'] ?? 0) < 0.40) {
            return [
                'status' => 'unrecognized',
                'is_unknown' => true,
                'message' => 'Không thể nhận diện được món do món chưa có trong danh sách 67 món nhận diện hoặc ảnh chưa rõ nét.',
                'top_prediction' => $top,
                'predictions' => $predictions,
                'inference_ms' => (int)($aiData['inference_ms'] ?? 0)
            ];
        }

        $dish = AiModel::findDishByCodeOrName($pdo, (string)$top['dish_code'], (string)($top['name_vi'] ?? ''));
        $recipe = null;
        if ($dish) {
            try {
                $recipe = FoodService::getRecipe($pdo, $uid, (int)$dish['id']);
            } catch (Throwable) {
                $recipe = null;
            }
        }

        return [
            'status' => 'recognized',
            'is_unknown' => false,
            'dish' => $dish,
            'recipe' => $recipe,
            'top_prediction' => $top,
            'predictions' => $predictions,
            'inference_ms' => (int)($aiData['inference_ms'] ?? 0)
        ];
    }

    public static function saveFeedback(PDO $pdo, int $uid, array $params): array {
        $predCode = !empty($params['predicted_dish_code']) ? (string)$params['predicted_dish_code'] : null;
        $predConf = isset($params['predicted_confidence']) ? (float)$params['predicted_confidence'] : null;
        $correctedCode = !empty($params['corrected_dish_code']) ? (string)$params['corrected_dish_code'] : null;
        $imgPath = !empty($params['image_path']) ? (string)$params['image_path'] : null;
        $items = isset($params['corrected_items']) ? json_encode($params['corrected_items'], JSON_UNESCAPED_UNICODE) : null;

        $id = AiModel::recordFeedback($pdo, $uid, $predCode, $predConf, $correctedCode, $items, $imgPath);
        return ['id' => $id, 'status' => 'recorded'];
    }
}


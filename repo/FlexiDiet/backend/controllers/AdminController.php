<?php
declare(strict_types=1);

/**
 * AdminController - Tiếp nhận và phân phối các yêu cầu HTTP trong khu vực Quản trị viên (/admin/*)
 */
class AdminController {
    public static function handle(PDO $pdo, int $uid, string $route, string $method): bool {
        // 1. Live AI Health, Metrics & Observability
        if ($route === '/admin/ai/health' && $method === 'GET') {
            $h = AiService::proxyGet('/health') ?: [
                'status' => 'Offline', 'service' => 'FlexiDiet AI Microservice',
                'model' => 'YOLOv10m-VietFood67', 'uptime' => '0 phút', 'cpu' => 0, 'ram' => 0
            ];
            response($h);
        }

        if ($route === '/admin/ai/metrics' && $method === 'GET') {
            $m = AiService::proxyGet('/v1/metrics') ?: [
                'volume_today' => 0, 'volume_week' => 0, 'volume_month' => 0,
                'in_flight' => 0, 'error_rate' => 0, 'p50_ms' => 180, 'p95_ms' => 340, 'unknown_detections' => 0
            ];
            $dbVol = one($pdo, 'SELECT 
                COUNT(CASE WHEN hit_at >= CURDATE() THEN 1 END) AS vol_today,
                COUNT(CASE WHEN hit_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS vol_week,
                COUNT(CASE WHEN hit_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) AS vol_month
                FROM rate_limit_hits WHERE bucket = "analyze"');
            if ($dbVol) {
                $m['volume_today'] = max((int)$dbVol['vol_today'], (int)($m['volume_today'] ?? 0));
                $m['volume_week'] = max((int)$dbVol['vol_week'], (int)($m['volume_week'] ?? 0));
                $m['volume_month'] = max((int)$dbVol['vol_month'], (int)($m['volume_month'] ?? 0));
            }
            response($m);
        }

        if ($route === '/admin/ai/labels' && $method === 'GET') {
            $res = AiService::proxyGet('/v1/labels');
            $usedCodes = array_column(all($pdo, 'SELECT dish_code FROM dishes WHERE dish_code IS NOT NULL AND is_active = 1'), 'dish_code');
            $usedMap = array_fill_keys($usedCodes, true);
            $items = [];
            if ($res && !empty($res['labels'])) {
                foreach ($res['labels'] as $l) {
                    $code = $l['dish_code'] ?? '';
                    $items[] = [
                        'dish_code' => $code,
                        'name_full' => $l['name_full'] ?? $code,
                        'name_vi' => $l['name_vi'] ?? $code,
                        'used' => isset($usedMap[$code]) ? 1 : 0
                    ];
                }
            }
            response($items);
        }

        if ($route === '/admin/ai/throughput' && $method === 'GET') {
            $period = (string)($_GET['period'] ?? 'hour');
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
            $bars = [];

            if ($period === 'month') {
                for ($i = 5; $i >= 0; $i--) {
                    $m = $now->sub(new DateInterval("P{$i}M"));
                    $ym = $m->format('Y-m');
                    $c = (int)(one($pdo, 'SELECT COUNT(*) c FROM rate_limit_hits WHERE bucket="analyze" AND DATE_FORMAT(hit_at, "%Y-%m")=?', [$ym])['c'] ?? 0);
                    $bars[] = ['label' => 'T' . $m->format('n'), 'count' => $c];
                }
            } elseif ($period === 'day') {
                $daysVi = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
                $rows = all($pdo, 'SELECT DATE(hit_at) dt, COUNT(*) c 
                    FROM rate_limit_hits 
                    WHERE bucket="analyze" AND hit_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                    GROUP BY DATE(hit_at)');
                $map = [];
                foreach ($rows as $r) $map[$r['dt']] = (int)$r['c'];

                for ($i = 6; $i >= 0; $i--) {
                    $d = $now->sub(new DateInterval("P{$i}D"));
                    $dt = $d->format('Y-m-d');
                    $idx = ((int)$d->format('N')) - 1;
                    $bars[] = ['label' => $daysVi[$idx] ?? $d->format('d/m'), 'count' => $map[$dt] ?? 0];
                }
            } else { // 'hour' (default): 00h to 23h today
                $rows = all($pdo, 'SELECT DATE_FORMAT(hit_at, "%H") hr, COUNT(*) c 
                    FROM rate_limit_hits 
                    WHERE bucket="analyze" AND DATE(hit_at) = CURDATE() 
                    GROUP BY DATE_FORMAT(hit_at, "%H")');
                $countsByHour = [];
                foreach ($rows as $r) {
                    $countsByHour[(int)$r['hr']] = (int)$r['c'];
                }
                for ($hr = 0; $hr < 24; $hr++) {
                    $bars[] = ['label' => sprintf('%02dh', $hr), 'count' => $countsByHour[$hr] ?? 0];
                }
            }
            response($bars);
        }

        if ($route === '/admin/ai/http-distribution' && $method === 'GET') {
            $period = (string)($_GET['period'] ?? 'hour');
            $timeSql = match($period) {
                'month' => 'hit_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)',
                'day' => 'hit_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)',
                default => 'DATE(hit_at) = CURDATE()'
            };

            $rows = all($pdo, "SELECT bucket, COUNT(*) c FROM rate_limit_hits WHERE bucket LIKE 'analyze:%' AND $timeSql GROUP BY bucket");
            $bMap = [];
            foreach ($rows as $r) {
                $code = substr($r['bucket'], strlen('analyze:'));
                $bMap[$code] = (int)$r['c'];
            }

            $m = AiService::proxyGet('/v1/metrics') ?: [];
            $pyTotal = (int)($m['volume_today'] ?? 0);
            $pyUnknown = (int)($m['unknown_detections'] ?? 0);
            $pyErr = (int)round(($pyTotal * (float)($m['error_rate'] ?? 0)) / 100);
            $pySuccess = max(0, $pyTotal - $pyUnknown - $pyErr);

            $c200 = max($bMap['200'] ?? 0, $pySuccess);
            $c422 = max($bMap['422'] ?? 0, $pyUnknown);
            $c500 = max($bMap['500'] ?? ($bMap['503'] ?? 0), $pyErr);
            $c429 = (int)($bMap['429'] ?? 0);
            $total = $c200 + $c422 + $c500 + $c429;

            $items = [
                [
                    'code' => '200',
                    'label' => '200 OK',
                    'badge' => 'Thành công',
                    'desc' => 'Ảnh phân tích & nhận diện hợp lệ',
                    'count' => $c200,
                    'pct' => $total > 0 ? round(($c200 / $total) * 100, 1) : 100.0,
                    'color' => 'var(--fd-green)'
                ],
                [
                    'code' => '422',
                    'label' => '422 Unprocessable',
                    'badge' => 'Không rõ món',
                    'desc' => 'Chưa có trong 67 nhãn hoặc ảnh mờ',
                    'count' => $c422,
                    'pct' => $total > 0 ? round(($c422 / $total) * 100, 1) : 0.0,
                    'color' => '#f59e0b'
                ],
                [
                    'code' => '500',
                    'label' => '500 / 503 Server Error',
                    'badge' => 'Lỗi máy chủ',
                    'desc' => 'Lỗi kết nối mô hình hoặc timeout',
                    'count' => $c500,
                    'pct' => $total > 0 ? round(($c500 / $total) * 100, 1) : 0.0,
                    'color' => '#ef4444'
                ],
                [
                    'code' => '429',
                    'label' => '429 Too Many Requests',
                    'badge' => 'Vượt giới hạn',
                    'desc' => 'Yêu cầu vượt ngưỡng tần suất',
                    'count' => $c429,
                    'pct' => $total > 0 ? round(($c429 / $total) * 100, 1) : 0.0,
                    'color' => '#3b82f6'
                ]
            ];

            response([
                'period' => $period,
                'total' => $total,
                'items' => $items
            ]);
        }

        // 2. Delegate to AdminRoutes for detailed CRUD & System Statistics
        return handleAdminRoute($pdo, $uid, $route, $method);
    }
}

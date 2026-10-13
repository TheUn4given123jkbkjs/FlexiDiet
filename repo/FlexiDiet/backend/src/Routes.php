<?php
declare(strict_types=1);

require_once __DIR__ . '/AdminRoutes.php';

function authData(PDO $pdo, int $uid): array {
    $user = one($pdo, 'SELECT id,display_name,email,role FROM users WHERE id=?', [$uid]);
    if (!$user) { unset($_SESSION['uid']); fail(401, 'UNAUTHENTICATED', 'Phiên đăng nhập không tồn tại.'); }
    return ['user' => $user, 'csrf_token' => sessionCsrf()];
}
function registration(PDO $pdo, array $p): array {
    $name = requiredString($p, 'display_name', 100);
    $email = mb_strtolower(requiredString($p, 'email', 255));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'INVALID_EMAIL', 'Địa chỉ email không hợp lệ.');
    $password = requiredString($p, 'password', 200);
    if (strlen($password) < 8 || strlen($password) > 72) fail(422, 'INVALID_PASSWORD', 'Mật khẩu phải từ 8–72 byte.');
    $sex = choice($p, 'sex', ['male', 'female']);
    $birth = dateValue($p['birth_date'] ?? null);
    $age = (new DateTimeImmutable($birth))->diff(new DateTimeImmutable(today()))->y;
    if ($age < 13 || $age > 110) fail(422, 'INVALID_AGE', 'Tuổi phải từ 13 đến 110.');
    $height = num($p, 'height_cm', 90, 250);
    $weight = num($p, 'weight_kg', 20, 400);
    $goal = choice($p, 'goal', ['lose', 'maintain', 'gain', 'build_muscle']);
    $target = isset($p['target_weight_kg']) && $p['target_weight_kg'] !== '' ? num($p, 'target_weight_kg', 20, 400) : null;
    $weekly = (int)num($p, 'weekly_workout_goal', 0, 7, 3);
    $pdo->beginTransaction();
    try {
        if (one($pdo, 'SELECT id FROM users WHERE email=?', [$email])) fail(409, 'EMAIL_EXISTS', 'Email này đã được đăng ký.');
        execSql($pdo, 'INSERT INTO users (email,password_hash,display_name) VALUES (?,?,?)', [$email, password_hash($password, PASSWORD_DEFAULT), $name]);
        $uid = (int)$pdo->lastInsertId();
        execSql($pdo, 'INSERT INTO user_profiles (user_id,sex,birth_date,height_cm,goal,target_weight_kg,weekly_workout_goal) VALUES (?,?,?,?,?,?,?)', [$uid,$sex,$birth,$height,$goal,$target,$weekly]);
        execSql($pdo, 'INSERT INTO weight_logs (user_id,log_date,weight_kg) VALUES (?,?,?)', [$uid,today(),$weight]);
        ensureBudget($pdo, $uid, today());
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return authData($pdo, $uid);
}
function login(PDO $pdo, array $p): array {
    $email = mb_strtolower(requiredString($p, 'email', 255));
    $password = requiredString($p, 'password', 200);
    $user = one($pdo, 'SELECT id,password_hash FROM users WHERE email=?', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) fail(401, 'INVALID_CREDENTIALS', 'Email hoặc mật khẩu không đúng.');
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return authData($pdo, (int)$user['id']);
}
function updateProfile(PDO $pdo, int $uid, array $p): array {
    $current = profileRow($pdo, $uid);
    $name = isset($p['display_name']) ? requiredString($p, 'display_name', 100) : $current['display_name'];
    $height = isset($p['height_cm']) ? num($p, 'height_cm', 90, 250) : (float)$current['height_cm'];
    $goal = isset($p['goal']) ? choice($p, 'goal', ['lose','maintain','gain','build_muscle']) : $current['goal'];
    $policy = isset($p['credit_policy']) ? choice($p, 'credit_policy', ['full','partial','capped']) : $current['credit_policy'];
    $target = array_key_exists('target_weight_kg', $p) ? ($p['target_weight_kg'] === null ? null : num($p,'target_weight_kg',20,400)) : $current['target_weight_kg'];
    $weight = isset($p['weight_kg']) ? num($p, 'weight_kg', 20, 400) : null;
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'UPDATE users SET display_name=? WHERE id=?', [$name,$uid]);
        execSql($pdo, 'UPDATE user_profiles SET height_cm=?, goal=?, target_weight_kg=?, credit_policy=? WHERE user_id=?', [$height,$goal,$target,$policy,$uid]);
        if ($weight !== null) execSql($pdo, 'INSERT INTO weight_logs (user_id, log_date, weight_kg) VALUES (?,?,?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg)', [$uid,today(),$weight]);
        ensureBudget($pdo, $uid, today(), true);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return profileRow($pdo, $uid);
}
/** Record one measured weight per day. Existing weight_logs UNIQUE(user_id,log_date)
 * gives idempotent edits, and historical daily budgets remain frozen. */
function recordWeight(PDO $pdo, int $uid, array $payload): array {
    $day = dateValue($payload['log_date'] ?? today());
    $kg = num($payload, 'weight_kg', 20, 400);
    $pdo->beginTransaction();
    try {
        execSql($pdo, 'INSERT INTO weight_logs (user_id,log_date,weight_kg) VALUES (?,?,?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg)', [$uid,$day,$kg]);
        // Only today's snapshot changes. Never re-write an older daily budget.
        ensureBudget($pdo,$uid,today(),true);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return ['date'=>$day,'weight_kg'=>$kg,'profile'=>profileRow($pdo,$uid)];
}

/** Read-only analytics: no fictitious activity, no zero-intake assumption.
 * Missing food logs make the comparison unavailable even when a budget exists. */
function profileInsights(PDO $pdo, int $uid, int $weightDays, int $energyDays): array {
    $end=today();
    $weightStart=(new DateTimeImmutable($end))->modify('-'.($weightDays-1).' days')->format('Y-m-d');
    $energyStart=(new DateTimeImmutable($end))->modify('-'.($energyDays-1).' days')->format('Y-m-d');
    $profile=profileRow($pdo,$uid);
    $weightRows=all($pdo,'SELECT log_date date,weight_kg kg FROM weight_logs WHERE user_id=? AND log_date BETWEEN ? AND ? ORDER BY log_date',[$uid,$weightStart,$end]);
    $budgetRows=all($pdo,'SELECT budget_date date,target_kcal target,exercise_raw_kcal raw,exercise_credit_kcal credit FROM daily_budgets WHERE user_id=? AND budget_date BETWEEN ? AND ?',[$uid,$energyStart,$end]);
    $meals=all($pdo,'SELECT entry_date date,COUNT(*) count_entries,ROUND(SUM(total_kcal),1) kcal FROM meal_entries WHERE user_id=? AND entry_date BETWEEN ? AND ? GROUP BY entry_date',[$uid,$energyStart,$end]);
    $workouts=all($pdo,'SELECT workout_date date,COUNT(*) count_entries,ROUND(SUM(raw_kcal),1) raw FROM workouts WHERE user_id=? AND workout_date BETWEEN ? AND ? GROUP BY workout_date',[$uid,$energyStart,$end]);
    $map=[];
    foreach ($budgetRows as $r) { $d=$r['date']; $map[$d]=['target'=>(float)$r['target'],'credit'=>(float)$r['credit'],'raw'=>(float)$r['raw']]; }
    $mealMap=[];
    foreach ($meals as $r) $mealMap[$r['date']]=(float)$r['kcal'];
    $workoutMap=[];
    foreach ($workouts as $r) $workoutMap[$r['date']]=(float)$r['raw'];
    $energy=[];
    $cursor=new DateTimeImmutable($energyStart); $last=new DateTimeImmutable($end);
    while ($cursor <= $last) {
        $d=$cursor->format('Y-m-d'); $budget=$map[$d] ?? null;
        $hasMeal=array_key_exists($d,$mealMap);
        $intake=$hasMeal?$mealMap[$d]:null;
        $dynamic=$budget ? round($budget['target']+$budget['credit'],1):null;
        $energy[]=[
            'date'=>$d,'has_meals'=>$hasMeal,'has_budget'=>$budget!==null,
            'intake_kcal'=>$intake,'base_target_kcal'=>$budget['target'] ?? null,
            'exercise_raw_kcal'=>$workoutMap[$d] ?? ($budget['raw'] ?? 0),
            'exercise_credit_kcal'=>$budget['credit'] ?? null,
            'dynamic_budget_kcal'=>$dynamic,
            'difference_kcal'=>($hasMeal && $budget) ? round($dynamic-$intake,1):null,
        ];
        $cursor=$cursor->modify('+1 day');
    }
    $weight=(float)($profile['weight_kg'] ?? 0);
    $height=(float)$profile['height_cm'];
    $bmi=$weight>0 && $height>0 ? round($weight/(($height/100)**2),1):null;
    $age=(new DateTimeImmutable($profile['birth_date']))->diff(new DateTimeImmutable($end))->y;
    $category='Chưa có dữ liệu';
    if ($bmi!==null) {
        $category=$age<18 ? 'Cần đánh giá BMI theo tuổi và giới' :
          ($bmi<18.5?'Dưới khoảng tham khảo':($bmi<25?'Trong khoảng tham khảo':($bmi<30?'Trên khoảng tham khảo':'Cao hơn khoảng tham khảo')));
    }
    return [
        'profile'=>['bmi'=>$bmi,'bmi_category'=>$category,'adult_bmi'=>$age>=18,'weight_kg'=>$weight ?: null,'height_cm'=>$height,'target_weight_kg'=>$profile['target_weight_kg']===null?null:(float)$profile['target_weight_kg']],
        'weights'=>array_map(static fn($r)=>['date'=>$r['date'],'kg'=>(float)$r['kg']],$weightRows),
        'energy'=>$energy,'weight_days'=>$weightDays,'energy_days'=>$energyDays,
        'notes'=>['difference'=>'Ngân sách động − calo đã ăn. Không đồng nghĩa với thâm hụt năng lượng sinh lý.','missing'=>'Ngày chưa ghi bữa ăn không được xem là 0 kcal.']
    ];
}

function mealList(PDO $pdo, int $uid, string $day): array {
    return all($pdo, 'SELECT id,entry_date,meal_type,name,source_kind,source_dish_id,serving_factor,total_kcal,total_protein_g,total_carb_g,total_fat_g,created_at FROM meal_entries WHERE user_id=? AND entry_date=? ORDER BY created_at DESC,id DESC', [$uid,$day]);
}
function workoutList(PDO $pdo, int $uid, ?string $date = null, ?int $year = null): array {
    if ($year !== null) return all($pdo, 'SELECT w.id,w.workout_date,w.duration_min,w.distance_km,w.raw_kcal,w.calorie_source,w.confidence,w.note,w.created_at,t.code exercise_code,t.name_vi exercise_name FROM workouts w JOIN exercise_types t ON t.id=w.exercise_type_id WHERE w.user_id=? AND w.workout_date BETWEEN ? AND ? ORDER BY w.workout_date DESC,w.id DESC LIMIT 1000', [$uid,"$year-01-01", "$year-12-31"]);
    return all($pdo, 'SELECT w.id,w.workout_date,w.duration_min,w.distance_km,w.raw_kcal,w.calorie_source,w.confidence,w.note,w.created_at,t.code exercise_code,t.name_vi exercise_name FROM workouts w JOIN exercise_types t ON t.id=w.exercise_type_id WHERE w.user_id=? AND w.workout_date=? ORDER BY w.id DESC', [$uid,$date]);
}
function createWorkout(PDO $pdo, int $uid, array $p): array {
    $day = dateValue($p['workout_date'] ?? today());
    $code = choice($p, 'exercise_code', ['running','cycling','gym','swimming','hiit','jump_rope','walking','yoga','badminton','football']);
    $duration = num($p,'duration_min',1,600);
    $distance = isset($p['distance_km']) && $p['distance_km'] !== '' ? num($p,'distance_km',0,500) : null;
    $source = choice($p,'calorie_source',['met','device'],'met');
    $type = one($pdo, 'SELECT t.id, r.met FROM exercise_types t LEFT JOIN exercise_met_rules r ON r.exercise_type_id=t.id WHERE t.code=? AND t.is_active=1 ORDER BY r.id LIMIT 1', [$code]);
    if (!$type || ($source === 'met' && $type['met'] === null)) fail(422,'EXERCISE_SEEDS_REQUIRED','Cần import database/seeds/06_exercise_types.sql.');
    $note = isset($p['note']) ? mb_substr(trim((string)$p['note']),0,255) : null;
    $weight = $source === 'met' ? weightOn($pdo,$uid,$day) : null;
    $met = $source === 'met' ? (float)$type['met'] : null;
    $raw = $source === 'met' ? workoutKcal($met,$weight,$duration) : num($p,'raw_kcal',0,5000);
    $pdo->beginTransaction();
    try {
        if ($day === today()) ensureBudget($pdo,$uid,$day);
        execSql($pdo,'INSERT INTO workouts (user_id,workout_date,exercise_type_id,duration_min,distance_km,calorie_source,confidence,met_value,weight_kg_used,raw_kcal,note) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$uid,$day,$type['id'],$duration,$distance,$source,$source === 'met' ? 'medium':'high',$met,$weight,$raw,$note]);
        $id = (int)$pdo->lastInsertId();
        if ($day === today() || one($pdo, 'SELECT id FROM daily_budgets WHERE user_id=? AND budget_date=?', [$uid,$day])) ensureBudget($pdo,$uid,$day);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return one($pdo,'SELECT w.id,w.workout_date,w.duration_min,w.raw_kcal,w.calorie_source,t.code exercise_code,t.name_vi exercise_name FROM workouts w JOIN exercise_types t ON t.id=w.exercise_type_id WHERE w.id=?', [$id]);
}
function deleteWorkout(PDO $pdo,int $uid,int $id): void {
    $pdo->beginTransaction();
    try {
        $old = one($pdo, 'SELECT workout_date FROM workouts WHERE id=? AND user_id=? FOR UPDATE', [$id,$uid]);
        if (!$old) fail(404,'NOT_FOUND','Không tìm thấy buổi tập.');
        execSql($pdo,'DELETE FROM workouts WHERE id=? AND user_id=?',[$id,$uid]);
        if ($old['workout_date'] === today() || one($pdo, 'SELECT id FROM daily_budgets WHERE user_id=? AND budget_date=?', [$uid,$old['workout_date']])) ensureBudget($pdo,$uid,$old['workout_date']);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function normalizeVietnamese(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $trans = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y'
    ];
    return strtr($str, $trans);
}
function dishRecipe(PDO $pdo, int $uid, int $dishId): array {
    $dish = one($pdo, 'SELECT d.id, d.name, d.serving_label, d.dish_code, d.origin FROM dishes d WHERE d.id=? AND d.is_active=1 AND (d.owner_user_id IS NULL OR d.owner_user_id=?)', [$dishId, $uid]);
    if (!$dish) fail(404, 'DISH_NOT_FOUND', 'Không tìm thấy món ăn.');
    $ingredients = all($pdo, 'SELECT di.ingredient_id, i.name, di.grams, i.kcal_100g, i.protein_100g, i.carb_100g, i.fat_100g, i.source,
        (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id=i.id AND pu.size_label="small" LIMIT 1) grams_small,
        (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id=i.id AND pu.size_label="medium" LIMIT 1) grams_medium,
        (SELECT pu.grams_per_unit FROM portion_units pu WHERE pu.ingredient_id=i.id AND pu.size_label="large" LIMIT 1) grams_large
        FROM dish_ingredients di JOIN ingredients i ON i.id=di.ingredient_id
        WHERE di.dish_id=? AND i.is_active=1 ORDER BY di.sort_order', [$dishId]);
    return ['dish' => $dish, 'ingredients' => $ingredients];
}
function searchIngredients(PDO $pdo, int $uid, string $q): array {
    $term = '%' . $q . '%';
    $normTerm = '%' . normalizeVietnamese($q) . '%';
    return all($pdo, 'SELECT i.id, i.name, i.kcal_100g, i.protein_100g, i.carb_100g, i.fat_100g, i.state, i.source,
        (SELECT ia.alias FROM ingredient_aliases ia WHERE ia.ingredient_id = i.id AND (ia.alias LIKE ? OR ia.alias_norm LIKE ?) LIMIT 1) AS matched_alias
        FROM ingredients i 
        WHERE i.is_active=1 
          AND (i.owner_user_id IS NULL OR i.owner_user_id=?) 
          AND (
            i.name LIKE ? 
            OR i.name_norm LIKE ? 
            OR EXISTS (
                SELECT 1 FROM ingredient_aliases ia 
                WHERE ia.ingredient_id = i.id 
                  AND (ia.alias LIKE ? OR ia.alias_norm LIKE ?)
            )
          ) 
        ORDER BY (i.name LIKE ? OR i.name_norm LIKE ?) DESC, i.name 
        LIMIT 30', 
        [$term, $normTerm, $uid, $term, $normTerm, $term, $normTerm, $term, $normTerm]
    );
}
function createCustomDish(PDO $pdo, int $uid, array $p): array {
    $name = requiredString($p, 'name');
    $label = isset($p['serving_label']) ? requiredString($p, 'serving_label', 60) : '1 phần';
    $items = $p['ingredients'] ?? [];
    if (!is_array($items) || empty($items)) fail(422, 'EMPTY_INGREDIENTS', 'Món ăn phải có ít nhất một nguyên liệu.');
    $norm = normalizeVietnamese($name);
    $pdo->beginTransaction();
    try {
        $dup = one($pdo, 'SELECT id FROM dishes WHERE owner_user_id=? AND name_norm=? AND is_active=1', [$uid, $norm]);
        if ($dup) fail(409, 'DISH_EXISTS', 'Bạn đã có món ăn với tên này trong danh mục cá nhân.');
        execSql($pdo, 'INSERT INTO dishes (owner_user_id, name, name_norm, origin, serving_label, is_active) VALUES (?, ?, ?, "saved", ?, 1)', [$uid, $name, $norm, $label]);
        $dishId = (int)$pdo->lastInsertId();
        foreach ($items as $idx => $it) {
            $ingId = (int)num($it, 'ingredient_id', 1, 999999999);
            $grams = num($it, 'grams', 0.1, 2000);
            $check = one($pdo, 'SELECT id FROM ingredients WHERE id=? AND is_active=1', [$ingId]);
            if (!$check) fail(422, 'INVALID_INGREDIENT', 'Nguyên liệu không tồn tại.');
            execSql($pdo, 'INSERT INTO dish_ingredients (dish_id, ingredient_id, grams, sort_order) VALUES (?, ?, ?, ?)', [$dishId, $ingId, $grams, $idx]);
        }
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return ['id' => $dishId, 'name' => $name, 'serving_label' => $label, 'origin' => 'saved'];
}
function createMeal(PDO $pdo,int $uid,array $p): array {
    $day = dateValue($p['entry_date'] ?? today());
    $meal = choice($p, 'meal_type', ['breakfast','lunch','dinner','snack']);
    $dishId = isset($p['dish_id']) ? (int)num($p,'dish_id',1,999999999) : null;
    $items = isset($p['items']) && is_array($p['items']) ? $p['items'] : null;
    $recipeItems = [];
    if ($items !== null) {
        $name = requiredString($p, 'name');
        if (empty($items)) fail(422, 'EMPTY_ITEMS', 'Món ăn phải có ít nhất một nguyên liệu.');
        $totals = [0, 0, 0, 0];
        foreach ($items as $idx => $it) {
            $ingId = (int)num($it, 'ingredient_id', 1, 999999999);
            $grams = num($it, 'grams', 0.1, 2000);
            $ing = one($pdo, 'SELECT id, name, source, kcal_100g, protein_100g, carb_100g, fat_100g FROM ingredients WHERE id=? AND is_active=1', [$ingId]);
            if (!$ing) fail(422, 'INVALID_INGREDIENT', "Nguyên liệu không tồn tại hoặc đã bị khóa.");
            $ingKcal = round($grams / 100 * (float)$ing['kcal_100g'], 1);
            $ingProt = round($grams / 100 * (float)$ing['protein_100g'], 1);
            $ingCarb = round($grams / 100 * (float)$ing['carb_100g'], 1);
            $ingFat = round($grams / 100 * (float)$ing['fat_100g'], 1);
            $totals[0] += $ingKcal;
            $totals[1] += $ingProt;
            $totals[2] += $ingCarb;
            $totals[3] += $ingFat;
            $recipeItems[] = [
                'ingredient_id' => $ingId,
                'name' => $ing['name'],
                'grams' => $grams,
                'kcal' => $ingKcal,
                'protein_g' => $ingProt,
                'carb_g' => $ingCarb,
                'fat_g' => $ingFat,
                'source' => $ing['source']
            ];
        }
        [$kcal, $protein, $carb, $fat] = array_map(fn($v) => round($v, 1), $totals);
        $factor = null;
    } elseif ($dishId) {
        $dish = one($pdo,'SELECT id,name FROM dishes WHERE id=? AND is_active=1 AND (owner_user_id IS NULL OR owner_user_id=?)',[$dishId,$uid]);
        if (!$dish) fail(404,'DISH_NOT_FOUND','Không tìm thấy món ăn.');
        $factor = num($p,'serving_factor',0.1,10,1);
        $recipeItemsRaw = all($pdo, 'SELECT di.ingredient_id,i.name,i.source,di.grams,i.kcal_100g,i.protein_100g,i.carb_100g,i.fat_100g FROM dish_ingredients di JOIN ingredients i ON i.id=di.ingredient_id WHERE di.dish_id=? AND i.is_active=1 ORDER BY di.sort_order', [$dishId]);
        if (!$recipeItemsRaw) fail(422,'DISH_WITHOUT_RECIPE','Món ăn chưa có công thức dinh dưỡng. Hãy ghi thủ công.');
        $name = $dish['name']; $totals = [0,0,0,0];
        foreach ($recipeItemsRaw as $it) {
            $grams = round((float)$it['grams'] * $factor, 1);
            $ingKcal = round($grams / 100 * (float)$it['kcal_100g'], 1);
            $ingProt = round($grams / 100 * (float)$it['protein_100g'], 1);
            $ingCarb = round($grams / 100 * (float)$it['carb_100g'], 1);
            $ingFat = round($grams / 100 * (float)$it['fat_100g'], 1);
            $totals[0] += $ingKcal; $totals[1] += $ingProt; $totals[2] += $ingCarb; $totals[3] += $ingFat;
            $recipeItems[] = [
                'ingredient_id' => $it['ingredient_id'],
                'name' => $it['name'],
                'grams' => $grams,
                'kcal' => $ingKcal,
                'protein_g' => $ingProt,
                'carb_g' => $ingCarb,
                'fat_g' => $ingFat,
                'source' => $it['source']
            ];
        }
        [$kcal,$protein,$carb,$fat] = array_map(fn($v)=>round($v,1),$totals);
    } else {
        $factor = null;
        $name = requiredString($p,'name');
        $kcal = num($p,'total_kcal',0,5000);
        $protein = num($p,'total_protein_g',0,500);
        $carb = num($p,'total_carb_g',0,800);
        $fat = num($p,'total_fat_g',0,500);
    }
    $pdo->beginTransaction();
    try {
        if ($day === today()) {
            ensureBudget($pdo,$uid,$day);
            // Lock the daily budget row and read the latest committed meals.
            // Locking reads avoid a stale REPEATABLE READ snapshot when other tabs
            // save meals at almost the same time; no database migration is needed.
            $budget = one($pdo, 'SELECT target_kcal,exercise_credit_kcal FROM daily_budgets WHERE user_id=? AND budget_date=? FOR UPDATE', [$uid,$day]);
            $existingMeals = all($pdo, 'SELECT total_kcal FROM meal_entries WHERE user_id=? AND entry_date=? FOR UPDATE', [$uid,$day]);
            $consumed = array_sum(array_map(static fn($meal) => (float)$meal['total_kcal'], $existingMeals));
            $remaining = (float)$budget['target_kcal'] + (float)$budget['exercise_credit_kcal'] - $consumed;
            if ($kcal > $remaining + 0.0001 && ($p['confirm_over_budget'] ?? false) !== true) {
                fail(409, 'BUDGET_EXCEEDED', 'Món ăn sẽ vượt ngân sách calo hôm nay. Vui lòng xác nhận nếu vẫn muốn thêm.');
            }
        }
        execSql($pdo, 'INSERT INTO meal_entries (user_id,entry_date,meal_type,name,source_kind,source_dish_id,serving_factor,total_kcal,total_protein_g,total_carb_g,total_fat_g) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$uid,$day,$meal,$name,'manual',$dishId,$factor,$kcal,$protein,$carb,$fat]);
        $id=(int)$pdo->lastInsertId();
        foreach ($recipeItems as $i=>$it) {
            execSql($pdo,'INSERT INTO meal_entry_items (entry_id,ingredient_id,ingredient_name,grams,kcal,protein_g,carb_g,fat_g,nutrition_source,line_origin,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$id,$it['ingredient_id'],$it['name'],$it['grams'],$it['kcal'],$it['protein_g'],$it['carb_g'],$it['fat_g'],$it['source'],'recipe',$i]);
        }
        $pdo->commit();
    } catch (Throwable $e) {if ($pdo->inTransaction()) $pdo->rollBack(); throw $e;}
    return one($pdo,'SELECT * FROM meal_entries WHERE id=? AND user_id=?',[$id,$uid]);
}

function requireAdmin(PDO $pdo, int $uid): void {
    $u = one($pdo, 'SELECT role FROM users WHERE id=?', [$uid]);
    if (!$u || $u['role'] !== 'admin') fail(403, 'FORBIDDEN', 'Yêu cầu quyền Quản trị viên.');
}

function proxyAiGet(string $path, int $timeout = 3): ?array {
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

function recognizeFood(PDO $pdo, int $uid): array {
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

    try {
        execSql($pdo, 'INSERT INTO rate_limit_hits (user_id, bucket, hit_at) VALUES (?, "analyze", NOW(3))', [$uid]);
    } catch (Throwable) {}

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

    $dish = one($pdo, 'SELECT id, name, name_norm, serving_label, dish_code, origin FROM dishes WHERE dish_code=? AND is_active=1 LIMIT 1', [$top['dish_code']]);
    if (!$dish) {
        $dish = one($pdo, 'SELECT id, name, name_norm, serving_label, dish_code, origin FROM dishes WHERE (name LIKE ? OR name_norm LIKE ?) AND is_active=1 LIMIT 1', ['%' . $top['name_vi'] . '%', '%' . normalizeVietnamese($top['name_vi']) . '%']);
    }

    $recipe = null;
    if ($dish) {
        try {
            $recipe = dishRecipe($pdo, $uid, (int)$dish['id']);
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

function executeRoute(string $route,string $method): never {
    if ($route === '/ping' && $method === 'GET') response(['status'=>'ok','service'=>'FlexiDiet PHP API']);
    if ($route === '/health' && $method === 'GET') { db()->query('SELECT 1'); response(['status'=>'ok','database'=>'connected']); }
    if ($route === '/auth/csrf' && $method === 'GET') response(['csrf_token'=>sessionCsrf()]);
    if ($method !== 'GET') verifyCsrf();
    if ($route === '/auth/register' && $method === 'POST') response(registration(db(),body()),201);
    if ($route === '/auth/login' && $method === 'POST') response(login(db(),body()));
    if ($route === '/auth/logout' && $method === 'POST') {
        currentUserId(); unset($_SESSION['uid']); session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(32)); response(['logged_out'=>true]);
    }
    if ($route === '/auth/me' && $method === 'GET') { $uid=currentUserId(); response(authData(db(),$uid)); }
    $uid = currentUserId(); $pdo = db();
    if ($route === '/profile' && $method === 'GET') response(profileRow($pdo,$uid));
    if ($route === '/profile' && $method === 'PATCH') response(updateProfile($pdo,$uid,body()));
    if ($route === '/weight-logs' && $method === 'POST') response(recordWeight($pdo,$uid,body()),201);
    if ($route === '/profile/insights' && $method === 'GET') {
        $w=(int)num($_GET,'weight_days',30,1825,365);
        $e=(int)num($_GET,'energy_days',7,365,7);
        if (!in_array($w,[30,90,365,1825],true) || !in_array($e,[7,30,365],true)) fail(422,'INVALID_PERIOD','Khoảng thời gian không được hỗ trợ.');
        response(profileInsights($pdo,$uid,$w,$e));
    }
    if ($route === '/dashboard' && $method === 'GET') {
        $day = dateValue($_GET['date'] ?? today());
        $pdo->beginTransaction();
        try { ensureBudget($pdo,$uid,$day); $pdo->commit(); } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        response(['budget'=>budgetView($pdo,$uid,$day),'meals'=>mealList($pdo,$uid,$day),'workouts'=>workoutList($pdo,$uid,$day),'profile'=>profileRow($pdo,$uid)]);
    }
    if ($route === '/foods' && $method === 'GET') {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q)>100) fail(422,'INVALID_QUERY','Từ khóa quá dài.');
        $term = '%' . $q . '%';
        response(all($pdo,'SELECT d.id,d.name,d.name_norm,d.serving_label,d.dish_code,d.origin,ROUND(SUM(di.grams*i.kcal_100g/100),1) kcal, ROUND(SUM(di.grams*i.protein_100g/100),1) protein_g, ROUND(SUM(di.grams*i.carb_100g/100),1) carb_g,ROUND(SUM(di.grams*i.fat_100g/100),1) fat_g FROM dishes d JOIN dish_ingredients di ON di.dish_id=d.id JOIN ingredients i ON i.id=di.ingredient_id WHERE d.is_active=1 AND i.is_active=1 AND (d.owner_user_id IS NULL OR d.owner_user_id=?) AND (d.name LIKE ? OR d.name_norm LIKE ? OR d.dish_code LIKE ?) GROUP BY d.id,d.name,d.name_norm,d.serving_label,d.dish_code,d.origin ORDER BY d.origin DESC, d.name LIMIT 300',[$uid,$term,$term,$term]));
    }
    if (preg_match('~^/foods/([1-9][0-9]*)/recipe$~D', $route, $m) && $method === 'GET') {
        response(dishRecipe($pdo, $uid, (int)$m[1]));
    }
    if ($route === '/ingredients' && $method === 'GET') {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 100) fail(422, 'INVALID_QUERY', 'Từ khóa quá dài.');
        response(searchIngredients($pdo, $uid, $q));
    }
    if ($route === '/dishes' && $method === 'POST') {
        response(createCustomDish($pdo, $uid, body()), 201);
    }
    if ($route === '/meals' && $method === 'GET') response(mealList($pdo,$uid,dateValue($_GET['date'] ?? today())));
    if ($route === '/meals' && $method === 'POST') response(createMeal($pdo,$uid,body()),201);
    if (preg_match('~^/meals/([1-9][0-9]*)$~D',$route,$m) && $method==='DELETE') {
        $stmt=$pdo->prepare('DELETE FROM meal_entries WHERE id=? AND user_id=?');$stmt->execute([(int)$m[1],$uid]);
        if (!$stmt->rowCount()) fail(404,'NOT_FOUND','Không tìm thấy món đã ghi.');
        response(['deleted'=>true]);
    }
    if ($route === '/exercises' && $method === 'GET') response(all($pdo,'SELECT t.id,t.code,t.name_vi,t.uses_distance,(SELECT r.met FROM exercise_met_rules r WHERE r.exercise_type_id=t.id ORDER BY r.id LIMIT 1) default_met FROM exercise_types t WHERE t.is_active=1 ORDER BY t.id'));
    if ($route === '/workouts' && $method === 'GET') {
        if (isset($_GET['year'])) {
            $year = (int)num($_GET,'year',2000,(int)date('Y')); response(workoutList($pdo,$uid,null,$year));
        }
        response(workoutList($pdo,$uid,dateValue($_GET['date'] ?? today())));
    }
    if ($route === '/workouts/heatmap' && $method === 'GET') {
        $year=(int)num($_GET,'year',2000,(int)date('Y'),(float)date('Y'));
        response(all($pdo,'SELECT workout_date date,COUNT(*) sessions,SUM(duration_min) minutes,ROUND(SUM(raw_kcal),1) raw_kcal FROM workouts WHERE user_id=? AND workout_date BETWEEN ? AND ? GROUP BY workout_date ORDER BY workout_date',[$uid,"$year-01-01","$year-12-31"]));
    }
    if ($route === '/workouts' && $method === 'POST') response(createWorkout($pdo,$uid,body()),201);
    if (preg_match('~^/workouts/([1-9][0-9]*)$~D',$route,$m) && $method==='DELETE') {deleteWorkout($pdo,$uid,(int)$m[1]);response(['deleted'=>true]);}
    if ($route === '/water' && $method === 'POST') {
        $p=body(); $ml=(int)num($p,'amount_ml',1,3000); $day=dateValue($p['log_date'] ?? today());
        execSql($pdo,'INSERT INTO water_logs (user_id,log_date,amount_ml) VALUES (?,?,?)',[$uid,$day,$ml]);
        response(['amount_ml'=>$ml,'water_total_ml'=>(int)one($pdo,'SELECT SUM(amount_ml) total FROM water_logs WHERE user_id=? AND log_date=?',[$uid,$day])['total']],201);
    }
    if ($route === '/ai/recognize' && $method === 'POST') response(recognizeFood($pdo, $uid));
    if (str_starts_with($route, '/admin/')) {
        requireAdmin($pdo, $uid);
        if ($route === '/admin/ai/health' && $method === 'GET') {
            $h = proxyAiGet('/health') ?: [
                'status' => 'Offline', 'service' => 'FlexiDiet AI Microservice',
                'model' => 'YOLOv10m-VietFood67', 'uptime' => '0 phút', 'cpu' => 0, 'ram' => 0
            ];
            response($h);
        }
        if ($route === '/admin/ai/metrics' && $method === 'GET') {
            $m = proxyAiGet('/v1/metrics') ?: [
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
            $res = proxyAiGet('/v1/labels');
            $usedCodes = array_column(all($pdo, 'SELECT dish_code FROM dishes WHERE dish_code IS NOT NULL AND is_active=1'), 'dish_code');
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
            $h = (int)date('G');
            $rows = all($pdo, 'SELECT DATE_FORMAT(hit_at, "%H") hr, COUNT(*) c 
                FROM rate_limit_hits 
                WHERE bucket = "analyze" AND hit_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                GROUP BY DATE_FORMAT(hit_at, "%H")');
            $countsByHour = [];
            foreach ($rows as $r) {
                $countsByHour[(int)$r['hr']] = (int)$r['c'];
            }
            $bars = [];
            for ($i = 0; $i < 24; $i++) {
                $hr = ($h - 23 + $i + 24) % 24;
                $bars[] = ['label' => sprintf('%02dh', $hr), 'count' => $countsByHour[$hr] ?? 0];
            }
            response($bars);
        }
        if (handleAdminRoute($pdo, $uid, $route, $method)) exit;
    }
    fail(404,'ENDPOINT_NOT_FOUND','API không tồn tại hoặc phương thức không hỗ trợ.');
}

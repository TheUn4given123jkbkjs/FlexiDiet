/* ==========================================================================
   FlexiDiet Admin Studio - Schema & Config (admin-config.js)
   Định nghĩa cấu hình màn hình, thuộc tính bảng, schema CRUD & Mock Data
   ========================================================================== */

// Navigation Structure: Category, Label, Icon
const SC = {
  stats: ['Giám sát AI', 'Tổng quan hệ thống', 'gauge'],
  obs: ['Giám sát AI', 'Hiệu năng AI', 'heart-pulse'],
  learn: ['Giám sát AI', 'Phân tích nhận diện', 'brain'],
  feedback: ['Giám sát AI', 'Nhật ký phản hồi AI', 'camera-retro'],
  dishes: ['Danh mục chuẩn', 'Món ăn', 'bowl-food'],
  ingredients: ['Danh mục chuẩn', 'Nguyên liệu', 'carrot'],
  exercise: ['Danh mục chuẩn', 'Bài tập & MET', 'dumbbell'],
  users: ['Người dùng', 'Vòng đời & giữ chân', 'users'],
  reengage: ['Người dùng', 'Lịch sử nhắc nhở', 'envelope']
};

const N = (l, min, max) => [l, 'number', { min, max }];
const YN = [[1, 'Có'], [0, 'Không']];
const ACT = [['active', 'Đang dùng'], ['inactive', 'Ngưng dùng'], ['', 'Tất cả']];
const macro = v => (v.carb_g + v.protein_g + v.fat_g > 100) ? 'Protein + Carb + Fat không được vượt quá 100g/100g nguyên liệu.' : '';

// Resources schema & endpoints
const R = {
  dishes: {
    t: 'Món ăn', p: '/admin/dishes', ops: 'aet', fl: [['status', ACT]], kids: [['dish_ingredients', 'Công thức']],
    f: [['name', 'Tên món'], ['dish_code', 'Nhãn AI (dish_code)', 'label'], ['serving_label', 'Khẩu phần mặc định']],
    seed: [
      { id: 1, name: 'Phở bò', dish_code: 'pho_bo', serving_label: '1 tô (vừa)', is_active: 1 },
      { id: 2, name: 'Cơm tấm sườn', dish_code: 'com_tam_suon', serving_label: '1 đĩa', is_active: 1 },
      { id: 3, name: 'Bún bò Huế', dish_code: 'bun_bo_hue', serving_label: '1 tô', is_active: 1 },
      { id: 4, name: 'Bánh mì thịt', dish_code: 'banh_mi', serving_label: '1 ổ', is_active: 1 },
      { id: 5, name: 'Gỏi cuốn tôm thịt', dish_code: 'goi_cuon', serving_label: '3 cuốn', is_active: 1 }
    ],
    note: 'Nhãn AI chọn từ labels.json của mô hình YOLOv10 (67 nhãn món ăn Việt Nam). Mỗi nhãn chỉ gán cho một món để AI nhận diện chuẩn xác.'
  },
  ingredients: {
    t: 'Nguyên liệu', p: '/admin/ingredients', ops: 'aet', fl: [['status', ACT]], kids: [['aliases', 'Bí danh'], ['portions', 'Khẩu phần']], check: macro,
    f: [
      ['name', 'Tên nguyên liệu'],
      ['kcal_100g', ...N('Kcal /100g', 0, 900)],
      ['protein_g', ...N('Protein (g)', 0, 100)],
      ['carb_g', ...N('Carb (g)', 0, 100)],
      ['fat_g', ...N('Fat (g)', 0, 100)],
      ['state', 'Trạng thái', 'select', [['raw', 'Sống'], ['cooked', 'Chín'], ['na', 'Không áp dụng']]]
    ],
    seed: [
      { id: 1, name: 'Thịt bò phi lê', kcal_100g: 140, protein_g: 21, carb_g: 0, fat_g: 6, state: 'raw', is_active: 1 },
      { id: 2, name: 'Bánh phở tươi', kcal_100g: 110, protein_g: 1.8, carb_g: 25, fat_g: 0.2, state: 'cooked', is_active: 1 },
      { id: 3, name: 'Gạo tẻ trắng', kcal_100g: 130, protein_g: 2.7, carb_g: 28, fat_g: 0.3, state: 'cooked', is_active: 1 },
      { id: 4, name: 'Ức gà nạc', kcal_100g: 165, protein_g: 31, carb_g: 0, fat_g: 3.6, state: 'raw', is_active: 1 },
      { id: 5, name: 'Trứng gà', kcal_100g: 155, protein_g: 13, carb_g: 1.1, fat_g: 11, state: 'raw', is_active: 1 }
    ],
    note: 'Sửa nguyên liệu chỉ áp dụng cho lần tính toán sau; nhật ký bữa ăn cũ đã chốt số calo không bị đổi (FR-07.2). Khuyến nghị không xóa cứng, hãy ngưng dùng.'
  },
  exercise: {
    t: 'Bài tập & MET', p: '/admin/exercise-types', ops: 'aet', fl: [['status', ACT]],
    f: [
      ['code', 'Mã bài tập', 'text', { re: /^[a-z0-9_]+$/, h: 'chữ thường, số, _' }],
      ['name_vi', 'Tên bài tập'],
      ['uses_distance', 'Tính theo quãng đường', 'select', YN],
      ['met', 'MET mặc định', 'number', { min: 1, max: 30 }]
    ],
    seed: [
      { id: 1, code: 'running', name_vi: 'Chạy bộ', uses_distance: 1, met: 8.3, is_active: 1 },
      { id: 2, code: 'cycling', name_vi: 'Đạp xe', uses_distance: 1, met: 7.5, is_active: 1 },
      { id: 3, code: 'gym', name_vi: 'Tập Gym / Thể hình', uses_distance: 0, met: 5.0, is_active: 1 },
      { id: 4, code: 'yoga', name_vi: 'Yoga thư giãn', uses_distance: 0, met: 2.5, is_active: 1 },
      { id: 5, code: 'badminton', name_vi: 'Đánh cầu lông', uses_distance: 0, met: 6.0, is_active: 1 }
    ],
    note: 'Công thức năng lượng: Calo đốt cháy = MET × Cân nặng (kg) × Thời gian (giờ).'
  },
  dish_ingredients: {
    t: 'Công thức món', p: '/admin/dishes/:pid/ingredients', ops: 'aed',
    f: [['ingredient_id', 'Nguyên liệu', 'ref', 'ingredients'], ['grams', ...N('Định lượng (g)', 0.1, 2000)], ['sort_order', ...N('Thứ tự', 0, 99)]],
    seed: [
      { id: 1, pid: 1, ingredient_id: 2, ingredient_name: 'Bánh phở tươi', grams: 150, sort_order: 1 },
      { id: 2, pid: 1, ingredient_id: 1, ingredient_name: 'Thịt bò phi lê', grams: 70, sort_order: 2 }
    ]
  },
  aliases: {
    t: 'Bí danh tìm kiếm', p: '/admin/ingredients/:pid/aliases', item: id => '/admin/aliases/' + id, ops: 'ad',
    f: [['alias', 'Từ đồng nghĩa / Bí danh']],
    seed: [{ id: 1, pid: 1, alias: 'bò nạc' }, { id: 2, pid: 1, alias: 'beef' }]
  },
  portions: {
    t: 'Quy đổi khẩu phần', p: '/admin/ingredients/:pid/portions', item: id => '/admin/portions/' + id, ops: 'aed',
    f: [
      ['unit_name', 'Tên đơn vị (bát, miếng, lát...)'],
      ['size_label', 'Kích cỡ', 'select', [['small', 'Nhỏ (S)'], ['medium', 'Vừa (M)'], ['large', 'Lớn (L)']]],
      ['grams_per_unit', ...N('Gram quy đổi', 0.1, 5000)],
      ['note', 'Ghi chú', 'text', { opt: 1 }]
    ],
    seed: [
      { id: 1, pid: 1, unit_name: 'miếng', size_label: 'medium', grams_per_unit: 35, note: '1 miếng bò lát tiêu chuẩn' },
      { id: 2, pid: 2, unit_name: 'vắt', size_label: 'medium', grams_per_unit: 100, note: '1 vắt phở tươi' }
    ]
  },
  feedback: {
    t: 'Nhật ký phản hồi AI', p: '/admin/ai/feedback-logs', ops: '', qk: 'dish_code', ph: 'Lọc theo dish_code...', fl: [],
    f: [['thumb_url', 'Ảnh nhận diện', 'img'], ['ai_label', 'AI dự đoán'], ['user_label', 'Người dùng chọn'], ['confidence', 'Độ tin cậy (Confidence)']],
    seed: [
      { id: 1, thumb_url: 'pictures/brand.png', ai_label: 'bun_cha', user_label: 'bun_thit_nuong', confidence: '0.52' },
      { id: 2, thumb_url: 'pictures/brand.png', ai_label: 'pho_bo', user_label: 'hu_tieu', confidence: '0.48' }
    ],
    note: 'Lưu vết các trường hợp người dùng sửa kết quả AI để trích xuất tập dữ liệu gán nhãn cho đợt huấn luyện kế tiếp.'
  },
  reengage: {
    t: 'Lịch sử nhắc nhở người dùng', p: '/admin/users/re-engage-logs', ops: '',
    fl: [['status', [['', 'Mọi trạng thái'], ['queued', 'Đang chờ gửi'], ['sent', 'Đã gửi'], ['failed', 'Thất bại']]]],
    f: [['display_name', 'Người nhận'], ['email', 'Email'], ['inactive_days', 'Offline (ngày)'], ['message', 'Nội dung nhắc'], ['status', 'Trạng thái'], ['created_at', 'Thời gian gửi']],
    seed: [
      { id: 1, display_name: 'Lê Văn Cường', email: 'cuong@gmail.com', inactive_days: 9, message: 'Bạn ơi, hôm nay hãy ghi lại bữa ăn để không gián đoạn mục tiêu nhé!', status: 'sent', created_at: '2026-10-12 09:30' },
      { id: 2, display_name: 'Hoàng Anh', email: 'anh.h@gmail.com', inactive_days: 14, message: 'FlexiDiet vẫn ở đây đồng hành cùng bạn!', status: 'queued', created_at: '2026-10-13 08:00' }
    ],
    note: 'Nhật ký các thông báo tự động và chiến dịch gửi email kích hoạt lại người dùng có dấu hiệu rời bỏ.'
  },
  users: {
    t: 'Quản lý người dùng', p: '/admin/users', ops: 'sr', re: 1,
    fl: [
      ['status', [['', 'Mọi trạng thái'], ['active', 'Đang hoạt động'], ['banned', 'Đã bị khóa'], ['dormant', 'Nguy cơ rời bỏ']]],
      ['inactive_days', [['', 'Mọi thời gian offline'], ['7', 'Offline ≥ 7 ngày'], ['14', 'Offline ≥ 14 ngày'], ['30', 'Offline ≥ 30 ngày']]]
    ],
    f: [['display_name', 'Họ tên'], ['email', 'Email'], ['role', 'Vai trò'], ['status', 'Trạng thái'], ['inactive_days', 'Offline (ngày)']],
    seed: [
      { id: 1, display_name: 'System Admin', email: 'admin@flexidiet.vn', role: 'admin', status: 'active', inactive_days: 0 },
      { id: 2, display_name: 'Nguyễn Minh Anh', email: 'minhanh@gmail.com', role: 'member', status: 'active', inactive_days: 2 },
      { id: 3, display_name: 'Lê Văn Cường', email: 'cuong@gmail.com', role: 'member', status: 'dormant', inactive_days: 9 },
      { id: 4, display_name: 'Phạm Quỳnh Như', email: 'quynhnhu@gmail.com', role: 'member', status: 'banned', inactive_days: 35 }
    ],
    note: 'Chỉ hiển thị thông tin tài khoản và số ngày hoạt động. Quyền riêng tư: Quản trị viên không đọc nhật ký ăn uống cá nhân của thành viên.'
  }
};

// Rich Interactive Mock Data for Studio / Demo mode
const DEMO = {
  stats: {
    dau: 142, wau: 685, total_dishes: 67, total_ingredients: 446, total_meals: 5680,
    goal_rate: { within: 62, over: 24, under: 14 }
  },
  stats_periods: {
    '1w': {
      user_trends: [
        { label: 'Th 2', new_users: 2, active_users: 110 },
        { label: 'Th 3', new_users: 4, active_users: 118 },
        { label: 'Th 4', new_users: 3, active_users: 125 },
        { label: 'Th 5', new_users: 5, active_users: 132 },
        { label: 'Th 6', new_users: 4, active_users: 138 },
        { label: 'Th 7', new_users: 7, active_users: 145 },
        { label: 'CN', new_users: 8, active_users: 152 }
      ],
      meals: [
        { label: 'Th 2', meals: 85 }, { label: 'Th 3', meals: 92 },
        { label: 'Th 4', meals: 88 }, { label: 'Th 5', meals: 110 },
        { label: 'Th 6', meals: 125 }, { label: 'Th 7', meals: 140 },
        { label: 'CN', meals: 160 }
      ],
      intake_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 1620, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 2040, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 2480, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 2260, color: '#8b5cf6', icon: 'dumbbell' }
      ],
      burned_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 390, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 260, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 180, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 330, color: '#8b5cf6', icon: 'dumbbell' }
      ]
    },
    '1m': {
      user_trends: [
        { label: 'Tuần 1', new_users: 6, active_users: 280 },
        { label: 'Tuần 2', new_users: 9, active_users: 340 },
        { label: 'Tuần 3', new_users: 12, active_users: 410 },
        { label: 'Tuần 4', new_users: 15, active_users: 490 }
      ],
      meals: [
        { label: 'Tuần 1', meals: 520 }, { label: 'Tuần 2', meals: 650 },
        { label: 'Tuần 3', meals: 720 }, { label: 'Tuần 4', meals: 840 }
      ],
      intake_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 1640, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 2050, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 2490, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 2270, color: '#8b5cf6', icon: 'dumbbell' }
      ],
      burned_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 380, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 250, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 190, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 320, color: '#8b5cf6', icon: 'dumbbell' }
      ]
    },
    '6m': {
      user_trends: [
        { label: 'T5', new_users: 12, active_users: 180 },
        { label: 'T6', new_users: 18, active_users: 260 },
        { label: 'T7', new_users: 25, active_users: 380 },
        { label: 'T8', new_users: 34, active_users: 490 },
        { label: 'T9', new_users: 46, active_users: 610 },
        { label: 'T10', new_users: 65, active_users: 750 }
      ],
      meals: [
        { label: 'T5', meals: 1800 }, { label: 'T6', meals: 2400 },
        { label: 'T7', meals: 3100 }, { label: 'T8', meals: 3900 },
        { label: 'T9', meals: 4800 }, { label: 'T10', meals: 5680 }
      ],
      intake_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 1650, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 2060, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 2510, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 2290, color: '#8b5cf6', icon: 'dumbbell' }
      ],
      burned_by_goal: [
        { goal: 'lose', label: 'Giảm cân', kcal: 370, color: '#f97316', icon: 'arrow-trend-down' },
        { goal: 'maintain', label: 'Duy trì cân nặng', kcal: 245, color: '#10b981', icon: 'scale-balanced' },
        { goal: 'gain', label: 'Tăng cân', kcal: 185, color: '#3b82f6', icon: 'arrow-trend-up' },
        { goal: 'build_muscle', label: 'Tăng cơ giảm mỡ', kcal: 315, color: '#8b5cf6', icon: 'dumbbell' }
      ]
    }
  },
  obs: {
    t: [
      { label: '00h', count: 0 }, { label: '02h', count: 0 }, { label: '04h', count: 0 },
      { label: '06h', count: 5 }, { label: '07h', count: 12 }, { label: '08h', count: 18 },
      { label: '09h', count: 8 }, { label: '11h', count: 14 }, { label: '12h', count: 28 },
      { label: '13h', count: 19 }, { label: '15h', count: 9 }, { label: '17h', count: 16 },
      { label: '18h', count: 32 }, { label: '19h', count: 27 }, { label: '20h', count: 15 },
      { label: '22h', count: 4 }
    ],
    m: { volume_today: 1450, volume_week: 9280, volume_month: 38400, in_flight: 2, error_rate: 1.2, p50_ms: 280, p95_ms: 840 },
    h: { status: 'Sẵn sàng (Healthy)', uptime: '5 ngày 14 giờ', cpu: 32, ram: 58, model: 'YOLOv10m (67 món Việt)' }
  },
  learn: {
    top_dishes: [
      { dish_code: 'pho_bo', count: 320 }, { dish_code: 'com_tam_suon', count: 260 },
      { dish_code: 'bun_bo_hue', count: 185 }, { dish_code: 'banh_mi', count: 140 },
      { dish_code: 'goi_cuon', count: 98 }
    ],
    label_distribution: Array.from({ length: 67 }, (_, i) => ({
      dish_code: (i === 0 ? 'pho_bo' : i === 1 ? 'com_tam_suon' : i === 2 ? 'bun_bo_hue' : 'mon_' + (i + 1)),
      count: 10 + ((i * 37) % 120)
    })),
    acceptance_rate: 86.8,
    confusion_pairs: [
      { ai: 'bun_cha', user: 'bun_thit_nuong', count: 42 },
      { ai: 'pho_bo', user: 'hu_tieu', count: 28 },
      { ai: 'banh_cuon', user: 'banh_uot', count: 19 }
    ],
    low_confidence: [
      { dish_code: 'banh_bot_loc', avg_confidence: 0.35, count: 64 },
      { dish_code: 'bun_mam', avg_confidence: 0.41, count: 38 },
      { dish_code: 'canh_chua_ca', avg_confidence: 0.44, count: 29 }
    ]
  }
};

<?php
// Базовий URL проєкту
define('BASE_URL', '/skybooking/public');

// Конфігурація підключення до бази даних MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'skybooking_db');
define('DB_USER', 'root');  // Користувач MySQL за замовчуванням в XAMPP
define('DB_PASS', '');      // Пароль порожній за замовчуванням в XAMPP
define('DB_CHARSET', 'utf8mb4');

// Підключення до бази даних MySQL через PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Не показуємо деталі помилки користувачу
    error_log("Database connection error: " . $e->getMessage());
    die("Помилка підключення до бази даних. Спробуйте пізніше.");
}

// Налаштування сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функція для перевірки авторизації
function isLoggedIn() {
    return isset($_SESSION['customer_id']);
}

// Функція для перевірки авторизації та перенаправлення
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Функція для виходу
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ============================================
// ФУНКЦІЇ БЕЗПЕКИ ТА ВАЛІДАЦІЇ
// ============================================

/**
 * Очищення та валідація рядкового введення
 */
function sanitizeString($input) {
    if ($input === null) return '';
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Валідація email
 */
function validateEmail($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Валідація цілого числа
 */
function validateInt($value, $min = null, $max = null) {
    if (!is_numeric($value)) return false;
    $value = intval($value);
    if ($min !== null && $value < $min) return false;
    if ($max !== null && $value > $max) return false;
    return $value;
}

/**
 * Валідація дати
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Перевірка на порожнє значення (включаючи пробіли)
 */
function isEmpty($value) {
    return empty(trim($value));
}

/**
 * Валідація телефону (український формат)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    // +380XXXXXXXXX або 0XXXXXXXXX
    return preg_match('/^(\+380|380|0)[0-9]{9}$/', $phone);
}

/**
 * Валідація номера паспорта
 */
function validatePassport($passport) {
    $passport = trim($passport);
    // Дозволяємо різні формати: AA123456, АА123456, 123456789
    return preg_match('/^[A-Za-zА-Яа-яІіЇї]{0,2}[0-9]{6,9}$/', $passport);
}

/**
 * Захист від CSRF - генерація токену
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Захист від CSRF - перевірка токену
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Безпечне отримання POST параметра
 */
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Безпечне отримання GET параметра
 */
function getGet($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * Валідація пароля
 */
function validatePassword($password) {
    // Мінімум 6 символів
    return strlen($password) >= 6;
}

/**
 * Захист від SQL ін'єкцій - екранування для LIKE
 */
function escapeLike($string) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
}

/**
 * Логування помилок безпеки
 */
function logSecurityEvent($message) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logMessage = "[$timestamp] IP: $ip - $message\n";
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Перевірка прав доступу до бронювання
 */
function checkBookingOwnership($pdo, $booking_id, $customer_id) {
    $stmt = $pdo->prepare("SELECT customer_id FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking || $booking['customer_id'] != $customer_id) {
        logSecurityEvent("Unauthorized access attempt to booking #$booking_id by customer #$customer_id");
        return false;
    }
    
    return true;
}

/**
 * Rate limiting - обмеження кількості запитів
 */
function checkRateLimit($action, $limit = 5, $period = 60) {
    $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $data = $_SESSION[$key];
    $current_time = time();
    
    // Скинути якщо минув період
    if ($current_time - $data['start_time'] > $period) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => $current_time];
        return true;
    }
    
    // Перевірити ліміт
    if ($data['count'] >= $limit) {
        logSecurityEvent("Rate limit exceeded for action: $action");
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Валідація масиву ID
 */
function validateIdArray($array, $min = 1, $max = 100) {
    if (!is_array($array)) return false;
    if (count($array) < $min || count($array) > $max) return false;
    
    foreach ($array as $id) {
        if (!is_numeric($id) || intval($id) <= 0) {
            return false;
        }
    }
    
    return true;
}

/**
 * Перевірка чи користувач є адміністратором
 * Дозволяємо доступ якщо:
 * - в сесії `customer_email` === 'admin@admin.com'
 * - або в таблиці customers є колонка `is_admin` = 1 для поточного user
 */
function isAdmin() {
    if (isset($_SESSION['customer_email']) && $_SESSION['customer_email'] === 'admin@admin.com') {
        return true;
    }

    if (!isset($_SESSION['customer_id'])) {
        return false;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT is_admin FROM customers WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['customer_id']]);
        $row = $stmt->fetch();
        if ($row && isset($row['is_admin']) && intval($row['is_admin']) === 1) {
            return true;
        }
    } catch (Exception $e) {
        // не фатальна помилка
    }

    return false;
}
?>

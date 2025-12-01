<?php 
require_once '../includes/config.php';

if (isLoggedIn()) {
    header('Location: /public/index.php');
    exit;
}

$page_title = 'Реєстрація - SkyBooking';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка rate limiting
    if (!checkRateLimit('register', 3, 300)) {
        $error = 'Занадто багато спроб реєстрації. Спробуйте через 5 хвилин.';
    } else {
        // Отримання та очищення даних
        $first_name = sanitizeString(getPost('first_name'));
        $last_name = sanitizeString(getPost('last_name'));
        $email = trim(getPost('email'));
        $phone = sanitizeString(getPost('phone'));
        $date_of_birth = getPost('date_of_birth');
        $nationality = sanitizeString(getPost('nationality'));
        $password = getPost('password');
        $confirm_password = getPost('confirm_password');
        
        // Валідація на стороні сервера
        if (isEmpty($first_name) || isEmpty($last_name) || isEmpty($email) || isEmpty($password)) {
            $error = 'Будь ласка, заповніть всі обов\'язкові поля.';
        } elseif (!validateEmail($email)) {
            $error = 'Невірний формат електронної пошти.';
            logSecurityEvent("Invalid email format attempt: $email");
        } elseif (!validatePassword($password)) {
            $error = 'Пароль повинен містити мінімум 6 символів.';
        } elseif ($password !== $confirm_password) {
            $error = 'Паролі не співпадають.';
        } elseif (!empty($phone) && !validatePhone($phone)) {
            $error = 'Невірний формат телефону. Використовуйте +380XXXXXXXXX';
        } elseif (!empty($date_of_birth) && !validateDate($date_of_birth)) {
            $error = 'Невірний формат дати народження.';
        } else {
            // Перевірка, чи існує користувач з такою поштою (параметризований запит)
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Користувач з такою поштою вже зареєстрований.';
                logSecurityEvent("Registration attempt with existing email: $email");
            } else {
                // Реєстрація користувача з безпечним хешуванням
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    // Додаємо колонку password_hash, якщо її немає
                    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255)");
                    
                    // Параметризований запит для захисту від SQL-ін'єкцій
                    $stmt = $pdo->prepare("
                        INSERT INTO customers (first_name, last_name, email, phone, date_of_birth, nationality, password_hash)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $first_name,
                        $last_name,
                        $email,
                        !empty($phone) ? $phone : null,
                        !empty($date_of_birth) ? $date_of_birth : null,
                        !empty($nationality) ? $nationality : null,
                        $password_hash
                    ]);
                    
                    $success = 'Реєстрація успішна! Тепер ви можете увійти.';
                    
                    // Автоматичний вхід з безпечним зберіганням в сесії
                    $customer_id = $pdo->lastInsertId();
                    $_SESSION['customer_id'] = $customer_id;
                    $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['customer_email'] = $email;
                    
                    // Регенерація ID сесії для захисту
                    session_regenerate_id(true);
                    
                    logSecurityEvent("New user registered: customer_id=$customer_id");
                    
                    // Безпечне перенаправлення
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: $redirect");
                    } else {
                        header('Location: /public/index.php');
                    }
                    exit;
                } catch (PDOException $e) {
                    logSecurityEvent("Registration error: " . $e->getMessage());
                    $error = 'Помилка реєстрації. Спробуйте ще раз.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Реєстрація</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <div class="form-group">
                        <label for="first_name">Ім'я: *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               required 
                               minlength="2"
                               maxlength="50"
                               pattern="[A-Za-zА-Яа-яІіЇїЄєҐґ\s\-']+"
                               title="Тільки літери, пробіли, дефіси та апострофи"
                               oninput="this.setCustomValidity(''); checkFormValidity('registerForm')">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Прізвище: *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               required
                               minlength="2"
                               maxlength="50"
                               pattern="[A-Za-zА-Яа-яІіЇїЄєҐґ\s\-']+"
                               title="Тільки літери, пробіли, дефіси та апострофи"
                               oninput="this.setCustomValidity(''); checkFormValidity('registerForm')">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Електронна пошта: *</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required
                           maxlength="100"
                           oninput="this.setCustomValidity(''); checkFormValidity('registerForm')">
                </div>

                <div class="form-group">
                    <label for="phone">Телефон:</label>
                    <input type="tel" name="phone" id="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="+380XXXXXXXXX"
                           pattern="(\+380|380|0)[0-9]{9}"
                           maxlength="13"
                           title="Формат: +380XXXXXXXXX або 0XXXXXXXXX"
                           oninput="this.setCustomValidity('')">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Дата народження:</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                               max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="nationality">Громадянство:</label>
                        <input type="text" name="nationality" id="nationality" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['nationality'] ?? ''); ?>"
                               placeholder="Україна">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Пароль: *</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               required
                               minlength="6" 
                               maxlength="255"
                               oninput="validatePasswordMatch(); checkFormValidity('registerForm')">
                        <small style="color: var(--gray-text);">Мінімум 6 символів</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Підтвердження пароля: *</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               class="form-control" 
                               required
                               minlength="6"
                               maxlength="255"
                               oninput="validatePasswordMatch(); checkFormValidity('registerForm')">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="submitBtn" disabled>Зареєструватись</button>
            </form>
            
            <noscript>
                <style>
                    #submitBtn { display: none !important; }
                    #submitBtn + .btn-fallback { display: inline-block !important; }
                </style>
                <button type="submit" form="registerForm" class="btn btn-primary btn-full btn-fallback" style="display: none;">
                    Зареєструватись
                </button>
            </noscript>
            
            <p style="text-align: center; margin-top: 1.5rem; color: var(--gray-text);">
                Вже маєте акаунт? <a href="/public/login.php" style="color: var(--primary-color); font-weight: 600;">Увійти</a>
            </p>
        </div>
    </section>
</div>

<style>
/* Валідація через CSS */
.form-control:invalid:not(:placeholder-shown) {
    border-color: var(--danger-color);
}

.form-control:valid:not(:placeholder-shown) {
    border-color: var(--success-color);
}

/* Приховування повідомлень браузера */
.form-control:invalid {
    box-shadow: none;
}
</style>

<script>
// Валідація на стороні клієнта
function validatePasswordMatch() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (confirmPassword.value && password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Паролі не співпадають');
    } else {
        confirmPassword.setCustomValidity('');
    }
}

function checkFormValidity(formId) {
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('submitBtn');
    
    if (!form || !submitBtn) return;
    
    // Перевірка всіх полів
    const inputs = form.querySelectorAll('input[required]');
    let allValid = true;
    
    inputs.forEach(input => {
        const value = input.value.trim();
        
        // Перевірка на порожнє поле або тільки пробіли
        if (!value || value.length === 0) {
            allValid = false;
            return;
        }
        
        // Перевірка HTML5 валідації
        if (!input.validity.valid) {
            allValid = false;
            return;
        }
    });
    
    submitBtn.disabled = !allValid;
}

// Ініціалізація при завантаженні
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    
    if (form) {
        // Перевірка валідності при відправці
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required]');
            let hasErrors = false;
            
            inputs.forEach(input => {
                const value = input.value.trim();
                
                // Блокувати відправку якщо є тільки пробіли
                if (!value || value.length === 0) {
                    input.setCustomValidity('Це поле обов\'язкове');
                    hasErrors = true;
                } else {
                    input.setCustomValidity('');
                }
            });
            
            if (hasErrors || !form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
        });
        
        // Початкова перевірка
        checkFormValidity('registerForm');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

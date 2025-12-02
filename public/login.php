<?php 
require_once '../includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Вхід - SkyBooking';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка rate limiting - захист від brute force
    if (!checkRateLimit('login', 5, 300)) {
        $error = 'Занадто багато спроб входу. Спробуйте через 5 хвилин.';
        logSecurityEvent("Rate limit exceeded for login from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        // Безпечне отримання даних
        $email = trim(getPost('email'));
        $password = getPost('password');
        
        // Валідація на стороні сервера
        if (isEmpty($email) || isEmpty($password)) {
            $error = 'Будь ласка, заповніть всі поля.';
        } elseif (!validateEmail($email)) {
            $error = 'Невірний формат електронної пошти.';
            logSecurityEvent("Invalid email format in login: $email");
        } else {
            // Перевірка колонки password_hash
            try {
                $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255)");
            } catch (PDOException $e) {
                // Колонка вже існує
            }
            
            // Параметризований запит для захисту від SQL-ін'єкцій
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $customer = $stmt->fetch();
            
            if ($customer && isset($customer['password_hash']) && password_verify($password, $customer['password_hash'])) {
                // Успішний вхід
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
                $_SESSION['customer_email'] = $customer['email'];
                
                // Регенерація ID сесії для захисту від session fixation
                session_regenerate_id(true);
                
                logSecurityEvent("Successful login for customer_id: " . $customer['customer_id']);
                
                // Безпечне перенаправлення
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                } else {
                    header('Location: ' . BASE_URL . '/index.php');
                }
                exit;
            } else {
                $error = 'Невірна пошта або пароль.';
                logSecurityEvent("Failed login attempt for email: $email");
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Вхід</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="email">Електронна пошта:</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required
                           maxlength="100"
                           oninput="this.setCustomValidity(''); checkLoginFormValidity()">
                </div>

                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" name="password" id="password" class="form-control" 
                           required
                           minlength="6"
                           maxlength="255"
                           oninput="this.setCustomValidity(''); checkLoginFormValidity()">
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="loginBtn" disabled>Увійти</button>
            </form>
            
            <noscript>
                <style>
                    #loginBtn { display: none !important; }
                </style>
                <button type="submit" form="loginForm" class="btn btn-primary btn-full">Увійти</button>
            </noscript>
            
            <p style="text-align: center; margin-top: 1.5rem; color: var(--gray-text);">
                Немає акаунта? <a href="<?php echo BASE_URL; ?>/register.php" style="color: var(--primary-color); font-weight: 600;">Зареєструватись</a>
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
</style>

<script>
function checkLoginFormValidity() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginBtn');
    
    if (!form || !submitBtn) return;
    
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    
    // Перевірка на порожні поля або тільки пробіли
    const emailValid = email.value.trim().length > 0 && email.validity.valid;
    const passwordValid = password.value.trim().length >= 6;
    
    submitBtn.disabled = !(emailValid && passwordValid);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required]');
            let hasErrors = false;
            
            inputs.forEach(input => {
                const value = input.value.trim();
                
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
        
        checkLoginFormValidity();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

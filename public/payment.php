<?php 
require_once '../includes/config.php';
requireLogin();

if (!isset($_SESSION['current_booking_id'])) {
    header('Location: /public/index.php');
    exit;
}

$page_title = 'Оплата - SkyBooking';
$booking_id = $_SESSION['current_booking_id'];

// Отримуємо інформацію про бронювання
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        f.flight_number,
        f.departure_time,
        a.name as airline_name,
        a.iata_code as airline_code,
        da.city as departure_city,
        aa.city as arrival_city
    FROM bookings b
    JOIN tickets t ON b.booking_id = t.booking_id
    JOIN flights f ON t.flight_id = f.flight_id
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE b.booking_id = ? AND b.customer_id = ?
    LIMIT 1
");
$stmt->execute([$booking_id, $_SESSION['customer_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: /public/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Перевірка CSRF токену
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Невалідний запит. Спробуйте ще раз.';
        logSecurityEvent('csrf_token_invalid', $_SESSION['customer_id']);
    }
    
    // Валідація прихованого поля booking_id (захист від IDOR)
    $posted_booking_id = intval($_POST['booking_id'] ?? 0);
    if ($posted_booking_id !== $booking_id) {
        $error = 'Некоректний ID бронювання.';
        logSecurityEvent('payment_booking_id_mismatch', $_SESSION['customer_id']);
    }
    
    $payment_method = sanitizeString($_POST['payment_method'] ?? '');
    $card_number = sanitizeString($_POST['card_number'] ?? '');
    $card_holder = sanitizeString($_POST['card_holder'] ?? '');
    $expiry = sanitizeString($_POST['expiry'] ?? '');
    $cvv = sanitizeString($_POST['cvv'] ?? '');
    
    if (empty($payment_method)) {
        $error = 'Оберіть спосіб оплати.';
        logSecurityEvent('payment_no_method', $_SESSION['customer_id']);
    } elseif ($payment_method === 'card') {
        // Валідація даних картки
        if (empty($card_number) || empty($card_holder) || empty($expiry) || empty($cvv)) {
            $error = 'Заповніть всі дані картки.';
            logSecurityEvent('payment_incomplete_card', $_SESSION['customer_id']);
        } elseif (!preg_match('/^[0-9\s]{13,19}$/', $card_number)) {
            $error = 'Некоректний номер картки.';
            logSecurityEvent('payment_invalid_card_number', $_SESSION['customer_id']);
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry)) {
            $error = 'Некоректний термін дії картки.';
            logSecurityEvent('payment_invalid_expiry', $_SESSION['customer_id']);
        } elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $error = 'Некоректний CVV.';
            logSecurityEvent('payment_invalid_cvv', $_SESSION['customer_id']);
        }
    }
    
    if (!$error) {
        try {
            $pdo->beginTransaction();
            
            // Генеруємо ID транзакції
            $transaction_id = 'TXN-' . strtoupper(bin2hex(random_bytes(8)));
            
            // Створюємо платіж
            $stmt = $pdo->prepare("
                INSERT INTO payments (booking_id, payment_date, amount, payment_method, transaction_id, payment_status, currency)
                VALUES (?, NOW(), ?, ?, ?, 'success', 'UAH')
            ");
            $stmt->execute([
                $booking_id,
                $booking['total_amount'],
                $payment_method,
                $transaction_id
            ]);
            
            // Оновлюємо статус бронювання
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'confirmed', payment_status = 'paid'
                WHERE booking_id = ?
            ");
            $stmt->execute([$booking_id]);
            
            $pdo->commit();
            
            // Переходимо до квитків
            header('Location: /public/ticket.php?booking_id=' . $booking_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Помилка обробки платежу. Спробуйте ще раз.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Оплата бронювання</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="booking-summary">
            <h2 style="margin-bottom: 1.5rem; color: var(--dark-text);">Деталі замовлення</h2>
            
            <div class="summary-row">
                <span>Номер бронювання:</span>
                <strong>#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Рейс:</span>
                <strong>
                    <?php echo htmlspecialchars($booking['airline_name']); ?> 
                    <?php echo htmlspecialchars($booking['airline_code'] . ' ' . $booking['flight_number']); ?>
                </strong>
            </div>
            
            <div class="summary-row">
                <span>Маршрут:</span>
                <strong>
                    <?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?>
                </strong>
            </div>
            
            <div class="summary-row">
                <span>До сплати:</span>
                <strong><?php echo number_format($booking['total_amount'], 2); ?> ₴</strong>
            </div>
        </div>

        <div class="form-container" style="margin-top: 2rem;">
            <form method="POST" action="" id="paymentForm" novalidate>
                <!-- Приховане поле для захисту від IDOR -->
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <h3 style="margin-bottom: 1.5rem;">Спосіб оплати</h3>
                
                <div class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="card" id="paymentCard" required checked onchange="toggleCardDetails(); checkPaymentFormValidity();">
                        💳 Банківська картка
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="paypal" id="paymentPaypal" required onchange="toggleCardDetails(); checkPaymentFormValidity();">
                        💰 PayPal
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="bank_transfer" id="paymentTransfer" required onchange="toggleCardDetails(); checkPaymentFormValidity();">
                        🏦 Банківський переказ
                    </label>
                </div>

                <div id="cardDetails" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                    <h3 style="margin-bottom: 1.5rem;">Дані картки</h3>
                    
                    <div class="form-group">
                        <label for="card_number">Номер картки:</label>
                        <input type="text" name="card_number" id="card_number" 
                               class="form-control card-field" 
                               placeholder="1234 5678 9012 3456"
                               minlength="13"
                               maxlength="19"
                               pattern="[0-9\s]{13,19}"
                               oninput="formatCardNumber(this); checkPaymentFormValidity();">
                    </div>

                    <div class="form-group">
                        <label for="card_holder">Власник картки:</label>
                        <input type="text" name="card_holder" id="card_holder" 
                               class="form-control card-field" 
                               placeholder="TARAS SHEVCHENKO"
                               minlength="3"
                               maxlength="50"
                               pattern="[A-Za-z\s]+"
                               oninput="this.value = this.value.toUpperCase(); checkPaymentFormValidity();">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry">Термін дії (MM/YY):</label>
                            <input type="text" name="expiry" id="expiry" 
                                   class="form-control card-field" 
                                   placeholder="12/25"
                                   maxlength="5"
                                   pattern="(0[1-9]|1[0-2])\/[0-9]{2}"
                                   oninput="formatExpiry(this); checkPaymentFormValidity();">
                        </div>

                        <div class="form-group">
                            <label for="cvv">CVV:</label>
                            <input type="text" name="cvv" id="cvv" 
                                   class="form-control card-field" 
                                   placeholder="123"
                                   minlength="3"
                                   maxlength="4"
                                   pattern="[0-9]{3,4}"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, ''); checkPaymentFormValidity();">
                        </div>
                    </div>
                </div>

                <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; border-left: 4px solid var(--warning-color);">
                    <p style="margin: 0; color: #92400e;">
                        🔒 Ваші платіжні дані захищені. Ми використовуємо безпечне з'єднання.
                    </p>
                </div>

                <button type="submit" name="process_payment" class="btn btn-success btn-full" id="paymentBtn" disabled>
                    Оплатити <?php echo number_format($booking['total_amount'], 2); ?> ₴
                </button>
                
                <noscript>
                    <style>
                        #paymentBtn { display: none !important; }
                    </style>
                    <button type="submit" name="process_payment" form="paymentForm" class="btn btn-success btn-full">
                        Оплатити <?php echo number_format($booking['total_amount'], 2); ?> ₴
                    </button>
                </noscript>
            </form>
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
function formatCardNumber(input) {
    // Видаляємо все крім цифр
    let value = input.value.replace(/\D/g, '');
    // Додаємо пробіли кожні 4 цифри
    value = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = value;
}

function formatExpiry(input) {
    // Видаляємо все крім цифр
    let value = input.value.replace(/\D/g, '');
    // Додаємо / після 2 цифр
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    input.value = value;
}

function toggleCardDetails() {
    const cardDetails = document.getElementById('cardDetails');
    const paymentCard = document.getElementById('paymentCard');
    const cardFields = document.querySelectorAll('.card-field');
    
    if (paymentCard && paymentCard.checked) {
        cardDetails.style.display = 'block';
        cardFields.forEach(field => field.required = true);
    } else {
        cardDetails.style.display = 'none';
        cardFields.forEach(field => field.required = false);
    }
}

function checkPaymentFormValidity() {
    const form = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('paymentBtn');
    
    if (!form || !submitBtn) return;
    
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const paymentCard = document.getElementById('paymentCard');
    
    if (!paymentMethod) {
        submitBtn.disabled = true;
        return;
    }
    
    // Якщо обрано картку, перевіряємо дані картки
    if (paymentCard && paymentCard.checked) {
        const cardNumber = document.getElementById('card_number');
        const cardHolder = document.getElementById('card_holder');
        const expiry = document.getElementById('expiry');
        const cvv = document.getElementById('cvv');
        
        const cardNumberValid = cardNumber.value.replace(/\s/g, '').length >= 13 && cardNumber.validity.valid;
        const cardHolderValid = cardHolder.value.trim().length >= 3 && cardHolder.validity.valid;
        const expiryValid = expiry.value.length === 5 && expiry.validity.valid;
        const cvvValid = cvv.value.length >= 3 && cvv.validity.valid;
        
        submitBtn.disabled = !(cardNumberValid && cardHolderValid && expiryValid && cvvValid);
    } else {
        // Для інших методів просто перевіряємо, що обрано метод
        submitBtn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paymentForm');
    
    if (form) {
        toggleCardDetails();
        
        form.addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Оберіть спосіб оплати');
                return false;
            }
            
            if (paymentMethod.value === 'card') {
                const cardFields = form.querySelectorAll('.card-field');
                let hasErrors = false;
                
                cardFields.forEach(field => {
                    const value = field.value.trim();
                    
                    if (!value || value.length === 0 || !field.validity.valid) {
                        field.setCustomValidity('Заповніть це поле коректно');
                        hasErrors = true;
                    } else {
                        field.setCustomValidity('');
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    form.reportValidity();
                    return false;
                }
            }
            
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
        });
        
        checkPaymentFormValidity();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

<?php 
require_once '../includes/config.php';
requireLogin();

if (!isset($_SESSION['selected_flight_id']) || !isset($_SESSION['selected_seats']) || 
    !isset($_SESSION['passengers_data']) || !isset($_SESSION['search'])) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

$page_title = 'Підтвердження бронювання - SkyBooking';
$flight_id = $_SESSION['selected_flight_id'];
$selected_seats = $_SESSION['selected_seats'];
$passengers_data = $_SESSION['passengers_data'];
$passengers_count = count($selected_seats);

// Отримуємо інформацію про рейс
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        a.name as airline_name,
        a.iata_code as airline_code,
        da.city as departure_city,
        da.name as departure_airport,
        da.iata_code as departure_code,
        aa.city as arrival_city,
        aa.name as arrival_airport,
        aa.iata_code as arrival_code
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE f.flight_id = ?
");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

// Розрахунок вартості
$base_price = $flight['base_price'];
$total_price = $base_price * $passengers_count;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    // Перевірка CSRF токену
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Невалідний запит. Спробуйте ще раз.';
        logSecurityEvent('csrf_token_invalid', $_SESSION['customer_id']);
    }
    
    // Валідація підтвердження
    if (!$error && !isset($_POST['terms_accepted'])) {
        $error = 'Підтвердіть згоду з умовами бронювання.';
        logSecurityEvent('booking_no_terms_accepted', $_SESSION['customer_id']);
    }
    
    if (!$error) {
        try {
            $pdo->beginTransaction();
        
        // Створюємо бронювання
        $stmt = $pdo->prepare("
            INSERT INTO bookings (customer_id, booking_date, status, total_amount, payment_status, currency)
            VALUES (?, NOW(), 'pending', ?, 'unpaid', 'UAH')
        ");
        $stmt->execute([$_SESSION['customer_id'], $total_price]);
        $booking_id = $pdo->lastInsertId();
        
        // Додаємо або отримуємо пасажирів
        $passenger_ids = [];
        foreach ($passengers_data as $passenger) {
            // Перевіряємо, чи існує пасажир
            $stmt = $pdo->prepare("
                SELECT passenger_id FROM passengers 
                WHERE customer_id = ? AND passport_number = ?
            ");
            $stmt->execute([$_SESSION['customer_id'], $passenger['passport_number']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $passenger_ids[] = $existing['passenger_id'];
            } else {
                // Додаємо нового пасажира
                $stmt = $pdo->prepare("
                    INSERT INTO passengers (customer_id, first_name, last_name, date_of_birth, passport_number, nationality)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['customer_id'],
                    $passenger['first_name'],
                    $passenger['last_name'],
                    $passenger['date_of_birth'],
                    $passenger['passport_number'],
                    $passenger['nationality']
                ]);
                $passenger_ids[] = $pdo->lastInsertId();
            }
        }
        
        // Створюємо квитки
        foreach ($selected_seats as $index => $seat) {
            // Визначаємо клас
            $seat_row = intval($seat);
            $travel_class = ($seat_row <= 3) ? 'business' : 'economy';
            $ticket_price = ($travel_class === 'business') ? $base_price * 1.5 : $base_price;
            
            $stmt = $pdo->prepare("
                INSERT INTO tickets (booking_id, flight_id, passenger_id, seat_number, travel_class, ticket_price, ticket_status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $booking_id,
                $flight_id,
                $passenger_ids[$index],
                $seat,
                $travel_class,
                $ticket_price
            ]);
        }
        
        // Оновлюємо загальну суму з урахуванням класу
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET total_amount = (SELECT SUM(ticket_price) FROM tickets WHERE booking_id = ?)
            WHERE booking_id = ?
        ");
        $stmt->execute([$booking_id, $booking_id]);
        
        $pdo->commit();
        
        // Зберігаємо ID бронювання та переходимо до оплати
        $_SESSION['current_booking_id'] = $booking_id;
        
        // Очищаємо дані сесії
        unset($_SESSION['selected_flight_id']);
        unset($_SESSION['selected_seats']);
        unset($_SESSION['passengers_data']);
        unset($_SESSION['search']);
        
        header('Location: ' . BASE_URL . '/payment.php');
        exit;
        
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Помилка створення бронювання: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Підтвердження бронювання</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="booking-summary">
            <h2 style="margin-bottom: 1.5rem; color: var(--dark-text);">Деталі рейсу</h2>
            
            <div class="summary-row">
                <span>Авіакомпанія:</span>
                <strong><?php echo htmlspecialchars($flight['airline_name']); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Рейс:</span>
                <strong><?php echo htmlspecialchars($flight['airline_code'] . ' ' . $flight['flight_number']); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Відправлення:</span>
                <strong>
                    <?php echo htmlspecialchars($flight['departure_city'] . ' (' . $flight['departure_code'] . ')'); ?>
                    <br>
                    <small style="color: var(--gray-text);">
                        <?php echo date('d.m.Y H:i', strtotime($flight['departure_time'])); ?>
                    </small>
                </strong>
            </div>
            
            <div class="summary-row">
                <span>Прибуття:</span>
                <strong>
                    <?php echo htmlspecialchars($flight['arrival_city'] . ' (' . $flight['arrival_code'] . ')'); ?>
                    <br>
                    <small style="color: var(--gray-text);">
                        <?php echo date('d.m.Y H:i', strtotime($flight['arrival_time'])); ?>
                    </small>
                </strong>
            </div>
            
            <div class="summary-row">
                <span>Місця:</span>
                <strong><?php echo implode(', ', $selected_seats); ?></strong>
            </div>
        </div>

        <div class="booking-summary" style="margin-top: 2rem;">
            <h2 style="margin-bottom: 1.5rem; color: var(--dark-text);">Пасажири</h2>
            
            <?php foreach ($passengers_data as $index => $passenger): ?>
                <div class="summary-row">
                    <span>Пасажир <?php echo $index + 1; ?> (Місце <?php echo $selected_seats[$index]; ?>):</span>
                    <strong>
                        <?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?>
                        <br>
                        <small style="color: var(--gray-text);">
                            Паспорт: <?php echo htmlspecialchars($passenger['passport_number']); ?>
                        </small>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="booking-summary" style="margin-top: 2rem;">
            <h2 style="margin-bottom: 1.5rem; color: var(--dark-text);">Вартість</h2>
            
            <div class="summary-row">
                <span>Базова ціна квитка:</span>
                <strong><?php echo number_format($base_price, 2); ?> ₴</strong>
            </div>
            
            <div class="summary-row">
                <span>Кількість пасажирів:</span>
                <strong><?php echo $passengers_count; ?></strong>
            </div>
            
            <div class="summary-row">
                <span>До сплати:</span>
                <strong><?php echo number_format($total_price, 2); ?> ₴</strong>
            </div>
        </div>

        <form method="POST" action="" id="bookingForm" novalidate style="margin-top: 2rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="terms_accepted" id="termsAccepted" required style="margin-right: 0.5rem;" oninput="checkBookingFormValidity()">
                    <span>Я підтверджую, що ознайомився з умовами бронювання та даними пасажирів</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="<?php echo BASE_URL; ?>/passengers.php" class="btn btn-secondary">Назад</a>
                <button type="submit" name="confirm_booking" class="btn btn-success" id="bookingBtn" disabled>
                    Підтвердити бронювання
                </button>
            </div>
            
            <noscript>
                <style>
                    #bookingBtn { display: none !important; }
                </style>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                    <button type="submit" name="confirm_booking" form="bookingForm" class="btn btn-success">
                        Підтвердити бронювання
                    </button>
                </div>
            </noscript>
        </form>
    </section>
</div>

<script>
function checkBookingFormValidity() {
    const form = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('bookingBtn');
    
    if (!form || !submitBtn) return;
    
    const termsAccepted = document.getElementById('termsAccepted');
    
    submitBtn.disabled = !termsAccepted.checked;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const termsAccepted = document.getElementById('termsAccepted');
            
            if (!termsAccepted.checked) {
                e.preventDefault();
                termsAccepted.setCustomValidity('Підтвердіть згоду з умовами');
                form.reportValidity();
                return false;
            } else {
                termsAccepted.setCustomValidity('');
            }
            
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
        });
        
        checkBookingFormValidity();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

<?php 
require_once '../includes/config.php';
requireLogin();

if (!isset($_SESSION['selected_flight_id']) || !isset($_SESSION['selected_seats']) || !isset($_SESSION['search'])) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

$page_title = 'Інформація про пасажирів - SkyBooking';
$flight_id = $_SESSION['selected_flight_id'];
$selected_seats = $_SESSION['selected_seats'];
$passengers_count = count($selected_seats);

// Отримуємо інформацію про рейс
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        a.name as airline_name,
        da.city as departure_city,
        aa.city as arrival_city
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE f.flight_id = ?
");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

// Отримуємо існуючих пасажирів користувача
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE customer_id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$existing_passengers = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка CSRF токену
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Невалідний запит. Спробуйте ще раз.';
        logSecurityEvent('csrf_token_invalid', $_SESSION['customer_id']);
    }
    
    $passengers_data = [];
    
    for ($i = 0; $i < $passengers_count; $i++) {
        $first_name = sanitizeString($_POST["first_name_$i"] ?? '');
        $last_name = sanitizeString($_POST["last_name_$i"] ?? '');
        $date_of_birth = $_POST["date_of_birth_$i"] ?? '';
        $passport_number = sanitizeString($_POST["passport_number_$i"] ?? '');
        $nationality = sanitizeString($_POST["nationality_$i"] ?? '');
        
        // Валідація на порожні значення
        if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($passport_number)) {
            $error = "Будь ласка, заповніть всі обов'язкові поля для всіх пасажирів.";
            logSecurityEvent('invalid_passenger_data', $_SESSION['customer_id']);
            break;
        }
        
        // Валідація дати народження
        if (!validateDate($date_of_birth)) {
            $error = "Некоректна дата народження для пасажира " . ($i + 1) . ".";
            logSecurityEvent('invalid_passenger_date', $_SESSION['customer_id']);
            break;
        }
        
        // Валідація паспорту
        if (!validatePassport($passport_number)) {
            $error = "Некоректний номер паспорту для пасажира " . ($i + 1) . ".";
            logSecurityEvent('invalid_passport', $_SESSION['customer_id']);
            break;
        }
        
        $passengers_data[] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'date_of_birth' => $date_of_birth,
            'passport_number' => $passport_number,
            'nationality' => $nationality
        ];
    }
    
    if (!$error) {
        // Зберігаємо дані пасажирів у сесію
        $_SESSION['passengers_data'] = $passengers_data;
        
        // Переходимо до бронювання
        header('Location: ' . BASE_URL . '/booking.php');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Інформація про пасажирів</h1>
        
        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <p style="font-size: 1.1rem;">
                <strong><?php echo htmlspecialchars($flight['departure_city']); ?></strong> 
                → 
                <strong><?php echo htmlspecialchars($flight['arrival_city']); ?></strong>
            </p>
            <p style="color: var(--gray-text); margin-top: 0.5rem;">
                Місця: <?php echo implode(', ', $selected_seats); ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="passengersForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="passengers-list">
                <?php for ($i = 0; $i < $passengers_count; $i++): ?>
                    <div class="passenger-card">
                        <div class="passenger-header">
                            <div class="passenger-number">Пасажир <?php echo $i + 1; ?></div>
                            <div style="color: var(--gray-text);">Місце: <?php echo $selected_seats[$i]; ?></div>
                        </div>

                        <?php if ($i === 0 && !empty($existing_passengers)): ?>
                            <div class="form-group">
                                <label>Використати збережені дані:</label>
                                <select class="form-control passenger-select" data-index="<?php echo $i; ?>">
                                    <option value="">-- Новий пасажир --</option>
                                    <?php foreach ($existing_passengers as $ep): ?>
                                        <option value="<?php echo htmlspecialchars(json_encode($ep)); ?>">
                                            <?php echo htmlspecialchars($ep['first_name'] . ' ' . $ep['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name_<?php echo $i; ?>">Ім'я: *</label>
                                <input type="text" name="first_name_<?php echo $i; ?>" 
                                       id="first_name_<?php echo $i; ?>" 
                                       class="form-control passenger-input" 
                                       required 
                                       minlength="2" 
                                       maxlength="50" 
                                       pattern="[A-Za-zА-Яа-яіІїЇєЄґҐ\s'-]+"
                                       oninput="checkPassengersFormValidity()">
                            </div>

                            <div class="form-group">
                                <label for="last_name_<?php echo $i; ?>">Прізвище: *</label>
                                <input type="text" name="last_name_<?php echo $i; ?>" 
                                       id="last_name_<?php echo $i; ?>" 
                                       class="form-control passenger-input" 
                                       required 
                                       minlength="2" 
                                       maxlength="50" 
                                       pattern="[A-Za-zА-Яа-яіІїЇєЄґҐ\s'-]+"
                                       oninput="checkPassengersFormValidity()">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth_<?php echo $i; ?>">Дата народження: *</label>
                                <input type="date" name="date_of_birth_<?php echo $i; ?>" 
                                       id="date_of_birth_<?php echo $i; ?>" 
                                       class="form-control passenger-input" 
                                       max="<?php echo date('Y-m-d'); ?>" 
                                       required
                                       oninput="checkPassengersFormValidity()">
                            </div>

                            <div class="form-group">
                                <label for="passport_number_<?php echo $i; ?>">Номер паспорта: *</label>
                                <input type="text" name="passport_number_<?php echo $i; ?>" 
                                       id="passport_number_<?php echo $i; ?>" 
                                       class="form-control passenger-input" 
                                       required 
                                       minlength="6" 
                                       maxlength="20" 
                                       pattern="[A-Z0-9]+"
                                       oninput="this.value = this.value.toUpperCase(); checkPassengersFormValidity();">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nationality_<?php echo $i; ?>">Громадянство:</label>
                            <input type="text" name="nationality_<?php echo $i; ?>" 
                                   id="nationality_<?php echo $i; ?>" 
                                   class="form-control" 
                                   value="Україна"
                                   maxlength="50">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" id="passengersBtn" disabled>Продовжити до бронювання</button>
            </div>
            
            <noscript>
                <style>
                    #passengersBtn { display: none !important; }
                </style>
                <div style="text-align: center; margin-top: 1rem;">
                    <button type="submit" form="passengersForm" class="btn btn-primary">Продовжити до бронювання</button>
                </div>
            </noscript>
        </form>
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
function checkPassengersFormValidity() {
    const form = document.getElementById('passengersForm');
    const submitBtn = document.getElementById('passengersBtn');
    
    if (!form || !submitBtn) return;
    
    const inputs = form.querySelectorAll('.passenger-input');
    let allValid = true;
    
    inputs.forEach(input => {
        const value = input.value.trim();
        
        if (!value || value.length === 0 || !input.validity.valid) {
            allValid = false;
        }
    });
    
    submitBtn.disabled = !allValid;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('passengersForm');
    
    if (form) {
        // Автозаповнення існуючих пасажирів
        const selects = document.querySelectorAll('.passenger-select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                if (this.value) {
                    const data = JSON.parse(this.value);
                    const index = this.getAttribute('data-index');
                    
                    document.getElementById('first_name_' + index).value = data.first_name;
                    document.getElementById('last_name_' + index).value = data.last_name;
                    document.getElementById('date_of_birth_' + index).value = data.date_of_birth;
                    document.getElementById('passport_number_' + index).value = data.passport_number;
                    document.getElementById('nationality_' + index).value = data.nationality || 'Україна';
                    
                    checkPassengersFormValidity();
                }
            });
        });
        
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required]');
            let hasErrors = false;
            
            inputs.forEach(input => {
                const value = input.value.trim();
                
                if (!value || value.length === 0) {
                    input.setCustomValidity('Це поле обов\'\'язкове');
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
        
        checkPassengersFormValidity();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

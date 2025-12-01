<?php 
require_once '../includes/config.php';
$page_title = 'Пошук рейсів - SkyBooking';

// Валідація параметрів GET (якщо форма була відправлена)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['departure'])) {
    $errors = [];
    
    // Валідація аеропорту відправлення
    if (empty($_GET['departure']) || !validateInt($_GET['departure'])) {
        $errors[] = 'Оберіть аеропорт відправлення.';
    }
    
    // Валідація аеропорту прибуття
    if (empty($_GET['arrival']) || !validateInt($_GET['arrival'])) {
        $errors[] = 'Оберіть аеропорт прибуття.';
    }
    
    // Перевірка, що аеропорти не однакові
    if (!empty($_GET['departure']) && !empty($_GET['arrival']) && $_GET['departure'] === $_GET['arrival']) {
        $errors[] = 'Аеропорт відправлення та прибуття не можуть бути однаковими.';
    }
    
    // Валідація дати
    if (empty($_GET['date']) || !validateDate($_GET['date'])) {
        $errors[] = 'Введіть коректну дату вильоту.';
    } elseif (strtotime($_GET['date']) < strtotime('today')) {
        $errors[] = 'Дата вильоту не може бути в минулому.';
    }
    
    // Валідація кількості пасажирів
    if (empty($_GET['passengers']) || !validateInt($_GET['passengers'])) {
        $errors[] = 'Введіть кількість пасажирів.';
    } elseif (intval($_GET['passengers']) < 1 || intval($_GET['passengers']) > 9) {
        $errors[] = 'Кількість пасажирів має бути від 1 до 9.';
    }
    
    // Логування підозрілих спроб
    if (!empty($errors)) {
        logSecurityEvent('invalid_search_params', $_SESSION['customer_id'] ?? null);
    }
}

require_once '../includes/header.php';

// Отримуємо список аеропортів
$airports = $pdo->query("SELECT * FROM airports ORDER BY city, name")->fetchAll();
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Пошук рейсів</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="GET" action="/public/flights.php" id="searchForm" novalidate>
                <div class="form-group">
                    <label for="departure">Аеропорт відправлення:</label>
                    <select name="departure" id="departure" class="form-control" required oninput="checkSearchFormValidity()">
                        <option value="">-- Оберіть аеропорт --</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['airport_id']; ?>">
                                <?php echo htmlspecialchars($airport['city'] . ' - ' . $airport['name'] . ' (' . $airport['iata_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="arrival">Аеропорт прибуття:</label>
                    <select name="arrival" id="arrival" class="form-control" required oninput="checkSearchFormValidity()">
                        <option value="">-- Оберіть аеропорт --</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['airport_id']; ?>">
                                <?php echo htmlspecialchars($airport['city'] . ' - ' . $airport['name'] . ' (' . $airport['iata_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Дата вильоту:</label>
                    <input type="date" name="date" id="date" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required oninput="checkSearchFormValidity()">
                </div>

                <div class="form-group">
                    <label for="passengers">Кількість пасажирів:</label>
                    <input type="number" name="passengers" id="passengers" class="form-control" 
                           min="1" max="9" value="1" required oninput="checkSearchFormValidity()">
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="searchBtn" disabled>Знайти рейси</button>
            </form>
            
            <noscript>
                <style>
                    #searchBtn { display: none !important; }
                </style>
                <button type="submit" form="searchForm" class="btn btn-primary btn-full">Знайти рейси</button>
            </noscript>
        </div>

        <div style="margin-top: 3rem; text-align: center; color: var(--gray-text);">
            <p>💡 <strong>Порада:</strong> Бронюйте квитки заздалегідь для найкращих цін</p>
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

select.form-control:invalid {
    border-color: var(--danger-color);
}

select.form-control:valid {
    border-color: var(--success-color);
}
</style>

<script>
function checkSearchFormValidity() {
    const form = document.getElementById('searchForm');
    const submitBtn = document.getElementById('searchBtn');
    
    if (!form || !submitBtn) return;
    
    const departure = document.getElementById('departure');
    const arrival = document.getElementById('arrival');
    const date = document.getElementById('date');
    const passengers = document.getElementById('passengers');
    
    // Перевірка всіх полів
    const departureValid = departure.value && departure.value !== '';
    const arrivalValid = arrival.value && arrival.value !== '';
    const dateValid = date.value && date.validity.valid;
    const passengersValid = passengers.value && passengers.validity.valid;
    const notSameAirport = departure.value !== arrival.value;
    
    // Кнопка активна тільки якщо всі поля валідні
    submitBtn.disabled = !(departureValid && arrivalValid && dateValid && passengersValid && notSameAirport);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('searchForm');
    
    if (form) {
        const departure = document.getElementById('departure');
        const arrival = document.getElementById('arrival');
        
        // Перевірка на однакові аеропорти
        function checkAirports() {
            if (departure.value && arrival.value && departure.value === arrival.value) {
                arrival.setCustomValidity('Аеропорт прибуття має відрізнятись від аеропорту відправлення');
            } else {
                arrival.setCustomValidity('');
            }
            checkSearchFormValidity();
        }
        
        departure.addEventListener('change', checkAirports);
        arrival.addEventListener('change', checkAirports);
        
        form.addEventListener('submit', function(e) {
            // Перевірка на порожні значення
            if (!departure.value || !arrival.value) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
            
            // Перевірка на однакові аеропорти
            if (departure.value === arrival.value) {
                e.preventDefault();
                arrival.setCustomValidity('Аеропорт прибуття має відрізнятись від аеропорту відправлення');
                form.reportValidity();
                return false;
            }
            
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
        });
        
        checkSearchFormValidity();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

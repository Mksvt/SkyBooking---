<?php
require_once '../includes/config.php';
requireLogin();
if (!isAdmin()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$table = getGet('table', 'customers');
$allowed = ['customers','flights','bookings','tickets','airports','airlines','passengers','payments'];
if (!in_array($table, $allowed)) die('Invalid table');

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// get columns
$stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION");
$stmt->execute([DB_NAME, $table]);
$cols = $stmt->fetchAll();

$pk = null;
foreach ($cols as $c) if ($c['COLUMN_KEY'] === 'PRI') { $pk = $c['COLUMN_NAME']; break; }

$row = null;
if ($id && $pk) {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Invalid CSRF'; }
    else {
        $data = [];
        $placeholders = [];
        $params = [];

        foreach ($cols as $c) {
            $name = $c['COLUMN_NAME'];
            if ($c['EXTRA'] === 'auto_increment' && !$id) continue;
            $val = isset($_POST[$name]) ? $_POST[$name] : null;
            $data[$name] = $val;
        }

        try {
            if ($id && $pk) {
                $sets = [];
                foreach ($data as $k => $v) { $sets[] = "`$k` = ?"; $params[] = $v; }
                $params[] = $id;
                $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$pk` = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $keys = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($keys), '?'));
                $sql = "INSERT INTO `$table` (`" . implode('`,`', $keys) . "`) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $id = $pdo->lastInsertId();
            }
            header('Location: admin.php?table=' . urlencode($table));
            exit;
        } catch (Exception $e) {
            $error = 'DB error: ' . $e->getMessage();
        }
    }
}

$page_title = ($id ? 'Редагування' : 'Створення') . ' - ' . $table . ' - SkyBooking';
require_once '../includes/header.php';
?>

<style>
.admin-container { max-width: 1000px; margin: 2rem auto; padding: 0 1.5rem; }
.admin-card {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 12px 12px 0 0;
    margin: -2.5rem -2.5rem 2rem;
}
.form-table {
    width: 100%;
    border-collapse: collapse;
}
.form-table tr {
    border-bottom: 1px solid #f0f0f0;
}
.form-table tr:last-child {
    border-bottom: none;
}
.form-table td {
    padding: 1rem 0;
}
.form-table label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.5rem;
}
.form-table input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}
.form-table input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.auto-field {
    background: #f8f9fa;
    color: #6c757d;
    font-weight: 600;
    padding: 0.75rem;
    border-radius: 6px;
}
.coord-helper {
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}
.coord-helper h4 {
    color: #0c4a6e;
    margin: 0 0 0.5rem 0;
    font-size: 0.95rem;
}
.coord-helper p {
    color: #0369a1;
    font-size: 0.85rem;
    margin: 0;
}
</style>

<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h1 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 0.75rem;">
                <?php echo $id ? '✏️ Редагування' : '➕ Створення'; ?>
                <span style="opacity: 0.8; font-size: 1.2rem;">• <?php echo htmlspecialchars($table); ?></span>
            </h1>
        </div>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #842029; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #dc3545;">
                <strong>❌ Помилка:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <table class="form-table">
                <?php foreach ($cols as $c):
                    $name = $c['COLUMN_NAME'];
                    $isAuto = ($c['EXTRA'] === 'auto_increment');
                    if ($isAuto && !$id) continue;
                    $val = $row[$name] ?? '';
                ?>
                <tr>
                    <td style="width: 250px;">
                        <label><?php echo htmlspecialchars($name); ?></label>
                        <?php if ($c['COLUMN_KEY'] === 'PRI'): ?>
                            <span style="background: #667eea; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">PRIMARY KEY</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isAuto): ?>
                            <div class="auto-field"><?php echo htmlspecialchars($val); ?> <small>(auto-increment)</small></div>
                        <?php else: ?>
                            <?php 
                            $placeholder = "Введіть " . $name;
                            $inputType = "text";
                            $step = "";
                            
                            // Спеціальні підказки та типи для певних полів
                            if ($name === 'latitude') {
                                $placeholder = "Наприклад: 50.401694 (широта від -90 до 90)";
                                $inputType = "number";
                                $step = "step='0.000001'";
                            } elseif ($name === 'longitude') {
                                $placeholder = "Наприклад: 30.451536 (довгота від -180 до 180)";
                                $inputType = "number";
                                $step = "step='0.000001'";
                            } elseif ($name === 'email') {
                                $inputType = "email";
                                $placeholder = "Введіть email";
                            } elseif (strpos($name, 'date') !== false || strpos($name, 'time') !== false) {
                                $inputType = "datetime-local";
                                if ($val) {
                                    $val = date('Y-m-d\TH:i', strtotime($val));
                                }
                            } elseif (strpos($name, 'phone') !== false) {
                                $placeholder = "+380XXXXXXXXX";
                            } elseif (strpos($name, 'price') !== false || strpos($name, 'amount') !== false) {
                                $inputType = "number";
                                $step = "step='0.01'";
                                $placeholder = "Введіть суму";
                            }
                            ?>
                            <input type="<?php echo $inputType; ?>" 
                                   name="<?php echo htmlspecialchars($name); ?>" 
                                   value="<?php echo htmlspecialchars($val); ?>"
                                   placeholder="<?php echo $placeholder; ?>"
                                   <?php echo $step; ?>>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($table === 'airports'): ?>
            <div class="coord-helper">
                <h4>📍 Підказка: Як знайти координати аеропорту</h4>
                <p>1️⃣ Відкрийте <a href="https://www.google.com/maps" target="_blank" style="color: #0c4a6e; font-weight: 600;">Google Maps</a></p>
                <p>2️⃣ Введіть назву аеропорту (наприклад, "Boryspil International Airport")</p>
                <p>3️⃣ Клікніть правою кнопкою миші на маркер аеропорту → оберіть "What's here?"</p>
                <p>4️⃣ Координати з'являться знизу (наприклад: 50.401694, 30.451536)</p>
                <p>5️⃣ Перше число - <strong>latitude</strong> (широта), друге - <strong>longitude</strong> (довгота)</p>
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2.5rem; font-size: 1rem;">
                    💾 <?php echo $id ? 'Зберегти зміни' : 'Створити запис'; ?>
                </button>
                <a href="admin.php?table=<?php echo urlencode($table); ?>" class="btn btn-secondary" style="padding: 0.75rem 2rem; font-size: 1rem;">
                    ❌ Скасувати
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Валідація координат
document.querySelector('form').addEventListener('submit', function(e) {
    const latInput = document.querySelector('input[name="latitude"]');
    const lonInput = document.querySelector('input[name="longitude"]');
    
    if (latInput && latInput.value) {
        const lat = parseFloat(latInput.value);
        if (lat < -90 || lat > 90) {
            e.preventDefault();
            alert('❌ Широта (latitude) повинна бути від -90 до 90');
            latInput.focus();
            return false;
        }
    }
    
    if (lonInput && lonInput.value) {
        const lon = parseFloat(lonInput.value);
        if (lon < -180 || lon > 180) {
            e.preventDefault();
            alert('❌ Довгота (longitude) повинна бути від -180 до 180');
            lonInput.focus();
            return false;
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

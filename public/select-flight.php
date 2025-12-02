<?php 
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['flight_id'])) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

$flight_id = $_POST['flight_id'];

// Зберігаємо ID рейсу в сесії
$_SESSION['selected_flight_id'] = $flight_id;

// Перевірка авторизації
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = '/public/seats.php';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Якщо користувач авторизований - переходимо на вибір місць
header('Location: ' . BASE_URL . '/seats.php');
exit;
?>

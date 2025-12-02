<?php
require_once 'includes/config.php';

$email = 'test@test.com';
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Оновлюємо пароль
$stmt = $pdo->prepare("UPDATE customers SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, $email]);

echo "Password updated successfully!\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
?>

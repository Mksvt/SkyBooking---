<?php 
require_once '../includes/config.php';
$page_title = 'SkyBooking - Бронювання авіаквитків онлайн';
require_once '../includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>✈️ Бронюйте авіаквитки легко та швидко</h1>
        <p>Ваша подорож починається тут. Знайдіть найкращі рейси за найвигіднішими цінами</p>
        <a href="/public/search.php" class="btn btn-primary">Знайти рейс</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Чому обирають SkyBooking?</h2>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>Понад 500 напрямків</h3>
                <p>Подорожуйте по всьому світу з найкращими авіакомпаніями</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3>Безпечні платежі</h3>
                <p>Захищені транзакції та різні способи оплати</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Миттєве бронювання</h3>
                <p>Швидке оформлення та отримання квитків онлайн</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Найкращі ціни</h3>
                <p>Знаходимо найвигідніші пропозиції для вас</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Електронні квитки</h3>
                <p>Отримайте квиток з QR-кодом на пошту</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎫</div>
                <h3>Вибір місця</h3>
                <p>Обирайте зручне місце в літаку самостійно</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background: var(--light-bg);">
    <div class="container">
        <h2 class="section-title">Як це працює?</h2>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">1️⃣</div>
                <h3>Пошук рейсу</h3>
                <p>Виберіть аеропорти відправлення та прибуття, дату вильоту</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">2️⃣</div>
                <h3>Вибір рейсу</h3>
                <p>Оберіть авіакомпанію та зручний час вильоту</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">3️⃣</div>
                <h3>Реєстрація/Вхід</h3>
                <p>Увійдіть в акаунт або зареєструйтесь</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">4️⃣</div>
                <h3>Вибір місць</h3>
                <p>Оберіть місця в літаку для себе та пасажирів</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">5️⃣</div>
                <h3>Оплата</h3>
                <p>Завершіть бронювання безпечною оплатою</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">6️⃣</div>
                <h3>Отримання квитка</h3>
                <p>Отримайте електронний квиток з QR-кодом</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container" style="text-align: center;">
        <h2 class="section-title">Готові до подорожі?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem; color: var(--gray-text);">
            Приєднуйтесь до тисяч задоволених пасажирів, які обирають SkyBooking
        </p>
        <a href="/public/search.php" class="btn btn-primary" style="margin-right: 1rem;">Знайти рейс</a>
        <?php if (!isLoggedIn()): ?>
            <a href="/public/register.php" class="btn btn-secondary">Зареєструватись</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

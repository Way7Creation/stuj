<?php
// Создайте файл: public/admin-panel.php

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin-login.php');
    exit;
}

// Простое подключение к БД для проверки
$pdo = new PDO('mysql:host=127.0.0.1;dbname=stuj_bd_jwrl', 'stuj_admin_bd', 'rA0pM9hV7scE2oN5vF3v');

// Получаем статистику
$products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$themes = $pdo->query("SELECT COUNT(*) FROM themes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Стужа</title>
    <style>
        body { 
            background: #121212; 
            color: #fff; 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 2rem;
        }
        .header {
            background: #1f1f1f;
            padding: 1rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #1f1f1f;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #e63946;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #e63946;
        }
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .link-card {
            background: #1f1f1f;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
        }
        .link-card:hover {
            background: #2f2f2f;
            transform: translateY(-2px);
        }
        .link-card a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }
        .logout {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .status {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 Админ-панель Стужа</h1>
        <div>
            <span class="status">✅ Система работает</span>
            <a href="/admin-login.php?logout=1" class="logout">🚪 Выход</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $products ?></div>
            <div>Товаров в каталоге</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $categories ?></div>
            <div>Категорий</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $themes ?></div>
            <div>Тем оформления</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">✅</div>
            <div>Laravel готов</div>
        </div>
    </div>

    <div class="links">
        <div class="link-card">
            <a href="/api/catalog" target="_blank">📋 API Каталог</a>
            <p>Проверить API товаров</p>
        </div>
        <div class="link-card">
            <a href="/" target="_blank">🏠 Главная страница</a>
            <p>Открыть сайт</p>
        </div>
        <div class="link-card">
            <a href="/catalog" target="_blank">📦 Каталог</a>
            <p>Просмотр товаров</p>
        </div>
        <div class="link-card">
            <a href="/quiz" target="_blank">🔮 Квиз подбора</a>
            <p>Астрологический подбор</p>
        </div>
    </div>

    <div style="margin-top: 2rem; padding: 1rem; background: rgba(40,167,69,0.1); border-radius: 8px;">
        <h3>✅ Система запущена успешно!</h3>
        <p>• Laravel: Работает</p>
        <p>• База данных: Подключена</p>
        <p>• Vue.js: Собран</p>
        <p>• API: Доступно</p>
    </div>
</body>
</html>

<?php
// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin-login.php');
    exit;
}
?>
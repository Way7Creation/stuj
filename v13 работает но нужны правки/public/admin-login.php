<?php
// Создайте файл: public/admin-login.php

session_start();

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email === 'admin@stuj.ru' && $password === 'password') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin-panel.php');
        exit;
    } else {
        $error = 'Неверные данные';
    }
}
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-form { 
            background: #1f1f1f; 
            padding: 2rem; 
            border-radius: 8px; 
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        input { 
            width: 100%;
            padding: 0.75rem; 
            margin: 0.5rem 0; 
            background: #333; 
            border: 1px solid #555; 
            color: #fff; 
            border-radius: 4px;
            box-sizing: border-box;
        }
        button { 
            width: 100%;
            padding: 0.75rem; 
            background: #e63946; 
            color: #fff; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }
        button:hover { background: #d52936; }
        .error { 
            color: #ff6b6b; 
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: rgba(255,107,107,0.1);
            border-radius: 4px;
        }
        h2 { 
            text-align: center; 
            margin-bottom: 1.5rem;
            color: #e63946;
        }
        .info {
            text-align: center; 
            margin-top: 1rem; 
            font-size: 0.9rem; 
            color: #999;
            background: rgba(230,57,70,0.1);
            padding: 0.75rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>🔐 Админ-панель Стужа</h2>
        
        <?php if (isset($error)): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="📧 Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="🔑 Пароль" required>
            <button type="submit">🚀 Войти</button>
        </form>
        
        <div class="info">
            <strong>По умолчанию:</strong><br>
            📧 admin@stuj.ru<br>
            🔑 password
        </div>
    </div>
</body>
</html>
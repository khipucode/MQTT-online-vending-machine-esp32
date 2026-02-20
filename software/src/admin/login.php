<?php
session_start();
require_once '../app/config.php';

if(isset($_POST['user']) && $_POST['user'] === ADMIN_USER && $_POST['pass'] === ADMIN_PASS) {
    $_SESSION['admin_logged'] = true;
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body { margin:0; font-family: -apple-system, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; padding: 20px; box-sizing: border-box; }
        .login-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        h2 { margin-top: 0; color: #1e293b; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🔒 Acesso Admin</h2>
        <form method="POST">
            <input type="text" name="user" placeholder="Usuário" required autocapitalize="none">
            <input type="password" name="pass" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
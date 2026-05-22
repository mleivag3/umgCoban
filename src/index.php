<?php
require_once 'config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $users = USERS;

    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        $_SESSION['user'] = [
            'username' => $username,
            'name'     => $users[$username]['name'],
            'role'     => $users[$username]['role'],
            'avatar'   => $users[$username]['avatar'],
        ];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Usuario o contrasena incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — <?= APP_NAME ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 48px 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 25px 60px rgba(0,0,0,.35);
    }
    .logo {
      text-align: center;
      margin-bottom: 32px;
    }
    .logo-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 56px; height: 56px;
      background: linear-gradient(135deg,#10b981,#059669);
      border-radius: 14px;
      font-size: 28px;
      margin-bottom: 12px;
    }
    .logo h1 { font-size: 22px; color: #0f172a; font-weight: 700; }
    .logo p  { font-size: 13px; color: #64748b; margin-top: 4px; }
    .badge-new {
      display: inline-block;
      background: #dcfce7; color: #16a34a;
      font-size: 11px; font-weight: 700;
      padding: 2px 8px; border-radius: 20px;
      margin-top: 6px;
    }
    .form-group { margin-bottom: 18px; }
    label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    input[type=text], input[type=password] {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px; font-size: 15px;
      transition: border-color .2s;
      outline: none;
    }
    input:focus { border-color: #10b981; }
    .btn {
      width: 100%; padding: 12px;
      background: linear-gradient(135deg,#10b981,#059669);
      color: #fff; border: none; border-radius: 8px;
      font-size: 15px; font-weight: 600; cursor: pointer;
      transition: opacity .2s;
    }
    .btn:hover { opacity: .9; }
    .error {
      background: #fef2f2; border: 1px solid #fca5a5;
      color: #dc2626; border-radius: 8px;
      padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
    }
    .hint {
      margin-top: 20px; text-align: center;
      font-size: 12px; color: #94a3b8;
    }
    .hint code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; }
    .version {
      text-align: center; margin-top: 28px;
      font-size: 11px; color: #cbd5e1;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <div class="logo-icon">&#128640;</div>
      <h1><?= APP_NAME ?></h1>
      <p>Pipeline CI/CD Demo</p>
      <span class="badge-new">&#10003; Deploy automatico activo</span>
    </div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="admin" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Contrasena</label>
        <input type="password" id="password" name="password" placeholder="********" required>
      </div>
      <button type="submit" class="btn">Iniciar sesion</button>
    </form>

    <div class="hint">
      Credenciales demo:<br>
      <code>admin</code> / <code>admin123</code> &nbsp;|&nbsp; <code>demo</code> / <code>demo123</code>
    </div>
    <div class="version">v<?= APP_VERSION ?> &mdash; build <?= BUILD_DATE ?></div>
  </div>
</body>
</html>

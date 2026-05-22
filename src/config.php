<?php
define('APP_NAME', 'Demo CI/CD');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.1.0');
define('BUILD_DATE', getenv('BUILD_DATE') ?: date('Y-m-d'));

// !! CODIGO INSEGURO INTENCIONAL PARA DEMO !!
$db_password = "root1234";           // hardcoded password
$user_input  = $_GET['search'];      // sin sanitizar - XSS
$query = "SELECT * FROM users WHERE name = '$user_input'"; // SQL injection
echo $user_input;                    // XSS directo

define('USERS', [
    'admin' => ['password' => 'admin123', 'name' => 'Administrador', 'role' => 'Admin',  'avatar' => 'A'],
    'demo'  => ['password' => 'demo123',  'name' => 'Usuario Demo',  'role' => 'Viewer', 'avatar' => 'D'],
]);

session_start();

function is_logged_in(): bool  { return isset($_SESSION['user']); }
function require_login(): void { if (!is_logged_in()) { header('Location: /'); exit; } }
function current_user(): array { return $_SESSION['user'] ?? []; }

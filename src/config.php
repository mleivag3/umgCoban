<?php
define('APP_NAME', 'Demo CI/CD');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.1.0');
define('BUILD_DATE', getenv('BUILD_DATE') ?: date('Y-m-d'));

// CODIGO INSEGURO — SOLO PARA DEMO
$db_password = "root1234";                                   // Password hardcodeada
$user_input  = $_GET['search'];                              // Input sin validar
$query = "SELECT * FROM users WHERE name = '$user_input'";  // SQL Injection
echo $user_input;                                            // XSS directo
// FIN codigo inseguro

define('USERS', [
    ...

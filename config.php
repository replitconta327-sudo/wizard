<?php
if (!defined('PIZZARIA_SYSTEM')) {
    define('PIZZARIA_SYSTEM', true);
}

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
} catch (Exception $e) {
    error_log('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    die('Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.');
}

define('SITE_NAME', 'Pizzaria São Paulo');
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('TIMEZONE', 'America/Sao_Paulo');

date_default_timezone_set(TIMEZONE);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/index.html');
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserType() {
    return $_SESSION['user_tipo'] ?? 'cliente';
}

<?php
require_once __DIR__ . '/config/session.php';

// Determine user type and log appropriate activity
if (isset($_SESSION['user_tipo'])) {
    try {
        if ($_SESSION['user_tipo'] === 'admin' && isset($_SESSION['user_id'])) {
            // Log admin logout
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, acao, detalhes, ip_address, user_agent, criado_em) VALUES (?, 'ADMIN_LOGOUT', 'Admin logged out', ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        } elseif ($_SESSION['user_tipo'] === 'cliente' && isset($_SESSION['user_id'])) {
            // Log customer logout
            $stmt = $pdo->prepare("INSERT INTO customer_logs (usuario_id, acao, detalhes, ip_address, user_agent, criado_em) VALUES (?, 'CUSTOMER_LOGOUT', 'Customer logged out', ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        }
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect based on user type
$redirect = '/login.php?logout=success';
if (isset($_GET['redirect'])) {
    $allowedRedirects = ['admin', 'customer', 'home'];
    $redirectParam = $_GET['redirect'];
    if (in_array($redirectParam, $allowedRedirects)) {
        switch ($redirectParam) {
            case 'admin':
                $redirect = '/admin/login.php?logout=success';
                break;
            case 'customer':
                $redirect = '/login.php?logout=success';
                break;
            case 'home':
                $redirect = '/index.php?logout=success';
                break;
        }
    }
}

header("Location: $redirect");
exit;
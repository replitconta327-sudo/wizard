<?php
session_start();
// Simula login para demo
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin Demo';
header('Location: /admin/pedidos.php');
exit;

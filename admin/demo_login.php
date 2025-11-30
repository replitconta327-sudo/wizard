<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
header('Location: /admin/pedidos.php');
exit;

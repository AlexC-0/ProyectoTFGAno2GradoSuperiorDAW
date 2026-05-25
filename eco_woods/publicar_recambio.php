<?php











require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
header('Location: publicar.php?tipo_publicacion=recambio');
exit;


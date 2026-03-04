<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Redirige el flujo antiguo de publicar recambio al nuevo punto unificado.
Por que se hizo asi: Se mantiene por compatibilidad para no romper enlaces existentes.
Para que sirve: Evita errores de navegacion mientras se moderniza la app.
*/
/*
Compatibilidad legacy:
- Esta ruta antigua se mantiene para no romper enlaces previos.
- El flujo de publicacion de recambios vive en publicar.php.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
header('Location: publicar.php?tipo_publicacion=recambio');
exit;

/*BORRAR*/

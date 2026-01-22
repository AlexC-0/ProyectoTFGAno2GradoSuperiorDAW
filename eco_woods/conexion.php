<?php
// Datos de conexión
$servidor  = "localhost";
$usuario   = "root";
$password  = "";          // En XAMPP por defecto suele estar vacío
$basedatos = "eco_woods";

// Crear conexión
$conexion = mysqli_connect($servidor, $usuario, $password, $basedatos);

// Comprobar conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer codificación de caracteres
mysqli_set_charset($conexion, "utf8mb4");
?>

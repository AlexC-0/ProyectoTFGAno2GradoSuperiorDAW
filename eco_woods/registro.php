<?php
session_start();
require 'conexion.php';

$errores = [];
$exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $telefono  = trim($_POST['telefono'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $localidad = trim($_POST['localidad'] ?? '');

    // Validaciones básicas
    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
        $errores[] = "Todos los campos obligatorios deben estar rellenos.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido.";
    }

    if ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (empty($errores)) {
        // Comprobar si ya existe un usuario con ese email
        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errores[] = "Ya existe un usuario registrado con ese email.";
        } else {
            // Insertar el nuevo usuario
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $telefono_db  = ($telefono  !== '') ? $telefono  : null;
            $provincia_db = ($provincia !== '') ? $provincia : null;
            $localidad_db = ($localidad !== '') ? $localidad : null;

            // OJO: añadimos es_admin con valor 0
            $sql_insert = "INSERT INTO usuarios 
                           (nombre, email, password, telefono, provincia, localidad, es_admin)
                           VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt_insert = $conexion->prepare($sql_insert);

            $stmt_insert->bind_param(
                "ssssss",
                $nombre,
                $email,
                $hash,
                $telefono_db,
                $provincia_db,
                $localidad_db
            );

            if ($stmt_insert->execute()) {
                $exito = "Registro completado correctamente. Ya puedes iniciar sesión.";
            } else {
                $errores[] = "Error al registrar el usuario. Inténtalo de nuevo.";
            }

            $stmt_insert->close();
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - ECO & WOODS</title>
    <!-- Unificamos CSS con el resto del proyecto -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="contenedor">

    <h1 style="display:flex; align-items:center;">
        <img src="uploads/Verde.png"
            alt="ECO & WOODS"
            style="height:180px; width:auto; object-fit:contain; display:block;">
    </h1>

        <nav>
            <a href="index.php">Inicio</a>
            <a href="login.php">Login</a>
            <a href="registro.php">Registro</a>
        </nav>
    </div>
</header>

<main>
    <div class="contenedor">

        <h2>Registro de usuario</h2>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <div class="mensaje exito">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="post" class="formulario">

            <p>
                <label for="nombre">Nombre*</label><br>
                <input type="text" name="nombre" id="nombre" required
                       value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>">
            </p>

            <p>
                <label for="email">Email*</label><br>
                <input type="email" name="email" id="email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </p>

            <p>
                <label for="password">Contraseña*</label><br>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <label for="password2">Repetir contraseña*</label><br>
                <input type="password" name="password2" id="password2" required>
            </p>

            <p>
                <label for="telefono">Teléfono</label><br>
                <input type="text" name="telefono" id="telefono"
                       value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>">
            </p>

            <p>
                <label for="provincia">Provincia</label><br>
                <input type="text" name="provincia" id="provincia"
                       value="<?php echo isset($provincia) ? htmlspecialchars($provincia) : ''; ?>">
            </p>

            <p>
                <label for="localidad">Localidad</label><br>
                <input type="text" name="localidad" id="localidad"
                       value="<?php echo isset($localidad) ? htmlspecialchars($localidad) : ''; ?>">
            </p>

            <p>
                <button type="submit">Registrarme</button>
            </p>

        </form>

    </div>
</main>

<footer>
    <div class="contenedor">
        &copy; <?php echo date('Y'); ?> ECO & WOODS
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>

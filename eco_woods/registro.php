<?php
// Bootstrap: sesion/utilidades comunes y layout compartido.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require 'conexion.php';

$errores = [];
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protege el alta de cuentas frente a envios externos no legitimos.
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $localidad = trim($_POST['localidad'] ?? '');

    // Validaciones basicas previas a BD.
    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
        $errores[] = "Todos los campos obligatorios deben estar rellenos.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato valido.";
    }

    if ($password !== $password2) {
        $errores[] = "Las contrasenas no coinciden.";
    }

    if (empty($errores)) {
        // Evita cuentas duplicadas por email.
        $sql = 'SELECT id_usuario FROM usuarios WHERE email = ?';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errores[] = "Ya existe un usuario registrado con ese email.";
        } else {
            // Hash seguro de contrasena y normalizacion de campos opcionales.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $telefono_db = ($telefono !== '') ? $telefono : null;
            $provincia_db = ($provincia !== '') ? $provincia : null;
            $localidad_db = ($localidad !== '') ? $localidad : null;

            // Alta de usuario normal (es_admin=0 por defecto).
            $sql_insert = "INSERT INTO usuarios
                           (nombre, email, password, telefono, provincia, localidad, es_admin)
                           VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt_insert = $conexion->prepare($sql_insert);
            $stmt_insert->bind_param(
                'ssssss',
                $nombre,
                $email,
                $hash,
                $telefono_db,
                $provincia_db,
                $localidad_db
            );

            if ($stmt_insert->execute()) {
                $exito = "Registro completado correctamente. Ya puedes iniciar sesion.";
            } else {
                $errores[] = "Error al registrar el usuario. Intentalo de nuevo.";
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['mode' => 'auth', 'active' => 'registro']); ?>

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

        <?php if ($exito !== ''): ?>
            <div class="mensaje exito">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="post" class="formulario">
            <!-- Token anti-CSRF: asegura que el POST nace del formulario legitimo -->
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

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
                <label for="password">Contrasena*</label><br>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <label for="password2">Repetir contrasena*</label><br>
                <input type="password" name="password2" id="password2" required>
            </p>

            <p>
                <label for="telefono">Telefono</label><br>
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

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">?</button>
<script src="js/app.js"></script>

</body>
</html>

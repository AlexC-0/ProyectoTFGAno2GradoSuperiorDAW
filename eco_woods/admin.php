<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Muestra el panel de administracion con listados de usuarios, productos y reseñas.
Por que se hizo asi: Se separan secciones para que la gestion sea clara y se reduce el riesgo de errores al consultar datos.
Para que sirve: Da control centralizado para moderar la plataforma.
*/
/*
DOCUMENTACION_PASO4
Panel de administracion del proyecto.
- Restringe acceso solo a usuarios con rol admin.
- Gestiona secciones de usuarios, muebles, recambios y resenas.
- Incluye acciones de mantenimiento con confirmacion y seguridad.
*/
// Arranque comun y helper de autorizacion para panel admin.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require 'conexion.php';

// Solo admins pueden entrar aqui
ew_require_admin('index.php');
function ew_stmt_result(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// SecciÃ³n actual del panel
$seccion = $_GET['seccion'] ?? 'usuarios';

// Datos para cada secciÃ³n
if ($seccion === 'usuarios') {

    $sql_usuarios = "SELECT id_usuario, nombre, email, telefono, provincia, localidad, fecha_registro, es_admin
                     FROM usuarios
                     ORDER BY fecha_registro DESC";
    $res_usuarios = ew_stmt_result($conexion, $sql_usuarios);

} elseif ($seccion === 'muebles') {

    $sql_muebles = "SELECT m.id_mueble, m.titulo, m.precio, m.provincia, m.localidad,
                           m.estado, m.categoria, m.fecha_publicacion,
                           u.nombre AS nombre_vendedor
                    FROM muebles m
                    JOIN usuarios u ON m.id_usuario = u.id_usuario
                    ORDER BY m.fecha_publicacion DESC";
    $res_muebles = ew_stmt_result($conexion, $sql_muebles);

} elseif ($seccion === 'recambios') {

    $sql_recambios = "SELECT id_recambio, nombre, descripcion, tipo, compatible_con, precio
                      FROM recambios3d
                      ORDER BY id_recambio DESC";
    $res_recambios = ew_stmt_result($conexion, $sql_recambios);

} elseif ($seccion === 'resenas') {

    $sql_resenas = "SELECT r.id_resena, r.puntuacion, r.comentario, r.fecha_resena,
                           u.nombre AS nombre_usuario,
                           m.titulo AS titulo_mueble,
                           m.id_mueble AS id_mueble
                    FROM resenas r
                    JOIN usuarios u ON r.id_usuario = u.id_usuario
                    JOIN muebles m ON r.id_mueble = m.id_mueble
                    ORDER BY r.fecha_resena DESC";
    $res_resenas = ew_stmt_result($conexion, $sql_resenas);

}

$es_admin = ew_is_admin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de administraciÃ³n - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'admin']); ?>

<main>
    <div class="contenedor">

        <h1>Panel de administraciÃ³n</h1>
        <p><strong>Usuario admin:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>

        <hr>

        <!-- SubmenÃº del panel -->
        <nav class="submenu-admin">
            <a href="admin.php?seccion=usuarios"
               class="<?php echo ($seccion === 'usuarios') ? 'activo' : ''; ?>">
                Usuarios
            </a>
            <a href="admin.php?seccion=muebles"
               class="<?php echo ($seccion === 'muebles') ? 'activo' : ''; ?>">
                Muebles
            </a>
            <a href="admin.php?seccion=recambios"
               class="<?php echo ($seccion === 'recambios') ? 'activo' : ''; ?>">
                Recambios 3D
            </a>
            <a href="admin.php?seccion=resenas"
               class="<?php echo ($seccion === 'resenas') ? 'activo' : ''; ?>">
                ReseÃ±as
            </a>
        </nav>

        <hr>

        <?php if ($seccion === 'usuarios'): ?>

            <h2>Listado de usuarios</h2>

            <?php if ($res_usuarios && mysqli_num_rows($res_usuarios) > 0): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>TelÃ©fono</th>
                            <th>UbicaciÃ³n</th>
                            <th>Fecha registro</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($u = mysqli_fetch_assoc($res_usuarios)): ?>
                            <tr>
                                <td><?php echo (int)$u['id_usuario']; ?></td>
                                <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['telefono'] ?? ''); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($u['provincia'] ?? ''); ?>
                                    <?php if (!empty($u['provincia']) && !empty($u['localidad'])): ?>
                                        -
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($u['localidad'] ?? ''); ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['fecha_registro']); ?></td>
                                <td>
                                    <?php
                                    $esAdminFila = !empty($u['es_admin']) && (int)$u['es_admin'] === 1;
                                    echo $esAdminFila ? 'Admin' : 'Usuario';
                                    ?>
                                </td>
                                <td>
                                    <?php if ((int)$u['id_usuario'] !== 1): ?>
                                        <form action="admin_borrar_usuario.php" method="post" style="margin:0;">
    <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion de usuario y datos asociados?');">
        Eliminar
    </button>
</form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay usuarios registrados.</p>
            <?php endif; ?>

        <?php elseif ($seccion === 'muebles'): ?>

            <h2>Listado de muebles</h2>

            <?php if ($res_muebles && mysqli_num_rows($res_muebles) > 0): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>TÃ­tulo</th>
                            <th>Vendedor</th>
                            <th>Precio</th>
                            <th>CategorÃ­a</th>
                            <th>UbicaciÃ³n</th>
                            <th>Estado</th>
                            <th>Fecha publicaciÃ³n</th>
                            <th>Ver</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($m = mysqli_fetch_assoc($res_muebles)): ?>
                            <tr>
                                <td><?php echo (int)$m['id_mueble']; ?></td>
                                <td><?php echo htmlspecialchars($m['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($m['nombre_vendedor']); ?></td>
                                <td>
                                    <?php
                                    $precio = (float)$m['precio'];
                                    echo number_format($precio, 2, ',', '.'); ?> â‚¬
                                </td>
                                <td><?php echo htmlspecialchars($m['categoria'] ?? ''); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($m['provincia']); ?>
                                    -
                                    <?php echo htmlspecialchars($m['localidad']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($m['estado']); ?></td>
                                <td><?php echo htmlspecialchars($m['fecha_publicacion']); ?></td>
                                <td>
                                    <a class="btn-ver"
                                       href="ver_mueble.php?id_mueble=<?php echo (int)$m['id_mueble']; ?>">
                                        Ver
                                    </a>
                                </td>
                                <td>
                                    <form action="admin_borrar_mueble.php" method="post" style="margin:0;">
    <input type="hidden" name="id_mueble" value="<?php echo (int)$m['id_mueble']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion de mueble y datos relacionados?');">
        Eliminar
    </button>
</form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay muebles registrados.</p>
            <?php endif; ?>

        <?php elseif ($seccion === 'recambios'): ?>

            <h2>Listado de recambios 3D</h2>

            <?php if ($res_recambios && mysqli_num_rows($res_recambios) > 0): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Compatible con</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($r = mysqli_fetch_assoc($res_recambios)): ?>
                            <tr>
                                <td><?php echo (int)$r['id_recambio']; ?></td>
                                <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($r['tipo']); ?></td>
                                <td><?php echo htmlspecialchars($r['compatible_con']); ?></td>
                                <td>
                                    <?php
                                    $precioRec = (float)$r['precio'];
                                    echo number_format($precioRec, 2, ',', '.'); ?> â‚¬
                                </td>
                                <td>
                                    <form action="admin_borrar_recambio.php" method="post" style="margin:0;">
    <input type="hidden" name="id_recambio" value="<?php echo (int)$r['id_recambio']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion del recambio?');">
        Eliminar
    </button>
</form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay recambios 3D registrados.</p>
            <?php endif; ?>

        <?php elseif ($seccion === 'resenas'): ?>

            <h2>Listado de reseÃ±as</h2>

            <?php if ($res_resenas && mysqli_num_rows($res_resenas) > 0): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mueble</th>
                            <th>Usuario</th>
                            <th>PuntuaciÃ³n</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($r = mysqli_fetch_assoc($res_resenas)): ?>
                            <tr>
                                <td><?php echo (int)$r['id_resena']; ?></td>
                                <td>
                                    <a href="ver_mueble.php?id_mueble=<?php echo (int)$r['id_mueble']; ?>">
                                        <?php echo htmlspecialchars($r['titulo_mueble']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($r['nombre_usuario']); ?></td>
                                <td><?php echo (int)$r['puntuacion']; ?>/5</td>
                                <td><?php echo htmlspecialchars($r['comentario']); ?></td>
                                <td><?php echo htmlspecialchars($r['fecha_resena']); ?></td>
                                <td>
                                    <form action="admin_borrar_resena.php" method="post" style="margin:0;">
    <input type="hidden" name="id_resena" value="<?php echo (int)$r['id_resena']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion de la resena?');">
        Eliminar
    </button>
</form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay reseÃ±as registradas.</p>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

</body>
</html>

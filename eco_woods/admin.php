<?php
session_start();
require 'conexion.php';

// Solo admins pueden entrar aquí
if (
    !isset($_SESSION['usuario_id']) ||
    empty($_SESSION['es_admin']) ||
    (int)$_SESSION['es_admin'] !== 1
) {
    header("Location: index.php");
    exit;
}

// Sección actual del panel
$seccion = $_GET['seccion'] ?? 'usuarios';

// Datos para cada sección
if ($seccion === 'usuarios') {

    $sql_usuarios = "SELECT id_usuario, nombre, email, telefono, provincia, localidad, fecha_registro, es_admin
                     FROM usuarios
                     ORDER BY fecha_registro DESC";
    $res_usuarios = mysqli_query($conexion, $sql_usuarios);

} elseif ($seccion === 'muebles') {

    $sql_muebles = "SELECT m.id_mueble, m.titulo, m.precio, m.provincia, m.localidad,
                           m.estado, m.categoria, m.fecha_publicacion,
                           u.nombre AS nombre_vendedor
                    FROM muebles m
                    JOIN usuarios u ON m.id_usuario = u.id_usuario
                    ORDER BY m.fecha_publicacion DESC";
    $res_muebles = mysqli_query($conexion, $sql_muebles);

} elseif ($seccion === 'recambios') {

    $sql_recambios = "SELECT id_recambio, nombre, descripcion, tipo, compatible_con, precio
                      FROM recambios3d
                      ORDER BY id_recambio DESC";
    $res_recambios = mysqli_query($conexion, $sql_recambios);

} elseif ($seccion === 'resenas') {

    $sql_resenas = "SELECT r.id_resena, r.puntuacion, r.comentario, r.fecha_resena,
                           u.nombre AS nombre_usuario,
                           m.titulo AS titulo_mueble,
                           m.id_mueble AS id_mueble
                    FROM resenas r
                    JOIN usuarios u ON r.id_usuario = u.id_usuario
                    JOIN muebles m ON r.id_mueble = m.id_mueble
                    ORDER BY r.fecha_resena DESC";
    $res_resenas = mysqli_query($conexion, $sql_resenas);

}

$es_admin = (!empty($_SESSION['es_admin']) && (int)$_SESSION['es_admin'] === 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de administración - ECO & WOODS</title>
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
            <a href="muebles.php">Muebles</a>
            <a href="recambios.php">Recambios 3D</a>

            <!-- Carrito como icono -->
            <a href="ver_carrito.php" class="nav-icon" aria-label="Carrito">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                </svg>
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if ($es_admin): ?>
                    <a href="publicar.php">Publicar</a>
                    <a href="admin.php">Panel Admin</a>
                <?php else: ?>
                    <a href="publicar.php">Publicar mueble</a>
                <?php endif; ?>

                <span class="saludo">
                    Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                </span>
                <a href="mi_perfil.php">Mi perfil</a>
                <a href="logout.php">Cerrar sesión</a>

            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="registro.php">Registro</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <div class="contenedor">

        <h1>Panel de administración</h1>
        <p><strong>Usuario admin:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>

        <hr>

        <!-- Submenú del panel -->
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
                Reseñas
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
                            <th>Teléfono</th>
                            <th>Ubicación</th>
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
                                        <a class="btn-eliminar"
                                           href="admin_borrar_usuario.php?id_usuario=<?php echo (int)$u['id_usuario']; ?>"
                                           onclick="return confirm('¿Seguro que quieres eliminar este usuario y todos sus datos asociados (muebles, reseñas, favoritos)?');">
                                            Eliminar
                                        </a>
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
                            <th>Título</th>
                            <th>Vendedor</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Fecha publicación</th>
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
                                    echo number_format($precio, 2, ',', '.'); ?> €
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
                                    <a class="btn-eliminar"
                                       href="admin_borrar_mueble.php?id_mueble=<?php echo (int)$m['id_mueble']; ?>"
                                       onclick="return confirm('¿Seguro que quieres eliminar este mueble y sus reseñas/favoritos?');">
                                        Eliminar
                                    </a>
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
                                    echo number_format($precioRec, 2, ',', '.'); ?> €
                                </td>
                                <td>
                                    <a class="btn-eliminar"
                                       href="admin_borrar_recambio.php?id_recambio=<?php echo (int)$r['id_recambio']; ?>"
                                       onclick="return confirm('¿Seguro que quieres eliminar este recambio 3D?');">
                                        Eliminar
                                    </a>
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

            <h2>Listado de reseñas</h2>

            <?php if ($res_resenas && mysqli_num_rows($res_resenas) > 0): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mueble</th>
                            <th>Usuario</th>
                            <th>Puntuación</th>
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
                                    <a class="btn-eliminar"
                                       href="admin_borrar_resena.php?id_resena=<?php echo (int)$r['id_resena']; ?>"
                                       onclick="return confirm('¿Seguro que quieres eliminar esta reseña?');">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay reseñas registradas.</p>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        ECO & WOODS - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>

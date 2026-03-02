<?php
/*
DOCUMENTACION_PASO4
API de listado de muebles.
- Devuelve catalogo ordenado con datos relevantes.
- Normaliza tipos numericos para consumo frontend.
- Respuesta estable para listados y filtros.
*/
// Carga conexión y helper de respuesta JSON uniforme.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';

// Consulta pública de catálogo: trae muebles con datos de vendedor y medios para listado.
$sql = "SELECT m.id_mueble,
               m.titulo,
               m.precio,
               m.provincia,
               m.localidad,
               m.estado,
               m.categoria,
               m.fecha_publicacion,
               m.imagen,
               m.imagen2,
               m.imagen3,
               m.imagen4,
               m.imagen5,
               u.nombre AS nombre_vendedor
        FROM muebles m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        ORDER BY m.fecha_publicacion DESC";
$resultado = mysqli_query($conexion, $sql);

// Normalizamos tipos para que la API sea estable en frontend (int/float en vez de strings SQL).
$muebles = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_mueble'] = (int)$fila['id_mueble'];
        $fila['precio'] = (float)$fila['precio'];
        $muebles[] = $fila;
    }
}

// Respuesta consistente para frontend: siempre incluye bandera ok y colección.
ew_json(['ok' => true, 'muebles' => $muebles]);


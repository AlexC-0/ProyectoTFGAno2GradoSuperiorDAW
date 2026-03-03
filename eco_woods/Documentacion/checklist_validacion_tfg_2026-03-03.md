# Checklist de Validacion TFG (2026-03-03)

## 1) Validacion tecnica automatica

- [x] Sintaxis PHP en todo el proyecto (`php -l` en todos los `.php`): OK.
- [x] SQL interpolado de negocio revisado y migrado en flujos criticos: aplicado.
- [x] Endpoints POST con CSRF en web tradicional: cubiertos.
- [x] Excepcion documentada:
  - `api/login.php` y `api/registro.php` no usan CSRF porque son endpoints API stateless de consumo externo (no formulario de sesion web).

## 2) Superficie SQL residual

- [x] Deuda SQL residual cerrada al 100%.
- [x] Eliminados usos de `mysqli_query` para checks de esquema (`SHOW COLUMNS` / `SHOW TABLES`).
- [x] Comprobaciones de metadatos migradas a consultas preparadas sobre `INFORMATION_SCHEMA`.

## 3) Pruebas funcionales manuales (pendientes de ejecucion)

Autenticacion y sesion:

- [ ] Registro web (altas validas/invalidas, email duplicado).
- [ ] Login/logout web.
- [ ] API login/registro (respuestas HTTP y payload).

Catalogo y detalle:

- [ ] Filtros en `muebles.php` (texto, categoria, precio, ubicacion).
- [ ] Visualizacion `recambios.php`.
- [ ] Detalle `ver_mueble.php` y `ver_recambio.php`.

Carrito y compra:

- [ ] Anadir mueble y recambio al carrito.
- [ ] Subir/bajar cantidad.
- [ ] Eliminar item.
- [ ] Finalizar compra (modo prueba).

Favoritos:

- [ ] Toggle favorito mueble.
- [ ] Toggle favorito recambio.

Mensajeria:

- [ ] Enviar mensaje al vendedor.
- [ ] Ver mensaje individual y marcar leido.
- [ ] Responder mensaje.
- [ ] Bandejas recibidos/enviados en perfil.

Panel admin:

- [ ] Listados por seccion (`usuarios`, `muebles`, `recambios`, `resenas`).
- [ ] Borrados admin con confirmacion y efecto en datos relacionados.

Seguridad funcional:

- [ ] Intento de acceso sin login a rutas privadas.
- [ ] Intento de accion POST sin CSRF valido.
- [ ] Intento de borrar recurso ajeno (usuario no propietario).

## 4) Evidencias para memoria/defensa

- [ ] Evidencias funcionales por flujo principal (sin capturas obligatorias).
- [ ] Tabla de casos de prueba (entrada, salida esperada, resultado real).
- [ ] Resumen de medidas de seguridad implementadas (CSRF, sesiones, prepared statements, control de roles).

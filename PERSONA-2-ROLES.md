# Persona 2 — Roles

## Mi aporte

Después del login, el rol guardado en `users.role` decide el panel:

- `encargado` → `/dashboard/encargado`: panel administrativo existente.
- `estudiante` → `/dashboard/estudiante`: consulta existente de equipos disponibles.
- Cualquier otro rol → error 403, sin acceso a un panel.

El estudiante no puede abrir `/equipos/create` ni listar, editar, crear o eliminar equipos desde las rutas administrativas. La comprobación se hace en el servidor; escribir la URL manualmente tampoco permite entrar.

## Archivos modificados

1. `app/Models/User.php`: constantes de roles y método `dashboardRoute()` para elegir el panel.
2. `app/Http/Middleware/RoleMiddleware.php`: comprueba el rol del usuario autenticado y rechaza accesos no autorizados.
3. `routes/web.php`: entrada `/dashboard` y conexión de las rutas del CRUD existente dentro de `auth`, `verified` y `role:encargado`.
4. `resources/views/livewire/pages/auth/login.blade.php`: solo se cambió el destino después de autenticar. Se elimina la URL pendiente para que cada usuario llegue a su panel.

Se agregaron este documento y `tests/Feature/RolesTest.php`. El alias `role` ya estaba registrado en `bootstrap/app.php`; no fue necesario modificarlo.

No se modificaron el registro, el cierre de sesión, los controladores del CRUD, los componentes de préstamos/devoluciones, las vistas, las migraciones, los seeders ni las validaciones de los compañeros. La nueva ruta `dashboard` permite que el registro existente encuentre su destino sin cambiar su código.

## Cómo explicarlo

“La autenticación confirma quién es el usuario. Mi parte usa su rol para decidir qué panel puede ver y qué rutas puede ejecutar. Primero `auth` exige iniciar sesión; después `role:encargado` o `role:estudiante` comprueba el permiso. Si el rol no coincide, Laravel devuelve 403 antes de ejecutar la acción. El estudiante consulta los equipos disponibles mediante la vista que ya estaba implementada.”

## Pruebas

Con PHP y las dependencias compatibles instaladas:

```bash
php artisan optimize:clear
php artisan test --filter=RolesTest
```

Las pruebas cubren login por rol, URL pendiente antes del login, entradas comunes, consulta de disponibles, acceso al panel ajeno, las siete rutas administrativas del CRUD, invitados y roles desconocidos.

Comprobación manual:

1. Iniciar sesión como encargado: debe llegar al panel administrativo.
2. Cerrar sesión e ingresar como estudiante: debe llegar al panel de consulta.
3. Como estudiante, abrir `/equipos/create`: debe devolver 403.
4. Intentar abrir `/equipos/create` sin sesión e iniciar sesión como estudiante: debe llegar al panel del estudiante.
5. Confirmar que un equipo con estado `prestado` no aparece en su consulta.

## Verificación en esta entrega

Se revisó la sintaxis de los archivos modificados y se comprobó la lógica de selección de panel y de permisos de forma aislada. No se ejecutó Laravel ni la suite PHPUnit: el PHP disponible es 8.2 y este proyecto requiere una versión superior y dependencias que el ZIP no incluye. Estas pruebas de integración quedan listas para ejecutar en el entorno del grupo.

Los tests antiguos de autenticación todavía esperan el dashboard genérico; el compañero de QA debe ajustar sus expectativas al destino de cada rol. También hay rutas de perfil que apuntan a un `ProfileController` ausente en este ZIP: se conservaron tal como llegaron para respetar las partes de los demás.

Referencias: [Middleware de Laravel](https://laravel.com/framework/docs/13.x/middleware), [seguridad de Livewire](https://livewire.laravel.com/docs/4.x/security).

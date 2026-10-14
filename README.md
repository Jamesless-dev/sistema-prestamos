# Sistema de préstamos de equipos

Aplicación académica para administrar equipos de laboratorio, registrar préstamos y devoluciones, y solicitar reservas por horario. Desarrollada con Laravel 13, Livewire 4, Volt, Blade y Tailwind CSS 4.

## Requisitos

- PHP 8.3 o superior y Composer 2. El PHP 8.2 de algunas instalaciones de XAMPP no es compatible.
- Extensiones PHP: PDO y el controlador de la base elegida (pdo_sqlite, pdo_mysql o pdo_pgsql), mbstring, openssl, fileinfo, curl, DOM y XML.
- Node.js 22.12 o superior (recomendado Node.js 24) y npm.
- SQLite para una demostración local; MySQL 8 o PostgreSQL 14 o superior para una instalación compartida.

## Instalación rápida

```bash
git clone https://github.com/Jamesless-dev/sistema-prestamos.git
cd sistema-prestamos
composer run setup
php artisan serve
```

Abre http://localhost:8000. El comando setup instala dependencias, crea .env si no existe, comprueba el controlador PHP de la conexión configurada, genera APP_KEY, ejecuta las migraciones y seeders, y compila los estilos.

Si la carpeta ya tiene datos o un .env configurado, revisa esa configuración antes de ejecutar setup: se usa la conexión que indique .env. Para actualizar una instalación existente sin regenerar su APP_KEY:

```bash
composer install
npm ci --ignore-scripts
php artisan migrate --seed
npm run build
```

No cambies APP_KEY en una instalación existente: invalidaría datos cifrados y sesiones.

### Instalación manual en Windows PowerShell

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File database/database.sqlite
php artisan key:generate
php artisan migrate --seed
npm ci --ignore-scripts
npm run build
php artisan serve
```

En Linux/macOS, usa `cp .env.example .env` y `touch database/database.sqlite`.

### MySQL

Crea una base de datos vacía y configura .env:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_prestamos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_clave
```

Después ejecuta `php artisan config:clear` y `php artisan migrate --seed`.

### PostgreSQL

Crea la base en PostgreSQL y configura los datos de tu instalación en .env:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=prestamos_laboratorio
DB_USERNAME=postgres
DB_PASSWORD=tu_clave
```

Si existe DB_URL en .env, revisa esa URL: también puede definir la conexión. No compartas credenciales reales en Git.

```bash
php artisan config:clear
composer run diagnostico
php artisan migrate --seed
php artisan serve
```

En PostgreSQL se utiliza una columna calculada almacenada para impedir dos préstamos activos del mismo equipo. En SQLite/MySQL se conserva la columna virtual. Las pruebas de GitHub incluyen PostgreSQL 16.

### Error «could not find driver»

El PHP que ejecuta el proyecto no tiene habilitado el controlador de la conexión configurada. Ejecuta `php artisan entorno:verificar`: muestra el ejecutable, el php.ini cargado y la extensión necesaria. `composer run diagnostico` comprueba también la conexión real sin cambiar datos.

Para PostgreSQL en Windows, desde la carpeta del proyecto:

```powershell
powershell -ExecutionPolicy Bypass -File .\configurar-postgresql.ps1
```

La herramienta habilita pdo_pgsql en el php.ini utilizado, hace una copia de respaldo y verifica que PHP lo cargue. No cambia .env, no descarga DLL ni elimina datos. Si tienes varios PHP instalados, indica el mismo ejecutable usado por el servidor:

```powershell
powershell -ExecutionPolicy Bypass -File .\configurar-postgresql.ps1 -PhpPath 'C:\ruta\php.exe'
```

Si faltan php_pdo_pgsql.dll o libpq.dll, repara esa instalación con su distribución oficial, de la misma versión y arquitectura. Si PHP no puede cargar la extensión después del cambio, la herramienta restaura la configuración anterior. Si no tienes permisos para editar php.ini, necesitarás usar una terminal con permisos sobre ese archivo.

Detén el servidor actual con Ctrl+C y vuelve a iniciarlo con ese mismo PHP. Después ejecuta `php artisan config:clear`, `composer run diagnostico` y `php artisan migrate --seed`. No uses migrate:fresh para solucionar este error en una base con datos.

### Reiniciar la demostración

```bash
php artisan migrate:fresh --seed
```

**Este comando elimina todas las tablas de la base configurada. Úsalo únicamente para reiniciar datos de demostración.** Para añadir los datos de prueba sin borrar movimientos, usa `php artisan db:seed`: los seeders no duplican ni sobrescriben registros existentes.

## Cuentas de prueba

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Encargado | encargado@tecsup.edu.pe | Admin123 |
| Estudiante | estudiante1@tecsup.edu.pe | Estudiante123 |
| Estudiante | estudiante2@tecsup.edu.pe | Estudiante123 |

Los tres usuarios tienen el correo verificado. Se crean ocho equipos (EQ-001 a EQ-008), inicialmente disponibles. Estas credenciales son exclusivamente para la demostración local; cámbialas antes de publicar el sistema.

El registro público crea únicamente estudiantes. Las cuentas nuevas deben verificar su correo antes de entrar al panel. Con `MAIL_MAILER=log`, el enlace aparece en `storage/logs/laravel.log`; para enviar correos reales, configura SMTP en .env.

## Flujo del sistema

- **Encargado:** entra al panel administrativo, administra equipos, selecciona un estudiante y registra un préstamo. El equipo pasa a Prestado.
- **Devolución:** se guarda fecha_devolucion en el mismo registro. El equipo pasa a Disponible, o a Reservado si todavía tiene una reserva aprobada vigente.
- **Estudiante:** consulta equipos disponibles y solicita reservas futuras. Solo puede consultar y cancelar sus propias solicitudes.
- **Reserva:** Pendiente → Aprobada/Rechazada; una aprobada puede Cancelarse o entregarse dentro de su horario. Al entregar, se crea un préstamo y la reserva pasa a Finalizada.
- Las reservas pendientes pueden competir por un horario; solo una solicitud con horario coincidente puede aprobarse. Horarios consecutivos sí se permiten.
- Por precaución, una reserva aprobada marca el equipo como Reservado desde su aprobación, aunque sea futura. Los préstamos directos no se permiten mientras haya una reserva aprobada vigente.
- Un equipo puede tener una reserva futura mientras está prestado; su entrega espera a que se registre la devolución.
- Las reservas vencidas sin entrega se cancelan automáticamente al ejecutar el proceso de expiración. Finalizada significa entregada; la devolución se consulta en préstamos.

### Proceso de expiración

En desarrollo, usa otra terminal:

```bash
php artisan schedule:work
```

O ejecuta `php artisan reservas:expirar` manualmente. En un servidor, programa `php artisan schedule:run` cada minuto. Sin ese proceso, una reserva vencida puede seguir apareciendo hasta que se recalcula el estado del equipo.

## Base de datos y relaciones

```text
users (role: encargado/estudiante)
  ├── hasMany prestamos mediante estudiante_id
  └── hasMany reservas mediante estudiante_id
equipos
  ├── hasMany prestamos
  └── hasMany reservas
prestamos → belongsTo equipo y estudiante
reservas  → belongsTo equipo y estudiante
```

- equipos: código único, nombre, categoría y estado.
- prestamos: equipo_id, estudiante_id, fecha_prestamo, fecha_devolucion nullable y observaciones.
- reservas: equipo_id, estudiante_id, fecha_inicio, fecha_fin, estado y observaciones.
- Las claves foráneas impiden borrar equipos o usuarios con historial. La interfaz muestra un mensaje explicativo.
- Hay índices para buscar préstamos activos y conflictos de reservas.
- Una columna calculada con índice único permite conservar múltiples préstamos devueltos, pero impide dos préstamos activos del mismo equipo. Si una instalación anterior ya tiene duplicados activos, deben corregirse antes de aplicar la migración; esta no elimina historial automáticamente.
- Las operaciones bloquean primero el equipo dentro de una transacción y vuelven a comprobar el estado antes de guardar. En MySQL, esto serializa las operaciones del mismo equipo. SQLite serializa las escrituras a nivel de base y es adecuado para la demostración local.
- El CRUD permite editar código, nombre y categoría; el estado debe coincidir con los movimientos. Un equipo nuevo siempre se registra Disponible.

## Archivos principales y responsabilidades

| Integrante | Archivos y explicación |
| --- | --- |
| 1 - Autenticación | LoginForm, Actions/Logout y páginas Volt de auth: credenciales, sesión y cierre de sesión. |
| 2 - Roles | RoleMiddleware, User::dashboardRoute, routes/web.php: acceso y redirección por rol. |
| 3 - CRUD | EquipoController, EquipoRequest, modelo Equipo y vistas equipos. |
| 4 - Préstamos | GestionPrestamos y OperacionEquipos::prestar; también solicitud, aprobación y entrega de reservas. |
| 5 - Devolución e integración | OperacionEquipos::devolver, cancelación, estados e historial. |
| 6 - Base de datos | database/migrations, database/seeders y relaciones de los modelos. |
| 7 - QA y entrega | tests/Feature, pruebas de instalación, README y docs/resumen-proyecto.pdf. |

OperacionEquipos concentra las reglas compartidas de préstamos y reservas. Los componentes Livewire validan los formularios y autorizan al usuario en cada acción. El middleware de rol y la verificación de correo persisten en las solicitudes de Livewire.

## Pruebas y comprobaciones

```bash
php artisan test --compact
php vendor/bin/pint --test
npm run build
composer audit
npm audit
```

Las pruebas usan SQLite en memoria por defecto y no borran la base local. Cubren autenticación, roles, CRUD, seeders repetibles, claves foráneas, historial, préstamos duplicados, devoluciones repetidas, reservas superpuestas, propiedad de solicitudes y expiración.

GitHub Actions ejecuta la suite con SQLite, MySQL y PostgreSQL, además de formato y compilación del frontend. Los cambios se revisan mediante Pull Request antes de integrarse a main.

Para la demostración, prueba ambos roles, intenta entrar como estudiante a /equipos/create, registra y devuelve un préstamo, revisa el historial y recorre solicitud → aprobación → entrega de reserva. Comprueba también errores de credenciales y campos obligatorios.

## Configuración

El idioma predeterminado es español y la zona horaria es America/Lima, configurables con APP_LOCALE y APP_TIMEZONE. En un servidor publicado, usa APP_ENV=production y APP_DEBUG=false, credenciales propias y un transporte de correo real.

Laravel Boost es una dependencia de desarrollo requerida por las instrucciones del repositorio. No es necesario para usar el sistema ni se instala con `composer install --no-dev`.

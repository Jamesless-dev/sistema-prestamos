# Sistema de Préstamos

Este sistema es una aplicación web desarrollada íntegramente en Laravel que permite gestionar el control y préstamo de equipos de manera segura y organizada. Su objetivo principal es optimizar la administración de inventario tecnológico o institucional, estableciendo un flujo claro de control entre encargados y usuarios comunes mediante una interfaz basada en roles.

Repositorio oficial: [https://github.com/Jamesless-dev/sistema-prestamos.git](https://github.com/Jamesless-dev/sistema-prestamos.git)

## Requisitos Previos

Antes de comenzar, asegúrate de tener instalados en tu sistema:
- **PHP** >= 8.3
- **Composer**
- **Node.js** y **NPM**
- **PostgreSQL** (asegúrate de tener el servicio corriendo)

## Instalación Paso a Paso

1. **Clona el repositorio:**
   ```bash
   git clone https://github.com/Jamesless-dev/sistema-prestamos.git
   cd sistema-prestamos
   ```

2. **Crea la base de datos:**
   Abre tu gestor de PostgreSQL y crea una base de datos llamada `prestamos_laboratorio`.

3. **Configura el archivo de entorno:**
   Copia el archivo de ejemplo:
   ```bash
   cp .env.example .env
   ```
   Abre el archivo `.env` y ajusta las credenciales de tu base de datos:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=prestamos_laboratorio
   DB_USERNAME=postgres
   DB_PASSWORD=tu_contraseña_de_postgres
   ```

4. **Ejecuta la instalación automática:**
   El proyecto incluye un script configurado en `composer.json` que instala dependencias, genera la clave de la aplicación, ejecuta las migraciones y compila los assets:
   ```bash
   composer run setup
   ```

5. **Puebla la base de datos con datos de prueba:**
   Este comando creará 1 encargado, 2 estudiantes y 8 equipos listos para usar:
   ```bash
   php artisan db:seed
   ```

6. **Inicia el entorno de desarrollo:**
   Abre una terminal y ejecuta el compilador de assets en modo desarrollo (para recarga en vivo):
   ```bash
   npm run dev
   ```
   *(Deja esta terminal corriendo en segundo plano).*

   Abre una **segunda terminal** e inicia el servidor de Laravel:
   ```bash
   php artisan serve
   ```

7. **Accede a la aplicación:**
   Abre tu navegador y ve a: [http://localhost:8000](http://localhost:8000)

---

## Credenciales de Prueba

El sistema incluye usuarios preconfigurados gracias a los seeders. Puedes usar estas credenciales para probar los diferentes roles y flujos:

| Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Encargado** | `encargado@tecsup.edu.pe` | `Admin123` |
| **Estudiante 1** | `juan@gmail.com` | `facil` |
| **Estudiante 2** | `maria@gmail.com` | `craken3214` |

> **Nota importante para pruebas:** Los navegadores comparten la misma sesión en todas sus pestañas. Si necesitas probar el flujo completo (ej: un estudiante solicita y el encargado aprueba), usa **dos navegadores diferentes** (ej. Chrome y Firefox) o una **ventana de incógnito** para iniciar sesión con ambos roles al mismo tiempo, evitando conflictos de sesión.

---

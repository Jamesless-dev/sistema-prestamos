<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar la base de datos</title>
    <style>
        body { margin: 0; background: #f4f4f4; color: #111; font: 17px/1.6 system-ui, sans-serif; }
        main { max-width: 720px; margin: 8vh auto; padding: 32px; background: white; border-radius: 12px; }
        h1 { font-size: 27px; line-height: 1.3; }
        code, pre { background: #eee; padding: 3px 6px; border-radius: 4px; }
        pre { overflow-x: auto; padding: 14px; }
    </style>
</head>
<body>
<main>
    <h1>La base de datos necesita una actualización</h1>
    @if($mostrarDiagnostico)
        <p>La conexión funciona, pero falta la tabla <code>reservas</code>.</p>
        <p>Actualiza el código de la rama <code>mejora-base-datos</code> y ejecuta desde la carpeta del proyecto:</p>
        <pre>composer run actualizar</pre>
        <p>El comando aplica las migraciones y seeders sin borrar usuarios, equipos ni préstamos. Después vuelve a abrir Reservas.</p>
        <p>Puedes comprobar la base con <code>composer run diagnostico</code>.</p>
    @else
        <p>El módulo de reservas requiere actualizar su base de datos. Contacta al encargado del sistema.</p>
    @endif
</main>
</body>
</html>

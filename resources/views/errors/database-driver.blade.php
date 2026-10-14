<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración de base de datos</title>
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
    <h1>No se pudo conectar con la base de datos</h1>
    @if($mostrarDiagnostico)
        <p>El PHP que ejecuta el proyecto no tiene cargada la extensión <code>{{ $extension }}</code>.</p>
        <p>Ejecuta este diagnóstico desde la carpeta del proyecto:</p>
        <pre>php artisan entorno:verificar</pre>
        <p>El comando indica el PHP y el archivo <code>php.ini</code> utilizados. Habilita la extensión en ese archivo y reinicia el servidor.</p>
        @if($extension === 'pdo_pgsql')
            <p>En Windows también puedes ejecutar la herramienta incluida:</p>
            <pre>powershell -ExecutionPolicy Bypass -File .\configurar-postgresql.ps1</pre>
        @endif
        <p>Después de corregirlo, comprueba la conexión con <code>composer run diagnostico</code>.</p>
    @else
        <p>La conexión de base de datos no está disponible. Contacta al encargado para revisar la configuración del servidor.</p>
    @endif
</main>
</body>
</html>

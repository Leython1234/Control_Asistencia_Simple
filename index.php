<?php
$modulo = "inicio";
require_once "config/conexion.php";

$totalEmpleados = 0;
$asistenciasHoy = 0;
$permisosHoy = 0;
$horasExtrasHoy = 0;

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM tb_empleado");
if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalEmpleados = $fila['total'];
}

$fechaHoy = date('Y-m-d');

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM tb_asistencia WHERE Fecha = '$fechaHoy' AND Estado = 'PRESENTE'");
if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $asistenciasHoy = $fila['total'];
}

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM tb_asistencia WHERE Fecha = '$fechaHoy' AND Estado = 'PERMISO'");
if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $permisosHoy = $fila['total'];
}

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM tb_asistencia WHERE Fecha = '$fechaHoy' AND HorasExtras > '00:00:00'");
if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $horasExtrasHoy = $fila['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Asistencia</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f6f8;
    color: #333;
}

.contenedor {
    display: flex;
    min-height: 100vh;
}

.contenido {
    flex: 1;
    padding: 30px;
}

.encabezado {
    margin-bottom: 25px;
}

.encabezado h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.encabezado p {
    color: #666;
}

.tarjetas {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.tarjeta {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.tarjeta h3 {
    font-size: 15px;
    color: #666;
    margin-bottom: 12px;
}

.tarjeta .numero {
    font-size: 32px;
    font-weight: bold;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.panel h2 {
    margin-bottom: 15px;
}

.panel p {
    color: #666;
    line-height: 1.6;
}

@media (max-width: 900px) {
    .tarjetas {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .contenedor {
        display: block;
    }

    .menu {
        width: 100%;
        min-height: auto;
    }

    .tarjetas {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="contenedor">

    <?php include "includes/menu.php"; ?>

    <main class="contenido">

        <div class="encabezado">
            <h1>Dashboard</h1>
            <p>Resumen del control de asistencia</p>
        </div>

        <div class="tarjetas">

            <div class="tarjeta">
                <h3>Total de empleados</h3>
                <div class="numero">
                    <?php echo $totalEmpleados; ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Asistencias de hoy</h3>
                <div class="numero">
                    <?php echo $asistenciasHoy; ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Permisos de hoy</h3>
                <div class="numero">
                    <?php echo $permisosHoy; ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Horas extras de hoy</h3>
                <div class="numero">
                    <?php echo $horasExtrasHoy; ?>
                </div>
            </div>

        </div>

        <div class="panel">
            <h2>Bienvenido al sistema</h2>

            <p>
                Desde este sistema puedes administrar los trabajadores,
                registrar asistencias, gestionar permisos y consultar
                reportes de asistencia.
            </p>
        </div>

    </main>

</div>

</body>
</html>
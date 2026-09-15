<?php
$modulo = "reportes";
require_once "../config/conexion.php";

$CodEmpleado = $_GET["CodEmpleado"] ?? "";
$FechaDesde = $_GET["FechaDesde"] ?? date("Y-m-01");
$FechaHasta = $_GET["FechaHasta"] ?? date("Y-m-d");

$datos = [];

$sql = "
    SELECT
        a.Fecha,
        a.CodEmpleado,
        CONCAT(e.Nombres, ' ', e.Apellidos) AS Trabajador,
        a.HoraEntrada,
        a.HoraSalida,
        a.Tardanza,
        a.HorasExtras,
        a.Estado,
        p.HoraInicio AS HoraPermisoInicio,
        p.HoraFin AS HoraPermisoFin,
        p.Motivo
    FROM tb_asistencia a
    INNER JOIN tb_empleado e
        ON a.CodEmpleado = e.CodEmpleado
    LEFT JOIN tb_permiso p
        ON a.CodEmpleado = p.CodEmpleado
        AND a.Fecha = p.Fecha
    WHERE a.Fecha BETWEEN ? AND ?
";

if ($CodEmpleado != "") {
    $sql .= " AND a.CodEmpleado = ?";
}

$sql .= " ORDER BY a.Fecha DESC, e.Apellidos, e.Nombres";

$stmt = $conexion->prepare($sql);

if ($CodEmpleado != "") {

    $stmt->bind_param(
        "sss",
        $FechaDesde,
        $FechaHasta,
        $CodEmpleado
    );

} else {

    $stmt->bind_param(
        "ss",
        $FechaDesde,
        $FechaHasta
    );
}

$stmt->execute();

$resultado = $stmt->get_result();

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

/*
 * RESUMEN
 */

$totalPresentes = 0;
$totalPermisos = 0;
$totalFaltas = 0;
$totalTardanzas = 0;
$totalHorasExtras = 0;
$totalTiempoPermiso = 0;

foreach ($datos as $fila) {

    if ($fila["Estado"] == "PRESENTE") {
        $totalPresentes++;
    }

    if ($fila["Estado"] == "PERMISO") {
        $totalPermisos++;
    }

    if ($fila["Estado"] == "FALTA") {
        $totalFaltas++;
    }

    if ($fila["Tardanza"] != "00:00:00") {
        $totalTardanzas++;
    }

    /*
     * Convertir horas extras a minutos
     */

    if (!empty($fila["HorasExtras"])) {

        $partes = explode(":", $fila["HorasExtras"]);

        $totalHorasExtras +=
            ($partes[0] * 60) +
            $partes[1];
    }

    /*
     * Calcular tiempo de permiso
     */

    if (
        $fila["Estado"] == "PERMISO" &&
        !empty($fila["HoraPermisoInicio"]) &&
        !empty($fila["HoraPermisoFin"])
    ) {

        $inicio = explode(":", $fila["HoraPermisoInicio"]);
        $fin = explode(":", $fila["HoraPermisoFin"]);

        $minInicio =
            ($inicio[0] * 60) +
            $inicio[1];

        $minFin =
            ($fin[0] * 60) +
            $fin[1];

        $totalTiempoPermiso +=
            $minFin - $minInicio;
    }
}

$horasExtrasHoras =
    floor($totalHorasExtras / 60);

$horasExtrasMinutos =
    $totalHorasExtras % 60;

$totalHorasExtrasTexto = sprintf(
    "%02d:%02d",
    $horasExtrasHoras,
    $horasExtrasMinutos
);

$permisoHoras =
    floor($totalTiempoPermiso / 60);

$permisoMinutos =
    $totalTiempoPermiso % 60;

$totalTiempoPermisoTexto = sprintf(
    "%02d:%02d",
    $permisoHoras,
    $permisoMinutos
);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reportes de Asistencia</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #333;
        }

        .contenedor-principal {
            display: flex;
            min-height: 100vh;
        }

        .contenido {
            flex: 1;
            padding: 30px;
            min-width: 0;
        }

        .contenedor {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .titulo {
            background: #1f2937;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }

        .titulo h1 {
            margin: 0;
            font-size: 24px;
        }

        .titulo p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }

        .filtros {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 25px;
        }

        .campo {
            display: flex;
            flex-direction: column;
        }

        .campo label {
            font-weight: bold;
            margin-bottom: 7px;
        }

        .campo select,
        .campo input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .boton {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            background: #1f2937;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover {
            background: #374151;
        }

        .resumen {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .tarjeta {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            background: #f8fafc;
        }

        .tarjeta span {
            display: block;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .tarjeta strong {
            font-size: 22px;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #1f2937;
            color: white;
        }

        tr:hover {
            background: #f8fafc;
        }

        .sin-datos {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        @media (max-width: 900px) {

            .contenedor-principal {
                display: block;
            }

            .contenido {
                padding: 20px;
            }

            .filtros {
                grid-template-columns: 1fr;
            }

            .resumen {
                grid-template-columns: 1fr 1fr;
            }

        }

    </style>

</head>

<body>

<div class="contenedor-principal">

    <?php include "../includes/menu.php"; ?>

    <main class="contenido">

        <div class="contenedor">

            <div class="titulo">

                <h1>Reportes de Asistencia</h1>

                <p>
                    Consulta de asistencia, permisos y horas extras
                </p>

            </div>

            <div class="panel">

                <form method="GET">

                    <div class="filtros">

                        <div class="campo">

                            <label>Trabajador</label>

                            <select name="CodEmpleado">

                                <option value="">
                                    Todos los trabajadores
                                </option>

                                <?php

                                $empleados = $conexion->query("
                                    SELECT
                                        CodEmpleado,
                                        Nombres,
                                        Apellidos
                                    FROM tb_empleado
                                    ORDER BY Apellidos, Nombres
                                ");

                                while ($empleado = $empleados->fetch_assoc()) {

                                    $seleccionado =
                                        ($CodEmpleado ==
                                        $empleado["CodEmpleado"])
                                        ? "selected"
                                        : "";

                                    echo '<option value="' .
                                        $empleado["CodEmpleado"] .
                                        '" ' .
                                        $seleccionado .
                                        '>';

                                    echo $empleado["Nombres"] .
                                        " " .
                                        $empleado["Apellidos"];

                                    echo "</option>";
                                }

                                ?>

                            </select>

                        </div>

                        <div class="campo">

                            <label>Desde</label>

                            <input
                                type="date"
                                name="FechaDesde"
                                value="<?php echo $FechaDesde; ?>"
                            >

                        </div>

                        <div class="campo">

                            <label>Hasta</label>

                            <input
                                type="date"
                                name="FechaHasta"
                                value="<?php echo $FechaHasta; ?>"
                            >

                        </div>

                        <button
                            type="submit"
                            class="boton"
                        >
                            BUSCAR
                        </button>

                    </div>

                </form>

                <div class="resumen">

                    <div class="tarjeta">

                        <span>Presentes</span>

                        <strong>
                            <?php echo $totalPresentes; ?>
                        </strong>

                    </div>

                    <div class="tarjeta">

                        <span>Permisos</span>

                        <strong>
                            <?php echo $totalPermisos; ?>
                        </strong>

                    </div>

                    <div class="tarjeta">

                        <span>Faltas</span>

                        <strong>
                            <?php echo $totalFaltas; ?>
                        </strong>

                    </div>

                    <div class="tarjeta">

                        <span>Tardanzas</span>

                        <strong>
                            <?php echo $totalTardanzas; ?>
                        </strong>

                    </div>

                    <div class="tarjeta">

                        <span>Horas extras</span>

                        <strong>
                            <?php echo $totalHorasExtrasTexto; ?>
                        </strong>

                    </div>

                </div>

                <div class="tabla-contenedor">

                    <table>

                        <thead>

                            <tr>

                                <th>Fecha</th>
                                <th>Trabajador</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Estado</th>
                                <th>Tardanza</th>
                                <th>Horas extras</th>
                                <th>Tiempo permiso</th>
                                <th>Motivo</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (count($datos) > 0): ?>

                            <?php foreach ($datos as $fila): ?>

                                <?php

                                $tiempoPermiso = "00:00";

                                if (
                                    $fila["Estado"] == "PERMISO" &&
                                    !empty($fila["HoraPermisoInicio"]) &&
                                    !empty($fila["HoraPermisoFin"])
                                ) {

                                    $inicio = explode(
                                        ":",
                                        $fila["HoraPermisoInicio"]
                                    );

                                    $fin = explode(
                                        ":",
                                        $fila["HoraPermisoFin"]
                                    );

                                    $minInicio =
                                        ($inicio[0] * 60) +
                                        $inicio[1];

                                    $minFin =
                                        ($fin[0] * 60) +
                                        $fin[1];

                                    $diferencia =
                                        $minFin - $minInicio;

                                    $horas =
                                        floor($diferencia / 60);

                                    $minutos =
                                        $diferencia % 60;

                                    $tiempoPermiso =
                                        sprintf(
                                            "%02d:%02d",
                                            $horas,
                                            $minutos
                                        );
                                }

                                ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo date(
                                            "d/m/Y",
                                            strtotime($fila["Fecha"])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $fila["Trabajador"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $fila["HoraEntrada"]
                                            ? substr(
                                                $fila["HoraEntrada"],
                                                0,
                                                5
                                            )
                                            : "-";
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $fila["HoraSalida"]
                                            ? substr(
                                                $fila["HoraSalida"],
                                                0,
                                                5
                                            )
                                            : "-";
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $fila["Estado"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo substr(
                                            $fila["Tardanza"],
                                            0,
                                            5
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo substr(
                                            $fila["HorasExtras"],
                                            0,
                                            5
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $tiempoPermiso;
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $fila["Motivo"]
                                            ? htmlspecialchars(
                                                $fila["Motivo"]
                                            )
                                            : "-";
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="sin-datos"
                                >
                                    No existen registros
                                    para el período seleccionado.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>
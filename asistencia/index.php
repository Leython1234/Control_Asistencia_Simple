<?php
$modulo = "asistencia";
require_once "../config/conexion.php";

$mensaje = "";
$error = "";

$horaEntradaNormal = "08:00";
$horaSalidaNormal = "18:20";

$editar = false;
$registroEditar = null;

/* ==============================
   FUNCION PARA CALCULAR HORAS
   ============================== */

function calcularHorasExtras($Fecha, $HoraEntrada, $HoraSalida, $horaEntradaNormal, $horaSalidaNormal)
{
    if ($HoraSalida == "") {
        return "00:00:00";
    }

    $diaSemana = date("N", strtotime($Fecha));

    $horaReal = explode(":", $HoraSalida);

    $minutosReal =
        ($horaReal[0] * 60) +
        $horaReal[1];

    /*
       LUNES A VIERNES
       Las horas extras comienzan después de las 18:20.
    */

    if ($diaSemana >= 1 && $diaSemana <= 5) {

        $horaBase = explode(":", $horaSalidaNormal);

        $minutosBase =
            ($horaBase[0] * 60) +
            $horaBase[1];

        if ($minutosReal <= $minutosBase) {
            return "00:00:00";
        }

        $diferencia =
            $minutosReal - $minutosBase;
    }

    /*
       SABADO
       Todo el tiempo trabajado es hora extra.
       Se toma como inicio las 08:00.
    */

    elseif ($diaSemana == 6) {

        if ($HoraEntrada == "") {
            $HoraEntrada = $horaEntradaNormal;
        }

        $horaInicio = explode(":", $HoraEntrada);

        $minutosInicio =
            ($horaInicio[0] * 60) +
            $horaInicio[1];

        if ($minutosReal <= $minutosInicio) {
            return "00:00:00";
        }

        $diferencia =
            $minutosReal - $minutosInicio;
    }

    /*
       DOMINGO
       Por ahora no se consideran horas extras.
    */

    else {

        return "00:00:00";
    }

    $horas = floor($diferencia / 60);
    $minutos = $diferencia % 60;

    return sprintf(
        "%02d:%02d:00",
        $horas,
        $minutos
    );
}

/* ==============================
   GUARDAR / EDITAR ASISTENCIA
   ============================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = $_POST["accion"];

    /* ==============================
       EDITAR ASISTENCIA
       ============================== */

    if ($accion == "editar") {

        $CodAsistencia = $_POST["CodAsistencia"];
        $CodEmpleado = $_POST["CodEmpleado"];
        $Fecha = $_POST["Fecha"];
        $HoraEntrada = $_POST["HoraEntrada"];
        $HoraSalida = $_POST["HoraSalida"];
        $Tardanza = $_POST["Tardanza"];
        $Estado = $_POST["Estado"];
        $Observacion = $_POST["Observacion"];

        /* Verificar que no exista otra asistencia
           del mismo trabajador en la misma fecha */

        $consulta = $conexion->prepare("
            SELECT CodAsistencia
            FROM tb_asistencia
            WHERE CodEmpleado = ?
            AND Fecha = ?
            AND CodAsistencia <> ?
        ");

        $consulta->bind_param(
            "ssi",
            $CodEmpleado,
            $Fecha,
            $CodAsistencia
        );

        $consulta->execute();
        $resultado = $consulta->get_result();

        if ($resultado->num_rows > 0) {

            $error = "El trabajador ya tiene otra asistencia registrada para esa fecha.";

        } else {

            /* Calcular horas extras automáticamente */

            $HorasExtras = calcularHorasExtras(
                $Fecha,
                $HoraEntrada,
                $HoraSalida,
                $horaEntradaNormal,
                $horaSalidaNormal
            );

            /* Si es falta, no debe tener horas */

            if ($Estado == "FALTA") {

                $HoraEntrada = "";
                $HoraSalida = "";
                $Tardanza = "00:00:00";
                $HorasExtras = "00:00:00";
            }

            /* PERMISO se conserva como estado,
               pero la modificación del permiso
               debe hacerse desde el módulo Permisos. */

            if ($Estado == "PERMISO") {

                $error = "Los registros con estado PERMISO deben modificarse desde el módulo Permisos.";

            } else {

                $actualizar = $conexion->prepare("
                    UPDATE tb_asistencia
                    SET
                        CodEmpleado = ?,
                        Fecha = ?,
                        HoraEntrada = NULLIF(?, ''),
                        HoraSalida = NULLIF(?, ''),
                        Tardanza = ?,
                        HorasExtras = ?,
                        Estado = ?,
                        Observacion = ?
                    WHERE CodAsistencia = ?
                ");

                $actualizar->bind_param(
                    "ssssssssi",
                    $CodEmpleado,
                    $Fecha,
                    $HoraEntrada,
                    $HoraSalida,
                    $Tardanza,
                    $HorasExtras,
                    $Estado,
                    $Observacion,
                    $CodAsistencia
                );

                if ($actualizar->execute()) {

                    $mensaje =
                        "Asistencia modificada correctamente. " .
                        "Horas extras: " . $HorasExtras;

                } else {

                    $error = "No se pudo modificar la asistencia.";
                }
            }
        }
    }

    /* ==============================
       ASISTENCIA NORMAL
       ============================== */

    if ($accion == "normal") {

        $CodEmpleado = $_POST["CodEmpleado"];
        $Fecha = $_POST["Fecha"];

        $consulta = $conexion->prepare("
            SELECT CodAsistencia, Estado
            FROM tb_asistencia
            WHERE CodEmpleado = ? AND Fecha = ?
        ");

        $consulta->bind_param(
            "ss",
            $CodEmpleado,
            $Fecha
        );

        $consulta->execute();

        $resultado = $consulta->get_result();

        if ($resultado->num_rows > 0) {

            $error = "El trabajador ya tiene una asistencia registrada para esa fecha.";

        } else {

            /*
               La asistencia normal corresponde
               a lunes a viernes.

               Para sábado se debe utilizar
               el registro de horas extras.
            */

            $diaSemana = date("N", strtotime($Fecha));

            if ($diaSemana == 6) {

                $error =
                    "El sábado no tiene jornada normal. " .
                    "Debe registrar la salida mediante HORAS EXTRAS.";

            } elseif ($diaSemana == 7) {

                $error =
                    "El domingo no está habilitado para registrar asistencia normal.";

            } else {

                $CodHorario = 1;
                $HoraEntrada = "08:00:00";
                $HoraSalida = "18:20:00";
                $Tardanza = "00:00:00";
                $HorasExtras = "00:00:00";
                $Estado = "PRESENTE";
                $Observacion = "";

                $insertar = $conexion->prepare("
                    INSERT INTO tb_asistencia
                    (
                        CodEmpleado,
                        CodHorario,
                        Fecha,
                        HoraEntrada,
                        HoraSalida,
                        Tardanza,
                        HorasExtras,
                        Estado,
                        Observacion
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insertar->bind_param(
                    "sisssssss",
                    $CodEmpleado,
                    $CodHorario,
                    $Fecha,
                    $HoraEntrada,
                    $HoraSalida,
                    $Tardanza,
                    $HorasExtras,
                    $Estado,
                    $Observacion
                );

                if ($insertar->execute()) {

                    $mensaje = "Asistencia normal registrada correctamente.";

                } else {

                    $error = "No se pudo registrar la asistencia.";
                }
            }
        }
    }

    /* ==============================
       HORAS EXTRAS
       ============================== */

    if ($accion == "extras") {

        $CodEmpleado = $_POST["CodEmpleado"];
        $Fecha = $_POST["Fecha"];
        $HoraSalidaExtra = $_POST["HoraSalidaExtra"];

        $diaSemana = date("N", strtotime($Fecha));

        $consulta = $conexion->prepare("
            SELECT CodAsistencia, Estado, HoraEntrada
            FROM tb_asistencia
            WHERE CodEmpleado = ? AND Fecha = ?
        ");

        $consulta->bind_param(
            "ss",
            $CodEmpleado,
            $Fecha
        );

        $consulta->execute();

        $resultado = $consulta->get_result();

        if ($HoraSalidaExtra == "") {

            $error = "Debe ingresar la hora real de salida.";

        } elseif ($diaSemana == 7) {

            $error =
                "El domingo no está habilitado para registrar horas extras.";

        } elseif (
            $diaSemana >= 1 &&
            $diaSemana <= 5 &&
            $HoraSalidaExtra <= $horaSalidaNormal
        ) {

            /*
               LUNES A VIERNES
               La salida debe ser después de 18:20.
            */

            $error =
                "De lunes a viernes, la hora de salida debe ser mayor a las 18:20.";

        } elseif (
            $diaSemana == 6 &&
            $HoraSalidaExtra <= $horaEntradaNormal
        ) {

            /*
               SABADO
               La salida debe ser después de la entrada.
            */

            $error =
                "El sábado, la hora de salida debe ser mayor a las 08:00.";

        } elseif ($resultado->num_rows > 0) {

            $asistencia = $resultado->fetch_assoc();

            if ($asistencia["Estado"] == "PERMISO") {

                $error =
                    "Este trabajador tiene un permiso registrado para esa fecha.";

            } else {

                $CodAsistencia =
                    $asistencia["CodAsistencia"];

                /*
                   Si es sábado, utilizamos la entrada
                   registrada. Si no existe, usamos 08:00.
                */

                $HoraEntrada = $asistencia["HoraEntrada"];

                if ($HoraEntrada == "" || $HoraEntrada == null) {

                    $HoraEntrada =
                        $horaEntradaNormal . ":00";
                }

                $HorasExtras = calcularHorasExtras(
                    $Fecha,
                    $HoraEntrada,
                    $HoraSalidaExtra,
                    $horaEntradaNormal,
                    $horaSalidaNormal
                );

                $HoraSalida =
                    $HoraSalidaExtra . ":00";

                $actualizar = $conexion->prepare("
                    UPDATE tb_asistencia
                    SET
                        HoraSalida = ?,
                        HorasExtras = ?
                    WHERE CodAsistencia = ?
                ");

                $actualizar->bind_param(
                    "ssi",
                    $HoraSalida,
                    $HorasExtras,
                    $CodAsistencia
                );

                if ($actualizar->execute()) {

                    $mensaje =
                        "Salida con horas extras registrada correctamente. " .
                        "Horas extras: " . $HorasExtras;

                } else {

                    $error =
                        "No se pudo actualizar la salida.";
                }
            }

        } else {

            /*
               NO EXISTE ASISTENCIA.

               Para lunes a viernes:
               Entrada = 08:00
               Extras = salida - 18:20

               Para sábado:
               Entrada = 08:00
               Extras = salida - 08:00
            */

            $CodHorario = 1;
            $HoraEntrada = "08:00:00";
            $HoraSalida = $HoraSalidaExtra . ":00";
            $Tardanza = "00:00:00";
            $Estado = "PRESENTE";
            $Observacion = "";

            $HorasExtras = calcularHorasExtras(
                $Fecha,
                $HoraEntrada,
                $HoraSalidaExtra,
                $horaEntradaNormal,
                $horaSalidaNormal
            );

            $insertar = $conexion->prepare("
                INSERT INTO tb_asistencia
                (
                    CodEmpleado,
                    CodHorario,
                    Fecha,
                    HoraEntrada,
                    HoraSalida,
                    Tardanza,
                    HorasExtras,
                    Estado,
                    Observacion
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertar->bind_param(
                "sisssssss",
                $CodEmpleado,
                $CodHorario,
                $Fecha,
                $HoraEntrada,
                $HoraSalida,
                $Tardanza,
                $HorasExtras,
                $Estado,
                $Observacion
            );

            if ($insertar->execute()) {

                $mensaje =
                    "Asistencia registrada correctamente. " .
                    "Horas extras: " . $HorasExtras;

            } else {

                $error = "No se pudo registrar la asistencia.";
            }
        }
    }
}

/* ==============================
   CARGAR REGISTRO PARA EDITAR
   ============================== */

if (isset($_GET["editar"])) {

    $CodAsistenciaEditar = $_GET["editar"];

    $consultaEditar = $conexion->prepare("
        SELECT
            CodAsistencia,
            CodEmpleado,
            Fecha,
            HoraEntrada,
            HoraSalida,
            Tardanza,
            HorasExtras,
            Estado,
            Observacion
        FROM tb_asistencia
        WHERE CodAsistencia = ?
    ");

    $consultaEditar->bind_param(
        "i",
        $CodAsistenciaEditar
    );

    $consultaEditar->execute();

    $resultadoEditar = $consultaEditar->get_result();

    if ($resultadoEditar->num_rows > 0) {

        $registroEditar = $resultadoEditar->fetch_assoc();
        $editar = true;

    } else {

        $error = "No se encontró la asistencia.";
    }
}

/* ==============================
   LISTAR ASISTENCIAS
   ============================== */

$listaAsistencias = $conexion->query("
    SELECT
        a.CodAsistencia,
        a.CodEmpleado,
        e.Nombres,
        e.Apellidos,
        a.Fecha,
        a.HoraEntrada,
        a.HoraSalida,
        a.Tardanza,
        a.HorasExtras,
        a.Estado,
        a.Observacion
    FROM tb_asistencia a
    INNER JOIN tb_empleado e
        ON a.CodEmpleado = e.CodEmpleado
    ORDER BY a.Fecha DESC, e.Apellidos, e.Nombres
");

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Asistencia - Control de Asistencia</title>

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
    margin-bottom: 20px;
}

.titulo h1 {
    margin: 0;
    font-size: 28px;
}

.titulo p {
    margin: 6px 0 0;
    color: #666;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.titulo-panel {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
}

.fila {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
}

.campo label {
    font-weight: bold;
    margin-bottom: 7px;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

.horario {
    background: #f1f5f9;
    border: 1px solid #d1d5db;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.horario strong {
    font-size: 16px;
}

.botones {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.boton {
    padding: 13px;
    border: none;
    border-radius: 5px;
    background: #1f2937;
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
}

.boton:hover {
    background: #374151;
}

.boton-extra {
    background: #4b5563;
}

.boton-extra:hover {
    background: #374151;
}

.boton-cancelar {
    background: #6b7280;
}

.boton-cancelar:hover {
    background: #4b5563;
}

.boton-editar {
    background: #374151;
    padding: 8px 12px;
    font-size: 12px;
}

.boton-editar:hover {
    background: #1f2937;
}

.mensaje {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.extra {
    display: none;
    margin-top: 20px;
    padding: 15px;
    background: #f8fafc;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

table th {
    background: #1f2937;
    color: white;
    padding: 12px 8px;
    text-align: left;
}

table td {
    padding: 10px 8px;
    border-bottom: 1px solid #ddd;
}

table tr:hover {
    background: #f8fafc;
}

.estado {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}

.estado-presente {
    background: #dcfce7;
    color: #166534;
}

.estado-falta {
    background: #fee2e2;
    color: #991b1b;
}

.estado-permiso {
    background: #fef3c7;
    color: #92400e;
}

.acciones {
    white-space: nowrap;
}

.aviso-edicion {
    background: #f1f5f9;
    border: 1px solid #d1d5db;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
    color: #374151;
}

.aviso-sabado {
    background: #f8fafc;
    border: 1px solid #d1d5db;
    padding: 12px;
    margin-top: 15px;
    border-radius: 5px;
    color: #374151;
    font-size: 13px;
}

@media (max-width: 700px) {

    .contenedor-principal {
        display: block;
    }

    .fila,
    .botones {
        grid-template-columns: 1fr;
    }

    .contenido {
        padding: 20px;
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

                <h1>Asistencia</h1>

                <p>
                    Registro y control de asistencia diaria de trabajadores
                </p>

            </div>

            <?php if ($mensaje != ""): ?>

                <div class="mensaje">
                    <?php echo $mensaje; ?>
                </div>

            <?php endif; ?>

            <?php if ($error != ""): ?>

                <div class="error">
                    <?php echo $error; ?>
                </div>

            <?php endif; ?>


            <?php if ($editar && $registroEditar): ?>

                <div class="panel">

                    <h2 class="titulo-panel">
                        Modificar asistencia
                    </h2>

                    <div class="aviso-edicion">

                        Está modificando el registro de asistencia
                        N.° <?php echo $registroEditar["CodAsistencia"]; ?>.

                        Las horas extras se calcularán automáticamente
                        según el día y la hora de salida.

                    </div>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="editar"
                        >

                        <input
                            type="hidden"
                            name="CodAsistencia"
                            value="<?php echo $registroEditar["CodAsistencia"]; ?>"
                        >

                        <div class="fila">

                            <div class="campo">

                                <label>Trabajador</label>

                                <select
                                    name="CodEmpleado"
                                    required
                                >

                                    <option value="">
                                        Seleccione un trabajador
                                    </option>

                                    <?php

                                    $consultaEmpleados = $conexion->query("
                                        SELECT
                                            CodEmpleado,
                                            Nombres,
                                            Apellidos
                                        FROM tb_empleado
                                        ORDER BY Apellidos, Nombres
                                    ");

                                    while ($empleado = $consultaEmpleados->fetch_assoc()):

                                    ?>

                                        <option
                                            value="<?php echo $empleado["CodEmpleado"]; ?>"
                                            <?php
                                            if (
                                                $empleado["CodEmpleado"] ==
                                                $registroEditar["CodEmpleado"]
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            <?php
                                            echo $empleado["Nombres"] .
                                                " " .
                                                $empleado["Apellidos"];
                                            ?>

                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>

                            <div class="campo">

                                <label>Fecha</label>

                                <input
                                    type="date"
                                    name="Fecha"
                                    value="<?php echo $registroEditar["Fecha"]; ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="fila">

                            <div class="campo">

                                <label>Hora de entrada</label>

                                <input
                                    type="time"
                                    name="HoraEntrada"
                                    value="<?php echo $registroEditar["HoraEntrada"] ? substr($registroEditar["HoraEntrada"], 0, 5) : ""; ?>"
                                >

                            </div>

                            <div class="campo">

                                <label>Hora de salida</label>

                                <input
                                    type="time"
                                    name="HoraSalida"
                                    value="<?php echo $registroEditar["HoraSalida"] ? substr($registroEditar["HoraSalida"], 0, 5) : ""; ?>"
                                >

                            </div>

                        </div>

                        <div class="fila">

                            <div class="campo">

                                <label>Estado</label>

                                <select
                                    name="Estado"
                                    id="Estado"
                                    required
                                    onchange="controlarEstado()"
                                >

                                    <option
                                        value="PRESENTE"
                                        <?php
                                        echo ($registroEditar["Estado"] == "PRESENTE")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        PRESENTE
                                    </option>

                                    <option
                                        value="FALTA"
                                        <?php
                                        echo ($registroEditar["Estado"] == "FALTA")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        FALTA
                                    </option>

                                    <option
                                        value="PERMISO"
                                        <?php
                                        echo ($registroEditar["Estado"] == "PERMISO")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        PERMISO
                                    </option>

                                </select>

                            </div>

                            <div class="campo">

                                <label>Tardanza</label>

                                <input
                                    type="time"
                                    name="Tardanza"
                                    step="1"
                                    value="<?php echo $registroEditar["Tardanza"]; ?>"
                                >

                            </div>

                        </div>

                        <div
                            class="campo"
                            style="margin-bottom:20px;"
                        >

                            <label>Observación</label>

                            <input
                                type="text"
                                name="Observacion"
                                maxlength="255"
                                value="<?php echo htmlspecialchars($registroEditar["Observacion"] ?? ""); ?>"
                                placeholder="Ingrese una observación"
                            >

                        </div>

                        <div class="botones">

                            <button
                                type="submit"
                                class="boton"
                            >
                                GUARDAR CAMBIOS
                            </button>

                            <a
                                href="index.php"
                                class="boton boton-cancelar"
                            >
                                CANCELAR
                            </a>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <?php if (!$editar): ?>

                <div class="panel">

                    <h2 class="titulo-panel">
                        Registrar asistencia
                    </h2>

                    <form method="POST">

                        <div class="fila">

                            <div class="campo">

                                <label>Trabajador</label>

                                <select
                                    name="CodEmpleado"
                                    required
                                >

                                    <option value="">
                                        Seleccione un trabajador
                                    </option>

                                    <?php

                                    $consulta = $conexion->query("
                                        SELECT
                                            CodEmpleado,
                                            Nombres,
                                            Apellidos
                                        FROM tb_empleado
                                        ORDER BY Apellidos, Nombres
                                    ");

                                    while ($empleado = $consulta->fetch_assoc()):

                                    ?>

                                        <option
                                            value="<?php echo $empleado["CodEmpleado"]; ?>"
                                        >

                                            <?php
                                            echo $empleado["Nombres"] .
                                                " " .
                                                $empleado["Apellidos"];
                                            ?>

                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>

                            <div class="campo">

                                <label>Fecha</label>

                                <input
                                    type="date"
                                    name="Fecha"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="horario">

                            <strong>Horario establecido</strong>

                            <br><br>

                            <span id="textoHorario">
                                Lunes a viernes:
                                Entrada <strong>08:00</strong>
                                &nbsp;&nbsp;&nbsp;
                                Salida <strong>18:20</strong>
                            </span>

                            <div
                                id="avisoSabado"
                                class="aviso-sabado"
                                style="display:none;"
                            >
                                <strong>Sábado:</strong>
                                no existe jornada normal.
                                Todo el tiempo trabajado se considera
                                hora extra desde la hora de entrada.
                            </div>

                        </div>

                        <div class="botones">

                            <button
                                type="submit"
                                name="accion"
                                value="normal"
                                class="boton"
                            >
                                REGISTRAR ASISTENCIA NORMAL
                            </button>

                            <button
                                type="button"
                                class="boton boton-extra"
                                onclick="mostrarExtras()"
                            >
                                SALIDA + HORAS EXTRAS
                            </button>

                        </div>

                        <div
                            class="extra"
                            id="panelExtras"
                        >

                            <div class="campo">

                                <label>
                                    Hora real de salida
                                </label>

                                <input
                                    type="time"
                                    name="HoraSalidaExtra"
                                    id="HoraSalidaExtra"
                                    min="18:21"
                                >

                            </div>

                            <br>

                            <button
                                type="submit"
                                name="accion"
                                value="extras"
                                class="boton"
                            >
                                REGISTRAR SALIDA CON HORAS EXTRAS
                            </button>

                            <div
                                id="textoExtra"
                                class="aviso-sabado"
                            >
                                De lunes a viernes, las horas extras
                                empiezan después de las 18:20.
                            </div>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <div class="panel">

                <h2 class="titulo-panel">
                    Asistencias registradas
                </h2>

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

                                <th>Observación</th>

                                <th>Acción</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if ($listaAsistencias->num_rows > 0): ?>

                            <?php while ($asistencia = $listaAsistencias->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo date(
                                            "d/m/Y",
                                            strtotime($asistencia["Fecha"])
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $asistencia["Nombres"] .
                                            " " .
                                            $asistencia["Apellidos"]
                                        );
                                        ?>

                                    </td>

                                    <td>
                                        <?php
                                        echo $asistencia["HoraEntrada"]
                                            ? substr($asistencia["HoraEntrada"], 0, 5)
                                            : "-";
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $asistencia["HoraSalida"]
                                            ? substr($asistencia["HoraSalida"], 0, 5)
                                            : "-";
                                        ?>
                                    </td>

                                    <td>

                                        <?php if ($asistencia["Estado"] == "PRESENTE"): ?>

                                            <span class="estado estado-presente">
                                                PRESENTE
                                            </span>

                                        <?php elseif ($asistencia["Estado"] == "FALTA"): ?>

                                            <span class="estado estado-falta">
                                                FALTA
                                            </span>

                                        <?php else: ?>

                                            <span class="estado estado-permiso">
                                                PERMISO
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php
                                        echo $asistencia["Tardanza"] ?? "00:00:00";
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $asistencia["HorasExtras"] ?? "00:00:00";
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $asistencia["Observacion"] ?? ""
                                        );
                                        ?>
                                    </td>

                                    <td class="acciones">

                                        <?php if ($asistencia["Estado"] != "PERMISO"): ?>

                                            <a
                                                href="index.php?editar=<?php echo $asistencia["CodAsistencia"]; ?>"
                                                class="boton boton-editar"
                                            >
                                                EDITAR
                                            </a>

                                        <?php else: ?>

                                            <span>
                                                Desde Permisos
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="9"
                                    style="text-align:center;"
                                >
                                    No existen asistencias registradas.
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


<script>

function mostrarExtras() {

    const panel =
        document.getElementById("panelExtras");

    if (panel.style.display === "block") {

        panel.style.display = "none";

    } else {

        panel.style.display = "block";

    }

    actualizarReglasFecha();
}


function controlarEstado() {

    const estado =
        document.getElementById("Estado");

    if (!estado) {
        return;
    }

    if (estado.value === "FALTA") {

        alert(
            "Si selecciona FALTA, las horas de entrada, salida, tardanza y horas extras se establecerán en cero o quedarán vacías."
        );
    }

    if (estado.value === "PERMISO") {

        alert(
            "Los permisos deben modificarse desde el módulo Permisos."
        );

    }

}


function actualizarReglasFecha() {

    const fecha =
        document.querySelector('input[name="Fecha"]');

    const salida =
        document.getElementById("HoraSalidaExtra");

    const avisoSabado =
        document.getElementById("avisoSabado");

    const textoExtra =
        document.getElementById("textoExtra");

    if (!fecha) {
        return;
    }

    if (fecha.value == "") {
        return;
    }

    const fechaSeleccionada =
        new Date(fecha.value + "T00:00:00");

    const diaSemana =
        fechaSeleccionada.getDay();

    /*
       JavaScript:
       0 = Domingo
       1 = Lunes
       ...
       6 = Sábado
    */

    if (diaSemana === 6) {

        if (salida) {
            salida.min = "08:01";
        }

        if (avisoSabado) {
            avisoSabado.style.display = "block";
        }

        if (textoExtra) {
            textoExtra.innerHTML =
                "<strong>Sábado:</strong> todo el tiempo trabajado " +
                "se considera hora extra desde la hora de entrada.";
        }

    } else {

        if (salida) {
            salida.min = "18:21";
        }

        if (avisoSabado) {
            avisoSabado.style.display = "none";
        }

        if (textoExtra) {
            textoExtra.innerHTML =
                "De lunes a viernes, las horas extras " +
                "empiezan después de las 18:20.";
        }
    }
}


/* Detectar cambio de fecha */

const campoFecha =
    document.querySelector('input[name="Fecha"]');

if (campoFecha) {

    campoFecha.addEventListener(
        "change",
        actualizarReglasFecha
    );

    actualizarReglasFecha();
}

</script>

</body>

</html>
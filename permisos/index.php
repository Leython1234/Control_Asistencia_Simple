<?php
$modulo = "permisos";
require_once "../config/conexion.php";

$horaFinJornada = "18:20";
$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $CodEmpleado = $_POST["CodEmpleado"];
    $Fecha = $_POST["Fecha"];
    $HoraEntrada = $_POST["HoraEntrada"];
    $HoraSalidaPermiso = $_POST["HoraSalidaPermiso"];
    $Motivo = trim($_POST["Motivo"]);
    $Observacion = trim($_POST["Observacion"]);

    $consulta = $conexion->prepare("
        SELECT CodPermiso
        FROM tb_permiso
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

        $error = "El trabajador ya tiene un permiso registrado para esa fecha.";

    } else {

        if ($HoraSalidaPermiso >= $horaFinJornada) {

            $error = "La hora de salida por permiso debe ser menor a las 18:20.";

        } else {

            $consultaAsistencia = $conexion->prepare("
                SELECT CodAsistencia
                FROM tb_asistencia
                WHERE CodEmpleado = ? AND Fecha = ?
            ");

            $consultaAsistencia->bind_param(
                "ss",
                $CodEmpleado,
                $Fecha
            );

            $consultaAsistencia->execute();
            $resultadoAsistencia = $consultaAsistencia->get_result();

            $CodHorario = 1;

            if ($resultadoAsistencia->num_rows > 0) {

                $asistencia = $resultadoAsistencia->fetch_assoc();

                $CodAsistencia = $asistencia["CodAsistencia"];

                $actualizar = $conexion->prepare("
                    UPDATE tb_asistencia
                    SET
                        CodHorario = ?,
                        HoraEntrada = ?,
                        HoraSalida = ?,
                        Estado = 'PERMISO',
                        Observacion = ?
                    WHERE CodAsistencia = ?
                ");

                $actualizar->bind_param(
                    "isssi",
                    $CodHorario,
                    $HoraEntrada,
                    $HoraSalidaPermiso,
                    $Motivo,
                    $CodAsistencia
                );

                $actualizar->execute();

            } else {

                $Tardanza = "00:00:00";
                $HorasExtras = "00:00:00";
                $Estado = "PERMISO";

                $insertarAsistencia = $conexion->prepare("
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

                $insertarAsistencia->bind_param(
                    "sisssssss",
                    $CodEmpleado,
                    $CodHorario,
                    $Fecha,
                    $HoraEntrada,
                    $HoraSalidaPermiso,
                    $Tardanza,
                    $HorasExtras,
                    $Estado,
                    $Motivo
                );

                $insertarAsistencia->execute();
            }

            $TipoPermiso = "HORAS";
            $EstadoPermiso = "APROBADO";

            $insertarPermiso = $conexion->prepare("
                INSERT INTO tb_permiso
                (
                    CodEmpleado,
                    Fecha,
                    HoraInicio,
                    HoraFin,
                    Motivo,
                    TipoPermiso,
                    Estado,
                    Observacion
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertarPermiso->bind_param(
                "ssssssss",
                $CodEmpleado,
                $Fecha,
                $HoraSalidaPermiso,
                $horaFinJornada,
                $Motivo,
                $TipoPermiso,
                $EstadoPermiso,
                $Observacion
            );

            if ($insertarPermiso->execute()) {

                $mensaje = "Permiso registrado correctamente.";

            } else {

                $error = "Ocurrió un error al registrar el permiso.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Permisos - Control de Asistencia</title>

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
        }

        .contenedor {
            width: 100%;
            max-width: 900px;
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
        }

        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 18px;
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
        .campo select,
        .campo textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .campo textarea {
            resize: vertical;
            min-height: 80px;
        }

        .campo input[readonly] {
            background: #eee;
        }

        .boton {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #1f2937;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover {
            background: #374151;
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

        @media (max-width: 700px) {

            .contenedor-principal {
                display: block;
            }

            .fila {
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

                <h1>Permisos</h1>

                <p>
                    Registro de asistencia para trabajadores con permiso
                </p>

            </div>

            <div class="panel">

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

                                while ($empleado = $consulta->fetch_assoc()) {

                                    echo '<option value="' .
                                        $empleado['CodEmpleado'] .
                                        '">';

                                    echo $empleado['Nombres'] .
                                        ' ' .
                                        $empleado['Apellidos'];

                                    echo '</option>';
                                }

                                ?>

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

                    <div class="fila">

                        <div class="campo">

                            <label>Hora de entrada</label>

                            <input
                                type="time"
                                id="HoraEntrada"
                                name="HoraEntrada"
                                value="08:00"
                                required
                            >

                        </div>

                        <div class="campo">

                            <label>Hora de salida por permiso</label>

                            <input
                                type="time"
                                id="HoraSalidaPermiso"
                                name="HoraSalidaPermiso"
                                value="13:00"
                                required
                            >

                        </div>

                    </div>

                    <div class="fila">

                        <div class="campo">

                            <label>Fin de jornada</label>

                            <input
                                type="time"
                                id="HoraFinJornada"
                                value="<?php echo $horaFinJornada; ?>"
                                readonly
                            >

                        </div>

                        <div class="campo">

                            <label>Tiempo de permiso</label>

                            <input
                                type="text"
                                id="TiempoPermiso"
                                value="05:20"
                                readonly
                            >

                        </div>

                    </div>

                    <div
                        class="campo"
                        style="margin-bottom: 18px;"
                    >

                        <label>Motivo</label>

                        <input
                            type="text"
                            name="Motivo"
                            placeholder="Ejemplo: Malestar"
                            required
                        >

                    </div>

                    <div
                        class="campo"
                        style="margin-bottom: 20px;"
                    >

                        <label>Observación</label>

                        <textarea
                            name="Observacion"
                            placeholder="Ingrese alguna observación..."
                        ></textarea>

                    </div>

                    <button
                        type="submit"
                        class="boton"
                    >
                        REGISTRAR PERMISO
                    </button>

                </form>

            </div>

        </div>

    </main>

</div>

<script>

function calcularTiempoPermiso() {

    const horaSalida =
        document.getElementById("HoraSalidaPermiso").value;

    const horaFin =
        document.getElementById("HoraFinJornada").value;

    if (horaSalida === "" || horaFin === "") {

        document.getElementById("TiempoPermiso").value =
            "00:00";

        return;
    }

    const salida = horaSalida.split(":");
    const fin = horaFin.split(":");

    const minutosSalida =
        parseInt(salida[0]) * 60 +
        parseInt(salida[1]);

    const minutosFin =
        parseInt(fin[0]) * 60 +
        parseInt(fin[1]);

    let diferencia =
        minutosFin - minutosSalida;

    if (diferencia < 0) {

        document.getElementById("TiempoPermiso").value =
            "Hora inválida";

        return;
    }

    const horas =
        Math.floor(diferencia / 60);

    const minutos =
        diferencia % 60;

    const horasFormateadas =
        String(horas).padStart(2, "0");

    const minutosFormateados =
        String(minutos).padStart(2, "0");

    document.getElementById("TiempoPermiso").value =
        horasFormateadas + ":" +
        minutosFormateados;
}

document
    .getElementById("HoraSalidaPermiso")
    .addEventListener(
        "input",
        calcularTiempoPermiso
    );

calcularTiempoPermiso();

</script>

</body>

</html>
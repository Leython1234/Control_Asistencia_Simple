<?php
$modulo = "empleados";
require_once "../config/conexion.php";

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $CodEmpleado = trim($_POST["CodEmpleado"]);
    $DNI = trim($_POST["DNI"]);
    $Nombres = trim($_POST["Nombres"]);
    $Apellidos = trim($_POST["Apellidos"]);
    $Cargo = trim($_POST["Cargo"]);
    $Observaciones = trim($_POST["Observaciones"]);

    if ($CodEmpleado == "" || $DNI == "" || $Nombres == "" || $Apellidos == "" || $Cargo == "") {

        $mensaje = "Complete todos los campos obligatorios.";
        $tipo = "error";

    } else {

        $sql = "INSERT INTO tb_empleado
                (CodEmpleado, DNI, Nombres, Apellidos, Cargo, Observaciones)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "ssssss",
            $CodEmpleado,
            $DNI,
            $Nombres,
            $Apellidos,
            $Cargo,
            $Observaciones
        );

        if ($stmt->execute()) {
            $mensaje = "Empleado registrado correctamente.";
            $tipo = "exito";
        } else {
            $mensaje = "No se pudo registrar el empleado. Verifique que el código o DNI no estén repetidos.";
            $tipo = "error";
        }

        $stmt->close();
    }
}

$sql = "SELECT * FROM tb_empleado ORDER BY Apellidos ASC, Nombres ASC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - Control de Asistencia</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
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

        .titulo {
            margin-bottom: 20px;
        }

        .titulo h1 {
            margin: 0;
            font-size: 28px;
        }

        .titulo p {
            margin-top: 6px;
            color: #666;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .campo {
            display: flex;
            flex-direction: column;
        }

        .campo label {
            margin-bottom: 6px;
            font-weight: bold;
        }

        .campo input,
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

        .campo-completo {
            grid-column: 1 / 3;
        }

        .botones {
            grid-column: 1 / 3;
            margin-top: 5px;
        }

        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-guardar {
            background: #0d6efd;
            color: white;
        }

        .btn-guardar:hover {
            background: #0b5ed7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }

        .mensaje {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .exito {
            background: #d1e7dd;
            color: #0f5132;
        }

        .error {
            background: #f8d7da;
            color: #842029;
        }

        @media (max-width: 768px) {

            .contenedor-principal {
                display: block;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo-completo,
            .botones {
                grid-column: 1;
            }

            .panel {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>

<body>

<div class="contenedor-principal">

    <?php include "../includes/menu.php"; ?>

    <main class="contenido">

        <div class="titulo">
            <h1>Empleados</h1>
            <p>Gestión de empleados</p>
        </div>

        <?php if ($mensaje != ""): ?>
            <div class="mensaje <?= $tipo ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="panel">

            <h2>Registrar empleado</h2>

            <form method="POST">

                <div class="formulario">

                    <div class="campo">
                        <label for="CodEmpleado">Código empleado *</label>
                        <input
                            type="text"
                            id="CodEmpleado"
                            name="CodEmpleado"
                            maxlength="7"
                            placeholder="EMP0001"
                            required>
                    </div>

                    <div class="campo">
                        <label for="DNI">DNI *</label>
                        <input
                            type="text"
                            id="DNI"
                            name="DNI"
                            maxlength="8"
                            placeholder="12345678"
                            required>
                    </div>

                    <div class="campo">
                        <label for="Nombres">Nombres *</label>
                        <input
                            type="text"
                            id="Nombres"
                            name="Nombres"
                            maxlength="50"
                            placeholder="Juan"
                            required>
                    </div>

                    <div class="campo">
                        <label for="Apellidos">Apellidos *</label>
                        <input
                            type="text"
                            id="Apellidos"
                            name="Apellidos"
                            maxlength="80"
                            placeholder="Pérez López"
                            required>
                    </div>

                    <div class="campo">
                        <label for="Cargo">Cargo *</label>
                        <input
                            type="text"
                            id="Cargo"
                            name="Cargo"
                            maxlength="60"
                            placeholder="Operario"
                            required>
                    </div>

                    <div class="campo campo-completo">
                        <label for="Observaciones">Observaciones</label>
                        <textarea
                            id="Observaciones"
                            name="Observaciones"
                            maxlength="255"
                            placeholder="Observaciones del trabajador"></textarea>
                    </div>

                    <div class="botones">
                        <button type="submit" class="btn btn-guardar">
                            Guardar empleado
                        </button>
                    </div>

                </div>

            </form>

        </div>

        <div class="panel">

            <h2>Empleados registrados</h2>

            <table>

                <thead>
                    <tr>
                        <th>Código</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Cargo</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($resultado->num_rows > 0): ?>

                    <?php while ($empleado = $resultado->fetch_assoc()): ?>

                        <tr>
                            <td><?= htmlspecialchars($empleado["CodEmpleado"]) ?></td>
                            <td><?= htmlspecialchars($empleado["DNI"]) ?></td>
                            <td><?= htmlspecialchars($empleado["Nombres"]) ?></td>
                            <td><?= htmlspecialchars($empleado["Apellidos"]) ?></td>
                            <td><?= htmlspecialchars($empleado["Cargo"]) ?></td>
                            <td><?= htmlspecialchars($empleado["Observaciones"] ?? "") ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No hay empleados registrados.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>
<?php
$base = "/control_a/";
?>

<aside class="menu">
    <div class="logo">
        CONTROL DE<br>ASISTENCIA
    </div>

    <nav>
        <a href="<?php echo $base; ?>" class="<?php echo ($modulo == 'inicio') ? 'activo' : ''; ?>">
            Inicio
        </a>

        <a href="<?php echo $base; ?>empleados/" class="<?php echo ($modulo == 'empleados') ? 'activo' : ''; ?>">
            Empleados
        </a>

        <a href="<?php echo $base; ?>asistencia/" class="<?php echo ($modulo == 'asistencia') ? 'activo' : ''; ?>">
            Asistencia
        </a>

        <a href="<?php echo $base; ?>permisos/" class="<?php echo ($modulo == 'permisos') ? 'activo' : ''; ?>">
            Permisos
        </a>

        <a href="<?php echo $base; ?>reportes/" class="<?php echo ($modulo == 'reportes') ? 'activo' : ''; ?>">
            Reportes
        </a>
    </nav>
</aside>

<style>
.menu {
    width: 230px;
    min-height: 100vh;
    background: #1f2937;
    color: white;
    padding: 25px 15px;
}

.logo {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    line-height: 1.4;
    margin-bottom: 35px;
}

.menu nav a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px 15px;
    margin-bottom: 8px;
    border-radius: 5px;
    transition: 0.2s;
}

.menu nav a:hover {
    background: #374151;
}

.menu nav a.activo {
    background: #4b5563;
    font-weight: bold;
}
</style>
<?php 
$host = "localhost";
$dbName = "desayunos_detalles";
$userName = "root";
$password = "admin";

$conexion = new mysqli($host, $userName, $password, $dbName);

if ($conexion->connect_error) {
    die("Error al conectar la base de datos: " . $conexion->connect_error);
}
?>
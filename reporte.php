<?php
include 'conexion.php';

// Respuesta con encabezado JSON
header('Content-Type: application/json');

// Clientes
$sqlClientes = "SELECT * FROM usuarios WHERE rol = 'cliente'";
$resultClientes = $conexion->query($sqlClientes);
$clientes = [];

while ($row = $resultClientes->fetch_assoc()) {
    $clientes[] = $row;
}

// Productos
$sqlProductos = "SELECT * FROM productos";
$resultProductos = $conexion->query($sqlProductos);
$productos = [];

while ($row = $resultProductos->fetch_assoc()) {
    $productos[] = $row;
}

// Pedidos
$sqlPedidos = "SELECT p.id, u.nombres, u.apellidos, p.fecha_pedido, p.estado, p.total 
               FROM pedidos p
               JOIN usuarios u ON p.usuario_id = u.id";
$resultPedidos = $conexion->query($sqlPedidos);
$pedidos = [];

while ($row = $resultPedidos->fetch_assoc()) {
    $pedidos[] = $row;
}

// Devolver todo junto
echo json_encode([
    'clientes' => $clientes,
    'productos' => $productos,
    'pedidos' => $pedidos
]);
?>
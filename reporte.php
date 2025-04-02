<?php
require_once 'conexion.php';

session_start();

// Verificar si es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$conexion = Conexion::conectar();

try {
    // Reporte de ventas por categoría
    $sqlVentas = "SELECT c.nombre AS categoria, SUM(dp.cantidad * dp.precio_unitario) AS total
                  FROM detalle_pedido dp
                  JOIN productos p ON dp.id_producto = p.id
                  JOIN categorias c ON p.id_categoria = c.id
                  GROUP BY c.nombre
                  ORDER BY total DESC";
    
    $stmtVentas = $conexion->prepare($sqlVentas);
    $stmtVentas->execute();
    $ventas = $stmtVentas->fetchAll();

    // Productos más vendidos
    $sqlProductos = "SELECT p.nombre, SUM(dp.cantidad) AS cantidad_vendida
                     FROM detalle_pedido dp
                     JOIN productos p ON dp.id_producto = p.id
                     GROUP BY p.nombre
                     ORDER BY cantidad_vendida DESC
                     LIMIT 5";
    
    $stmtProductos = $conexion->prepare($sqlProductos);
    $stmtProductos->execute();
    $productos = $stmtProductos->fetchAll();

} catch (PDOException $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Desayunos y Detalles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-reporte {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-reporte {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin/header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">Reportes de Ventas</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-reporte">
                    <div class="card-header bg-primary text-white">
                        <h3>Ventas por Categoría</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-reporte">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Total Ventas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas as $venta): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($venta['categoria']) ?></td>
                                        <td>$<?= number_format($venta['total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-reporte">
                    <div class="card-header bg-success text-white">
                        <h3>Productos Más Vendidos</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-reporte">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad Vendida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td><?= $producto['cantidad_vendida'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'conexion.php';

try {
    // Consulta SQL mejorada con JOIN para verificar stock
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, 
            IFNULL(p.imagen, 'img/placeholder.jpg') as imagen, 
            p.categoria, p.stock, p.destacado
            FROM productos p
            WHERE p.stock > 0
            ORDER BY p.destacado DESC, p.nombre ASC";
    
    $result = $conexion->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conexion->error);
    }

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $row['precio'] = floatval($row['precio']);
        $row['imagen'] = (!empty($row['imagen']) && file_exists($row['imagen'])) ? 
                         $row['imagen'] : 'img/placeholder.jpg';
        $productos[] = $row;
    }

    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'count' => count($productos)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'productos' => []
    ]);
} finally {
    $conexion->close();
}
?>
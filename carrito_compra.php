<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'conexion.php';
session_start();

try {
    // Obtener conteo de items
    if (isset($_GET['action']) && $_GET['action'] == 'count') {
        $count = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
        echo json_encode([
            'success' => true, 
            'count' => $count,
            'timestamp' => time() // Para evitar caché
        ]);
        exit;
    }

    // Obtener carrito completo con detalles
    if (isset($_GET['action']) && $_GET['action'] == 'get') {
        $carrito = [];
        
        if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
            // Obtener IDs de productos únicos
            $ids = array_unique(array_map('intval', array_column($_SESSION['carrito'], 'producto_id')));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Consulta preparada para obtener detalles de productos
            $sql = "SELECT id, nombre, precio, imagen, categoria, stock 
                    FROM productos 
                    WHERE id IN ($placeholders)";
            $stmt = $conexion->prepare($sql);
            
            if (count($ids) > 0) {
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                $stmt->execute();
                $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Combinar con datos del carrito
                foreach ($_SESSION['carrito'] as $item) {
                    $producto = current(array_filter($productos, fn($p) => $p['id'] == $item['producto_id']));
                    if ($producto) {
                        // Convertir tipos explícitamente
                        $precio = (float)$producto['precio'];
                        $cantidad = (int)$item['cantidad'];
                        
                        $carrito[] = [
                            'producto_id' => (int)$producto['id'],
                            'nombre' => $producto['nombre'],
                            'precio' => $precio,
                            'imagen' => $producto['imagen'] ?: 'img/placeholder.jpg',
                            'categoria' => $producto['categoria'],
                            'cantidad' => $cantidad,
                            'subtotal' => $precio * $cantidad,
                            'stock' => (int)$producto['stock']
                        ];
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'carrito' => $carrito,
            'timestamp' => time()
        ]);
        exit;
    }

    echo json_encode([
        'success' => false, 
        'error' => 'Acción no válida',
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
} finally {
    $conexion->close();
}
?>
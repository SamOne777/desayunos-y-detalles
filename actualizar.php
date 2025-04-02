<?php
require_once 'conexion.php';
require_once 'clases.php';

session_start();

// Verificar si es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $conexion = Conexion::conectar();
    
    $datosProducto = [
        'nombre' => $_POST['nombre'],
        'descripcion' => $_POST['descripcion'],
        'precio' => $_POST['precio'],
        'stock' => $_POST['stock'],
        'id_categoria' => $_POST['id_categoria']
    ];
    
    // Mantener la imagen actual si no se sube una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreImagen = uniqid() . '_' . basename($_FILES['imagen']['name']);
        $rutaDestino = 'img/productos/' . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $datosProducto['imagen'] = $rutaDestino;
        }
    }
    
    try {
        $producto = ProductoFactory::crear('desayuno', $conexion, $datosProducto);
        $producto->id = $_POST['id'];
        
        if ($producto->actualizar()) {
            $_SESSION['exito'] = "Producto actualizado correctamente";
        } else {
            $_SESSION['error'] = "Error al actualizar producto";
        }
        
        header('Location: admin/productos.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: admin/editar_producto.php?id='.$_POST['id']);
        exit();
    }
} else {
    header('Location: admin/productos.php');
    exit();
}
?>
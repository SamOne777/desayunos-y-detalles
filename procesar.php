<?php
require_once 'conexion.php';
require_once 'clases.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = Conexion::conectar();
    
    // Manejo de la imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreImagen = uniqid() . '_' . basename($_FILES['imagen']['name']);
        $rutaDestino = 'img/productos/' . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagen = $rutaDestino;
        }
    }

    $datosProducto = [
        'nombre' => $_POST['nombre'],
        'descripcion' => $_POST['descripcion'],
        'precio' => $_POST['precio'],
        'stock' => $_POST['stock'],
        'imagen' => $imagen,
        'id_categoria' => $_POST['id_categoria']
    ];
    
    try {
        if (isset($_POST['id'])) { // Actualización
            $producto = ProductoFactory::crear('desayuno', $conexion, $datosProducto);
            $producto->id = $_POST['id'];
            if ($producto->actualizar()) {
                $_SESSION['exito'] = "Producto actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar producto";
            }
        } else { // Registro
            $producto = ProductoFactory::crear('desayuno', $conexion, $datosProducto);
            $id = $producto->guardar();
            $_SESSION['exito'] = "Producto registrado correctamente";
        }
        
        header('Location: admin/productos.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . (isset($_POST['id']) ? 'admin/editar_producto.php?id='.$_POST['id'] : 'admin/nuevo_producto.php'));
        exit();
    }
}
?>
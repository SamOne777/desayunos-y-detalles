<?php
require_once 'conexion.php';
require_once 'usuarioabstracto.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = Conexion::conectar();
    
    $datosUsuario = [
        'nombre' => $_POST['nombre'],
        'email' => $_POST['email'],
        'direccion' => $_POST['direccion'],
        'telefono' => $_POST['telefono'],
        'password' => $_POST['password'],
        'rol' => $_POST['rol'] ?? 'cliente'
    ];
    
    try {
        $usuario = new Usuario($conexion, $datosUsuario);
        
        if (isset($_POST['id'])) { // Actualización
            $usuario->id = $_POST['id'];
            if ($usuario->actualizar()) {
                $_SESSION['exito'] = "Usuario actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar usuario";
            }
        } else { // Registro
            $id = $usuario->registrar();
            $_SESSION['exito'] = "Usuario registrado correctamente";
            
            // Autenticar si es registro de cliente
            if ($datosUsuario['rol'] === 'cliente') {
                $usuarioAutenticado = $usuario->autenticar();
                if ($usuarioAutenticado) {
                    $_SESSION['usuario'] = $usuarioAutenticado;
                }
            }
        }
        
        header('Location: ' . ($_POST['rol'] === 'admin' ? 'admin/usuarios.php' : 'catalogo.php'));
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . ($_POST['action'] === 'registro' ? 'registro.php' : 'admin/editar_usuario.php?id='.$_POST['id']));
        exit();
    }
}
?>
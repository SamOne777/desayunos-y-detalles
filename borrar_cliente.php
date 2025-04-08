<?php
include 'conexion.php';

header('Content-Type: application/json');

// Obtener lista de usuarios si se solicita
if (isset($_GET['listar'])) {
    $query = "SELECT nombres, apellidos, correo FROM usuarios WHERE rol = 'cliente'";
    $resultado = $conexion->query($query);
    $usuarios = [];

    while ($row = $resultado->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode($usuarios);
    exit;
}

// Eliminar usuario si se envió por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];

    // Verificar si el cliente existe
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? AND rol = 'cliente'");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Cliente no encontrado"]);
        exit;
    }

    // Eliminar cliente
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Cliente eliminado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar el cliente"]);
    }

    $stmt->close();
    exit;
}
?>
<?php
include 'conexion.php';

// -------------------------
// Obtener datos de un cliente
// -------------------------
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conexion->prepare("SELECT nombres, apellidos, documento, telefono, correo FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
    exit;
}

// -------------------------
// Obtener lista de clientes
// -------------------------
if (isset($_GET['usuarios']) && $_GET['usuarios'] == 1) {
    $query = "SELECT id, nombres, apellidos FROM usuarios WHERE rol = 'cliente'";
    $result = $conexion->query($query);
    $usuarios = [];

    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    echo json_encode($usuarios);
    exit;
}

// -------------------------
// Actualizar cliente
// -------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
    $documento = $_POST["documento"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $contrasena = !empty($_POST["contrasena"]) ? password_hash($_POST["contrasena"], PASSWORD_DEFAULT) : null;

    $sql = "UPDATE usuarios SET 
            nombres=?, apellidos=?, documento=?, telefono=?, correo=?" .
            ($contrasena ? ", contrasena=?" : "") . " WHERE id=?";
    
    $stmt = $conexion->prepare($sql);

    if ($contrasena) {
        $stmt->bind_param("ssssssi", $nombres, $apellidos, $documento, $telefono, $correo, $contrasena, $id);
    } else {
        $stmt->bind_param("sssssi", $nombres, $apellidos, $documento, $telefono, $correo, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Cliente actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al actualizar el cliente"]);
    }

    $stmt->close();
    exit;
}
?>
<?php
class ModuloUsuarios {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function listarUsuarios($rol = null) {
        $sql = "SELECT * FROM usuarios";
        $params = [];
        
        if ($rol) {
            $sql .= " WHERE rol = ?";
            $params[] = $rol;
        }
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerUsuario($id) {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM usuarios WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function buscarUsuarios($termino) {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM usuarios 
             WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ?"
        );
        $termino = "%$termino%";
        $stmt->execute([$termino, $termino, $termino]);
        return $stmt->fetchAll();
    }
}
?>
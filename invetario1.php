<?php
interface Inventario {
    public function verificarStock();
}

class DesayunoInventario implements Inventario {
    private $producto;
    private $conexion;

    public function __construct($conexion, $producto) {
        $this->conexion = $conexion;
        $this->producto = $producto;
    }

    public function verificarStock() {
        $stmt = $this->conexion->prepare(
            "SELECT stock FROM productos WHERE id = ?"
        );
        $stmt->execute([$this->producto->id]);
        $resultado = $stmt->fetch();
        
        return $resultado ? "Disponibles: " . $resultado['stock'] : "Producto no encontrado";
    }
}

class InventarioFactory {
    public static function crear($tipo, $conexion, $producto) {
        switch ($tipo) {
            case 'desayuno':
                return new DesayunoInventario($conexion, $producto);
            default:
                throw new Exception("Tipo de inventario no válido");
        }
    }
}

// Ejemplo de uso
require_once 'conexion.php';
$conexion = Conexion::conectar();

$producto = new stdClass();
$producto->id = 1; // ID del producto a verificar

$inventario = InventarioFactory::crear('desayuno', $conexion, $producto);
echo $inventario->verificarStock();
?>
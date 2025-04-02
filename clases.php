<?php
abstract class Producto {
    protected $id;
    protected $nombre;
    protected $descripcion;
    protected $precio;
    protected $stock;
    protected $imagen;
    protected $id_categoria;

    abstract public function guardar();
    abstract public function actualizar();
    abstract public function eliminar();
}

class Desayuno extends Producto {
    private $conexion;

    public function __construct($conexion, $datos = []) {
        $this->conexion = $conexion;
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public function guardar() {
        $stmt = $this->conexion->prepare(
            "INSERT INTO productos (nombre, descripcion, precio, stock, imagen, id_categoria) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->nombre,
            $this->descripcion,
            $this->precio,
            $this->stock,
            $this->imagen,
            $this->id_categoria
        ]);
        return $this->conexion->lastInsertId();
    }

    public function actualizar() {
        $stmt = $this->conexion->prepare(
            "UPDATE productos SET 
             nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, id_categoria = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $this->nombre,
            $this->descripcion,
            $this->precio,
            $this->stock,
            $this->imagen,
            $this->id_categoria,
            $this->id
        ]);
    }

    public function eliminar() {
        $stmt = $this->conexion->prepare(
            "DELETE FROM productos WHERE id = ?"
        );
        return $stmt->execute([$this->id]);
    }
}

// Factory Pattern para productos
class ProductoFactory {
    public static function crear($tipo, $conexion, $datos) {
        switch ($tipo) {
            case 'desayuno':
                return new Desayuno($conexion, $datos);
            default:
                throw new Exception("Tipo de producto no válido");
        }
    }
}
?>
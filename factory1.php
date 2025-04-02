<?php
interface ProductoInterface {
    public function getNombre();
    public function getPrecio();
    public function getCategoria();
}

class DesayunoSimple implements ProductoInterface {
    private $nombre;
    private $precio;
    private $categoria;

    public function __construct($nombre, $precio, $categoria) {
        $this->nombre = $nombre;
        $this->precio = $precio;
        $this->categoria = $categoria;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function getCategoria() {
        return $this->categoria;
    }
}

class DesayunoCombo implements ProductoInterface {
    private $productos = [];

    public function agregarProducto(ProductoInterface $producto) {
        $this->productos[] = $producto;
    }

    public function getNombre() {
        $nombres = [];
        foreach ($this->productos as $producto) {
            $nombres[] = $producto->getNombre();
        }
        return "Combo: " . implode(" + ", $nombres);
    }

    public function getPrecio() {
        $total = 0;
        foreach ($this->productos as $producto) {
            $total += $producto->getPrecio();
        }
        return $total * 0.9; // 10% de descuento en combos
    }

    public function getCategoria() {
        return "Combo";
    }
}

class ProductoFactory {
    public static function crear($tipo, $nombre = null, $precio = null, $categoria = null) {
        switch ($tipo) {
            case 'simple':
                return new DesayunoSimple($nombre, $precio, $categoria);
            case 'combo':
                return new DesayunoCombo();
            default:
                throw new Exception("Tipo de producto no válido");
        }
    }
}

// Ejemplo de uso
$cafe = ProductoFactory::crear('simple', 'Café Premium', 4.75, 'Bebidas');
$pan = ProductoFactory::crear('simple', 'Croissant', 3.25, 'Postres');

$comboDesayuno = ProductoFactory::crear('combo');
$comboDesayuno->agregarProducto($cafe);
$comboDesayuno->agregarProducto($pan);

echo $comboDesayuno->getNombre() . " - $" . $comboDesayuno->getPrecio();
?>
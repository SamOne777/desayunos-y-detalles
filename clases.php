<?php
// Clase abstracta Usuario base
abstract class Usuario {
    protected $nombres;
    protected $apellidos;
    protected $documento;
    protected $telefono;
    protected $correo;
    protected $contrasena;
    protected $rol;

    public function __construct($nombres, $apellidos, $documento, $telefono, $correo, $contrasena, $rol = 'cliente') {
        $this->nombres = $this->sanitizeInput($nombres);
        $this->apellidos = $this->sanitizeInput($apellidos);
        $this->documento = $this->sanitizeInput($documento);
        $this->telefono = $this->sanitizeInput($telefono);
        $this->correo = filter_var($correo, FILTER_SANITIZE_EMAIL);
        $this->contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        $this->rol = $rol;
    }

    protected function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    // Métodos getters
    public function getNombres() { return $this->nombres; }
    public function getApellidos() { return $this->apellidos; }
    public function getDocumento() { return $this->documento; }
    public function getTelefono() { return $this->telefono; }
    public function getCorreo() { return $this->correo; }
    public function getRol() { return $this->rol; }

    // Métodos abstractos
    abstract public function guardar($conexion);
}

// Clase Cliente
class Cliente extends Usuario {
    public function __construct($nombres, $apellidos, $documento, $telefono, $correo, $contrasena) {
        parent::__construct($nombres, $apellidos, $documento, $telefono, $correo, $contrasena, 'cliente');
    }

    public function guardar($conexion) {
        $sql = "INSERT INTO usuarios (nombres, apellidos, documento, telefono, correo, contrasena, rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssss", 
            $this->nombres, 
            $this->apellidos, 
            $this->documento, 
            $this->telefono, 
            $this->correo, 
            $this->contrasena,
            $this->rol);
        
        return $stmt->execute();
    }
}

// Clase Admin
class Admin extends Usuario {
    public function __construct($nombres, $apellidos, $documento, $telefono, $correo, $contrasena) {
        parent::__construct($nombres, $apellidos, $documento, $telefono, $correo, $contrasena, 'admin');
    }

    public function guardar($conexion) {
        $sql = "INSERT INTO usuarios (nombres, apellidos, documento, telefono, correo, contrasena, rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssss", 
            $this->nombres, 
            $this->apellidos, 
            $this->documento, 
            $this->telefono, 
            $this->correo, 
            $this->contrasena,
            $this->rol);
        
        return $stmt->execute();
    }
}

// Clase Producto
class Producto {
    private $id;
    private $nombre;
    private $descripcion;
    private $precio;
    private $categoria;
    private $imagen;
    private $stock;
    private $destacado;
    private $fecha_creacion;

    public function __construct($nombre, $descripcion, $precio, $categoria, $imagen = null, $stock = 0, $destacado = false, $id = null) {
        $this->id = $id;
        $this->nombre = $this->sanitizeInput($nombre);
        $this->descripcion = $this->sanitizeInput($descripcion);
        $this->precio = floatval($precio);
        $this->categoria = $this->sanitizeInput($categoria);
        $this->imagen = $imagen;
        $this->stock = intval($stock);
        $this->destacado = (bool)$destacado;
    }

    protected function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    // Métodos getters
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrecio() { return $this->precio; }
    public function getCategoria() { return $this->categoria; }
    public function getImagen() { return $this->imagen; }
    public function getStock() { return $this->stock; }
    public function isDestacado() { return $this->destacado; }

    // Métodos de persistencia
    public function guardar($conexion) {
        if ($this->id) {
            return $this->actualizar($conexion);
        } else {
            return $this->crear($conexion);
        }
    }

    private function crear($conexion) {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria, imagen, stock, destacado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdsiii", 
            $this->nombre, 
            $this->descripcion, 
            $this->precio, 
            $this->categoria, 
            $this->imagen, 
            $this->stock, 
            $this->destacado);
        
        if ($stmt->execute()) {
            $this->id = $conexion->insert_id;
            return true;
        }
        return false;
    }

    private function actualizar($conexion) {
        $sql = "UPDATE productos SET 
                nombre = ?, 
                descripcion = ?, 
                precio = ?, 
                categoria = ?, 
                imagen = ?, 
                stock = ?, 
                destacado = ? 
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdsiiii", 
            $this->nombre, 
            $this->descripcion, 
            $this->precio, 
            $this->categoria, 
            $this->imagen, 
            $this->stock, 
            $this->destacado,
            $this->id);
        
        return $stmt->execute();
    }

    public function eliminar($conexion) {
        $sql = "DELETE FROM productos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    // Métodos estáticos para consultas
    public static function obtenerPorId($conexion, $id) {
        $sql = "SELECT * FROM productos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return new self(
                $row['nombre'],
                $row['descripcion'],
                $row['precio'],
                $row['categoria'],
                $row['imagen'],
                $row['stock'],
                $row['destacado'],
                $row['id']
            );
        }
        return null;
    }

    public static function obtenerTodos($conexion, $destacados = false, $categoria = null, $limit = null) {
        $sql = "SELECT * FROM productos WHERE stock > 0";
        $params = [];
        $types = "";
        
        if ($destacados) {
            $sql .= " AND destacado = 1";
        }
        
        if ($categoria) {
            $sql .= " AND categoria = ?";
            $params[] = $categoria;
            $types .= "s";
        }
        
        $sql .= " ORDER BY destacado DESC, nombre ASC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";
        }
        
        $stmt = $conexion->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $productos = [];
        
        while ($row = $result->fetch_assoc()) {
            $productos[] = new self(
                $row['nombre'],
                $row['descripcion'],
                $row['precio'],
                $row['categoria'],
                $row['imagen'],
                $row['stock'],
                $row['destacado'],
                $row['id']
            );
        }
        
        return $productos;
    }
}

// Clase Carrito
class Carrito {
    public static function agregarProducto($producto_id, $cantidad = 1) {
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // Buscar si el producto ya está en el carrito
        $index = array_search($producto_id, array_column($_SESSION['carrito'], 'producto_id'));

        if ($index !== false) {
            // Actualizar cantidad si ya existe
            $_SESSION['carrito'][$index]['cantidad'] += $cantidad;
        } else {
            // Agregar nuevo item al carrito
            $_SESSION['carrito'][] = [
                'producto_id' => $producto_id,
                'cantidad' => $cantidad
            ];
        }
    }

    public static function actualizarProducto($producto_id, $cantidad) {
        if (!isset($_SESSION['carrito'])) {
            return false;
        }

        $index = array_search($producto_id, array_column($_SESSION['carrito'], 'producto_id'));

        if ($index !== false) {
            if ($cantidad > 0) {
                $_SESSION['carrito'][$index]['cantidad'] = $cantidad;
            } else {
                // Eliminar producto si cantidad es 0
                array_splice($_SESSION['carrito'], $index, 1);
            }
            return true;
        }
        return false;
    }

    public static function eliminarProducto($producto_id) {
        return self::actualizarProducto($producto_id, 0);
    }

    public static function obtenerProductos($conexion) {
        if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
            return [];
        }

        $ids = array_map('intval', array_column($_SESSION['carrito'], 'producto_id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $sql = "SELECT id, nombre, precio, imagen, categoria, stock 
                FROM productos 
                WHERE id IN ($placeholders)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Combinar con cantidades del carrito
        $items = [];
        foreach ($_SESSION['carrito'] as $item) {
            $producto = current(array_filter($productos, fn($p) => $p['id'] == $item['producto_id']));
            if ($producto) {
                $items[] = [
                    'producto_id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'imagen' => $producto['imagen'],
                    'categoria' => $producto['categoria'],
                    'stock' => $producto['stock'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $producto['precio'] * $item['cantidad']
                ];
            }
        }

        return $items;
    }

    public static function calcularTotal($conexion) {
        $items = self::obtenerProductos($conexion);
        return array_sum(array_column($items, 'subtotal'));
    }

    public static function contarProductos() {
        return isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
    }

    public static function vaciar() {
        unset($_SESSION['carrito']);
    }
}

// Clase Pedido
class Pedido {
    private $id;
    private $usuario_id;
    private $fecha_pedido;
    private $estado;
    private $total;
    private $direccion_entrega;
    private $instrucciones;

    public function __construct($usuario_id, $total, $direccion_entrega, $instrucciones = '', $estado = 'pendiente', $fecha_pedido = null, $id = null) {
        $this->id = $id;
        $this->usuario_id = $usuario_id;
        $this->total = floatval($total);
        $this->direccion_entrega = $this->sanitizeInput($direccion_entrega);
        $this->instrucciones = $this->sanitizeInput($instrucciones);
        $this->estado = in_array($estado, ['pendiente', 'preparando', 'enviado', 'entregado', 'cancelado']) ? $estado : 'pendiente';
        $this->fecha_pedido = $fecha_pedido ?: date('Y-m-d H:i:s');
    }

    protected function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    // Métodos getters
    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getFechaPedido() { return $this->fecha_pedido; }
    public function getEstado() { return $this->estado; }
    public function getTotal() { return $this->total; }
    public function getDireccionEntrega() { return $this->direccion_entrega; }
    public function getInstrucciones() { return $this->instrucciones; }

    // Métodos de persistencia
    public function guardar($conexion) {
        if ($this->id) {
            return $this->actualizar($conexion);
        } else {
            return $this->crear($conexion);
        }
    }

    private function crear($conexion) {
        $conexion->begin_transaction();

        try {
            // Insertar pedido
            $sql = "INSERT INTO pedidos (usuario_id, fecha_pedido, estado, total, direccion_entrega, instrucciones) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("issdss", 
                $this->usuario_id, 
                $this->fecha_pedido, 
                $this->estado, 
                $this->total, 
                $this->direccion_entrega, 
                $this->instrucciones);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear pedido: " . $conexion->error);
            }

            $this->id = $conexion->insert_id;

            // Insertar detalles del pedido desde el carrito
            $items = Carrito::obtenerProductos($conexion);
            foreach ($items as $item) {
                $sqlDetalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) 
                               VALUES (?, ?, ?, ?)";
                $stmtDetalle = $conexion->prepare($sqlDetalle);
                $stmtDetalle->bind_param("iiid", 
                    $this->id, 
                    $item['producto_id'], 
                    $item['cantidad'], 
                    $item['precio']);
                
                if (!$stmtDetalle->execute()) {
                    throw new Exception("Error al agregar detalle de pedido: " . $conexion->error);
                }

                // Actualizar stock
                $sqlStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmtStock = $conexion->prepare($sqlStock);
                $stmtStock->bind_param("ii", $item['cantidad'], $item['producto_id']);
                
                if (!$stmtStock->execute()) {
                    throw new Exception("Error al actualizar stock: " . $conexion->error);
                }
            }

            $conexion->commit();
            Carrito::vaciar();
            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log($e->getMessage());
            return false;
        }
    }

    private function actualizar($conexion) {
        $sql = "UPDATE pedidos SET 
                estado = ?,
                direccion_entrega = ?,
                instrucciones = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssi", 
            $this->estado, 
            $this->direccion_entrega, 
            $this->instrucciones,
            $this->id);
        
        return $stmt->execute();
    }

    // Métodos estáticos para consultas
    public static function obtenerPorId($conexion, $id) {
        $sql = "SELECT * FROM pedidos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return new self(
                $row['usuario_id'],
                $row['total'],
                $row['direccion_entrega'],
                $row['instrucciones'],
                $row['estado'],
                $row['fecha_pedido'],
                $row['id']
            );
        }
        return null;
    }

    public static function obtenerPorUsuario($conexion, $usuario_id) {
        $sql = "SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha_pedido DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pedidos = [];
        
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = new self(
                $row['usuario_id'],
                $row['total'],
                $row['direccion_entrega'],
                $row['instrucciones'],
                $row['estado'],
                $row['fecha_pedido'],
                $row['id']
            );
        }
        
        return $pedidos;
    }

    public static function obtenerDetalles($conexion, $pedido_id) {
        $sql = "SELECT dp.*, p.nombre, p.imagen 
                FROM detalle_pedido dp
                JOIN productos p ON dp.producto_id = p.id
                WHERE dp.pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Función para crear usuarios
function crearUsuario($rol, $nombres, $apellidos, $documento, $telefono, $correo, $contrasena) {
    switch ($rol) {
        case 'cliente':
            return new Cliente($nombres, $apellidos, $documento, $telefono, $correo, $contrasena);
        case 'admin':
            return new Admin($nombres, $apellidos, $documento, $telefono, $correo, $contrasena);
        default:
            throw new Exception("Rol no válido");
    }
}
?>
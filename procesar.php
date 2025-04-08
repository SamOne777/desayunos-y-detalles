<?php
include 'conexion.php';
include 'clases.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar registro de cliente
    if (isset($_POST['registro_cliente'])) {
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $documento = trim($_POST['documento']);
        $telefono = trim($_POST['telefono']);
        $correo = trim($_POST['correo']);
        $contrasena = $_POST['contrasena'];

        try {
            // Validación de campos obligatorios
            if (empty($nombres) || empty($apellidos) || empty($documento) || empty($telefono) || empty($correo) || empty($contrasena)) {
                throw new Exception("Todos los campos son obligatorios");
            }
        
            // Validación de formato de correo
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El correo electrónico no es válido");
            }
        
            // Creación y registro del cliente
            $cliente = new Cliente($nombres, $apellidos, $documento, $telefono, $correo, $contrasena);
            
            if ($cliente->guardar($conexion)) {
                // Respuesta de éxito en texto plano
                echo "Cliente registrado exitosamente";
            } else {
                throw new Exception("Error al registrar el cliente. Puede que el correo o documento ya existan.");
            }
        } catch (Exception $e) {
            // Respuesta de error en texto plano
            echo "Error: " . $e->getMessage();
        }
        exit;
    }

    // Procesar nuevo producto (admin)
    if (isset($_POST['nuevo_producto'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $categoria = trim($_POST['categoria']);
        $imagen = $_POST['imagen'] ?? null;
        $stock = intval($_POST['stock'] ?? 0);
        $destacado = isset($_POST['destacado']) ? 1 : 0;

        try {
            // Validación de campos obligatorios
            if (empty($nombre) || empty($descripcion) || empty($categoria) || $precio <= 0) {
                throw new Exception("Complete todos los campos obligatorios y asegure un precio válido");
            }
        
            // Creación y guardado del producto
            $producto = new Producto($nombre, $descripcion, $precio, $categoria, $imagen, $stock, $destacado);
            if ($producto->guardar($conexion)) {
                // Respuesta de éxito en texto plano
                echo "Producto agregado exitosamente";
            } else {
                throw new Exception("Error al agregar el producto");
            }
        } catch (Exception $e) {
            // Respuesta de error en texto plano
            echo "Error: " . $e->getMessage();
        }
        exit;
    }

    // Procesar agregar al carrito
    if (isset($_POST['agregar_carrito'])) {
        try {
            $producto_id = intval($_POST['producto_id']);
            $cantidad = intval($_POST['cantidad'] ?? 1);

            if ($producto_id <= 0 || $cantidad <= 0) {
                throw new Exception("Datos del producto inválidos");
            }

            // Verificar stock disponible
            $sql = "SELECT stock FROM productos WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc()['stock'];

            if ($stock < $cantidad) {
                throw new Exception("No hay suficiente stock disponible");
            }

            // Inicializar carrito si no existe
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

            echo json_encode([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'count' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Procesar actualizar carrito
    if (isset($_POST['actualizar_carrito'])) {
        try {
            $carrito = $_POST['carrito'] ?? [];

            if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
                throw new Exception("El carrito está vacío");
            }

            // Actualizar cantidades
            foreach ($carrito as $item) {
                $producto_id = intval($item['producto_id']);
                $cantidad = intval($item['cantidad']);

                // Buscar producto en el carrito
                $index = array_search($producto_id, array_column($_SESSION['carrito'], 'producto_id'));

                if ($index !== false) {
                    if ($cantidad > 0) {
                        // Actualizar cantidad
                        $_SESSION['carrito'][$index]['cantidad'] = $cantidad;
                    } else {
                        // Eliminar producto si cantidad es 0
                        array_splice($_SESSION['carrito'], $index, 1);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Carrito actualizado',
                'count' => array_sum(array_column($_SESSION['carrito'], 'cantidad'))
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Procesar realizar pedido
    if (isset($_POST['realizar_pedido'])) {
        try {
            if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
                throw new Exception("El carrito está vacío");
            }

            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Debe iniciar sesión para realizar un pedido");
            }

            $direccion = trim($_POST['direccion'] ?? '');
            if (empty($direccion)) {
                throw new Exception("La dirección de entrega es obligatoria");
            }

            $conexion->begin_transaction();

            // Calcular total
            $total = 0;
            $items = [];
            
            // Obtener detalles completos de los productos
            $ids = array_map('intval', array_column($_SESSION['carrito'], 'producto_id'));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id IN ($placeholders) FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Preparar items y verificar stock
            foreach ($_SESSION['carrito'] as $item) {
                $producto = current(array_filter($productos, fn($p) => $p['id'] == $item['producto_id']));
                
                if (!$producto) {
                    throw new Exception("Producto no encontrado: ID {$item['producto_id']}");
                }
                
                if ($producto['stock'] < $item['cantidad']) {
                    throw new Exception("No hay suficiente stock para: {$producto['nombre']}");
                }
                
                $items[] = [
                    'producto' => $producto,
                    'cantidad' => $item['cantidad']
                ];
                
                $total += $producto['precio'] * $item['cantidad'];
            }

            // Crear pedido
            $usuario_id = $_SESSION['usuario_id'];
            $instrucciones = trim($_POST['instrucciones'] ?? '');

            $sqlPedido = "INSERT INTO pedidos (usuario_id, total, direccion_entrega, instrucciones) 
                          VALUES (?, ?, ?, ?)";
            $stmtPedido = $conexion->prepare($sqlPedido);
            $stmtPedido->bind_param("idss", $usuario_id, $total, $direccion, $instrucciones);
            $stmtPedido->execute();
            $pedido_id = $conexion->insert_id;

            // Agregar detalles del pedido y actualizar stock
            foreach ($items as $item) {
                $sqlDetalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) 
                               VALUES (?, ?, ?, ?)";
                $stmtDetalle = $conexion->prepare($sqlDetalle);
                $stmtDetalle->bind_param("iiid", $pedido_id, $item['producto']['id'], $item['cantidad'], $item['producto']['precio']);
                $stmtDetalle->execute();

                $sqlStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmtStock = $conexion->prepare($sqlStock);
                $stmtStock->bind_param("ii", $item['cantidad'], $item['producto']['id']);
                $stmtStock->execute();
            }

            $conexion->commit();
            unset($_SESSION['carrito']);
            
            echo json_encode([
                'success' => true,
                'message' => "¡Pedido realizado con éxito! Número de pedido: $pedido_id",
                'pedido_id' => $pedido_id
            ]);

        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode([
                'success' => false,
                'error' => "Error al realizar el pedido: " . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Si no es POST, devolver error
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
?>
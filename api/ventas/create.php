<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Validar que se han enviado los datos necesarios
if (!isset($data['cliente_id']) || !isset($data['caja_id']) || !isset($data['productos']) || empty($data['productos'])) {
    jsonResponse(['success' => false, 'message' => 'Datos incompletos']);
}

// Validar que el cliente existe
$clienteQuery = "SELECT id FROM cliente WHERE id = ?";
$cliente = fetchOne($clienteQuery, [$data['cliente_id']], "i");

if (!$cliente) {
    jsonResponse(['success' => false, 'message' => 'El cliente seleccionado no existe']);
}

// Validar que la caja existe y está abierta
$cajaQuery = "SELECT id FROM caja WHERE id = ? AND estaAbierta = 1";
$caja = fetchOne($cajaQuery, [$data['caja_id']], "i");

if (!$caja) {
    jsonResponse(['success' => false, 'message' => 'La caja seleccionada no existe o no está abierta']);
}

// Validar los productos y calcular el total
$montoTotal = 0;
$detallesVenta = [];

$conn = getConnection();
$conn->begin_transaction();

try {
    // Verificar cada producto
    foreach ($data['productos'] as $producto) {
        // Validar datos del producto
        if (!isset($producto['id']) || !isset($producto['cantidad']) || !isset($producto['precioUnitario'])) {
            throw new Exception('Datos de producto incompletos');
        }
        
        // Validar que el producto existe y tiene stock suficiente
        $productoQuery = "SELECT p.id, p.nombre, i.cantidad as stockDisponible 
                          FROM producto p 
                          JOIN inventario i ON p.id = i.producto_id 
                          WHERE p.id = ?";
        $stmt = $conn->prepare($productoQuery);
        $stmt->bind_param("i", $producto['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $productoDb = $result->fetch_assoc();
        
        if (!$productoDb) {
            throw new Exception('El producto con ID ' . $producto['id'] . ' no existe');
        }
        
        if ($productoDb['stockDisponible'] < $producto['cantidad']) {
            throw new Exception('Stock insuficiente para el producto ' . $productoDb['nombre']);
        }
        
        // Calcular subtotal y agregar al total
        $subtotal = $producto['cantidad'] * $producto['precioUnitario'];
        $montoTotal += $subtotal;
        
        // Agregar a la lista de detalles
        $detallesVenta[] = [
            'producto_id' => $producto['id'],
            'cantidad' => $producto['cantidad'],
            'precioUnitario' => $producto['precioUnitario'],
            'subtotal' => $subtotal
        ];
        
        // Actualizar inventario
        $nuevoStock = $productoDb['stockDisponible'] - $producto['cantidad'];
        $updateInventarioQuery = "UPDATE inventario SET cantidad = ? WHERE producto_id = ?";
        $stmt = $conn->prepare($updateInventarioQuery);
        $stmt->bind_param("ii", $nuevoStock, $producto['id']);
        $stmt->execute();
    }
    
    // Crear la venta
    $insertVentaQuery = "INSERT INTO venta (fecha, cliente_id, montoTotal, estadoVenta, caja_id) 
                         VALUES (NOW(), ?, ?, 'Completada', ?)";
    $stmt = $conn->prepare($insertVentaQuery);
    $stmt->bind_param("idi", $data['cliente_id'], $montoTotal, $data['caja_id']);
    $stmt->execute();
    
    $ventaId = $conn->insert_id;
    
    // Crear los detalles de la venta
    foreach ($detallesVenta as $detalle) {
        $insertDetalleQuery = "INSERT INTO detalleventa (venta_id, producto_id, cantidad, precioUnitario, subtotal) 
                              VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertDetalleQuery);
        $stmt->bind_param("iidd", $ventaId, $detalle['producto_id'], $detalle['cantidad'], $detalle['precioUnitario'], $detalle['subtotal']);
        $stmt->execute();
    }
    
    // Crear ticket
    $empleadoId = $_SESSION['user_id'];
    $insertTicketQuery = "INSERT INTO ticket (venta_id, fechaEmision, montoTotal, esFiscal, empleado_id) 
                         VALUES (?, NOW(), ?, 0, ?)";
    $stmt = $conn->prepare($insertTicketQuery);
    $stmt->bind_param("idi", $ventaId, $montoTotal, $empleadoId);
    $stmt->execute();
    
    // Registrar movimiento de caja
    $insertMovimientoQuery = "INSERT INTO movimientocaja (caja_id, tipo, monto, concepto, fechaHora, finalizado) 
                             VALUES (?, 'ingreso', ?, ?, NOW(), 1)";
    $stmt = $conn->prepare($insertMovimientoQuery);
    $concepto = "Venta #$ventaId";
    $stmt->bind_param("ids", $data['caja_id'], $montoTotal, $concepto);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    // Respuesta exitosa
    jsonResponse([
        'success' => true, 
        'message' => 'Venta registrada correctamente', 
        'venta_id' => $ventaId,
        'monto_total' => $montoTotal
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    jsonResponse([
        'success' => false, 
        'message' => 'Error al registrar la venta: ' . $e->getMessage()
    ]);
}
?> 
<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/ventas/index.php');
}

$id = (int) $_GET['id'];

// Verificar que la venta existe y está pendiente
$sql = "SELECT id, estadoVenta, montoTotal, caja_id FROM venta WHERE id = ?";
$venta = fetchOne($sql, [$id], "i");

if (!$venta || $venta['estadoVenta'] !== 'Pendiente') {
    $_SESSION['error'] = "La venta no existe o no está en estado pendiente.";
    redirect('/views/ventas/index.php');
}

// Iniciar transacción
$conn = getConnection();
$conn->begin_transaction();

try {
    // Actualizar el estado de la venta a Completada
    $updateVentaQuery = "UPDATE venta SET estadoVenta = 'Completada' WHERE id = ?";
    $stmt = $conn->prepare($updateVentaQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Crear ticket para la venta
    $empleadoId = $_SESSION['user_id'];
    $montoTotal = $venta['montoTotal'];
    $insertTicketQuery = "INSERT INTO ticket (venta_id, fechaEmision, montoTotal, esFiscal, empleado_id) 
                         VALUES (?, NOW(), ?, 0, ?)";
    $stmt = $conn->prepare($insertTicketQuery);
    $stmt->bind_param("idi", $id, $montoTotal, $empleadoId);
    $stmt->execute();
    
    // Registrar movimiento de caja
    $cajaId = $venta['caja_id'];
    $insertMovimientoQuery = "INSERT INTO movimientocaja (caja_id, tipo, monto, concepto, fechaHora, finalizado) 
                             VALUES (?, 'ingreso', ?, ?, NOW(), 1)";
    $stmt = $conn->prepare($insertMovimientoQuery);
    $concepto = "Venta #$id";
    $stmt->bind_param("ids", $cajaId, $montoTotal, $concepto);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    $_SESSION['success'] = "La venta ha sido completada exitosamente.";
    redirect('/views/ventas/view.php?id=' . $id);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    $_SESSION['error'] = "Error al completar la venta: " . $e->getMessage();
    redirect('/views/ventas/view.php?id=' . $id);
}
?> 
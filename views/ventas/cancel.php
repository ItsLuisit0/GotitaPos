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
$sql = "SELECT id, estadoVenta FROM venta WHERE id = ?";
$venta = fetchOne($sql, [$id], "i");

if (!$venta || $venta['estadoVenta'] !== 'Pendiente') {
    $_SESSION['error'] = "La venta no existe o no está en estado pendiente.";
    redirect('/views/ventas/index.php');
}

// Iniciar transacción
$conn = getConnection();
$conn->begin_transaction();

try {
    // Obtener los detalles de la venta para restaurar inventario
    $detallesQuery = "SELECT producto_id, cantidad FROM detalleventa WHERE venta_id = ?";
    $stmt = $conn->prepare($detallesQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $detalles = [];
    
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    
    // Restaurar inventario
    foreach ($detalles as $detalle) {
        $productoId = $detalle['producto_id'];
        $cantidad = $detalle['cantidad'];
        
        // Obtener cantidad actual en inventario
        $inventarioQuery = "SELECT cantidad FROM inventario WHERE producto_id = ?";
        $stmt = $conn->prepare($inventarioQuery);
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inventario = $result->fetch_assoc();
        
        // Calcular nueva cantidad
        $nuevaCantidad = $inventario['cantidad'] + $cantidad;
        
        // Actualizar inventario
        $updateInventarioQuery = "UPDATE inventario SET cantidad = ? WHERE producto_id = ?";
        $stmt = $conn->prepare($updateInventarioQuery);
        $stmt->bind_param("ii", $nuevaCantidad, $productoId);
        $stmt->execute();
    }
    
    // Actualizar el estado de la venta a Cancelada
    $updateVentaQuery = "UPDATE venta SET estadoVenta = 'Cancelada' WHERE id = ?";
    $stmt = $conn->prepare($updateVentaQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    $_SESSION['success'] = "La venta ha sido cancelada y el inventario ha sido restaurado.";
    redirect('/views/ventas/view.php?id=' . $id);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    $_SESSION['error'] = "Error al cancelar la venta: " . $e->getMessage();
    redirect('/views/ventas/view.php?id=' . $id);
}
?> 
<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/clientes/index.php');
}

$id = (int) $_GET['id'];

// Verificar si el cliente existe
$sql = "SELECT id FROM cliente WHERE id = ?";
$cliente = fetchOne($sql, [$id], "i");

if (!$cliente) {
    // Si el cliente no existe, redirigir
    redirect('/views/clientes/index.php');
}

// Verificar si el cliente tiene ventas asociadas
$sqlVentas = "SELECT COUNT(*) as total FROM venta WHERE cliente_id = ?";
$resultVentas = fetchOne($sqlVentas, [$id], "i");

if ($resultVentas && $resultVentas['total'] > 0) {
    // Si tiene ventas, no se puede eliminar
    $_SESSION['error'] = "No se puede eliminar el cliente porque tiene ventas asociadas.";
    redirect('/views/clientes/index.php');
}

// Eliminar el cliente
$sqlDelete = "DELETE FROM cliente WHERE id = ?";
$result = execute($sqlDelete, [$id], "i");

if ($result !== false) {
    // Cliente eliminado exitosamente
    $_SESSION['success'] = "Cliente eliminado exitosamente.";
} else {
    // Error al eliminar
    $_SESSION['error'] = "Error al eliminar el cliente. Intente nuevamente.";
}

// Redirigir a la lista de clientes
redirect('/views/clientes/index.php');
?> 
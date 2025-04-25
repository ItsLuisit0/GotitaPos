<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

// Verificar si es una solicitud DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que se proporcione un ID de cliente
if (empty($data['id'])) {
    jsonResponse(['success' => false, 'message' => 'Se requiere el ID del cliente'], 400);
}

$cliente_id = (int) $data['id'];

// Verificar que el cliente exista
$sql = "SELECT * FROM cliente WHERE id = ?";
$cliente = fetchOne($sql, [$cliente_id], 'i');

if (!$cliente) {
    jsonResponse(['success' => false, 'message' => 'Cliente no encontrado'], 404);
}

// Verificar si el cliente tiene ventas asociadas
$sql = "SELECT COUNT(*) as total FROM venta WHERE cliente_id = ?";
$ventas = fetchOne($sql, [$cliente_id], 'i');

if ($ventas['total'] > 0) {
    jsonResponse([
        'success' => false, 
        'message' => 'No se puede eliminar el cliente porque tiene ' . $ventas['total'] . ' venta(s) asociada(s)'
    ], 400);
}

// Eliminar el cliente
$sql = "DELETE FROM cliente WHERE id = ?";
$result = execute($sql, [$cliente_id], 'i');

if ($result) {
    jsonResponse([
        'success' => true, 
        'message' => 'Cliente eliminado exitosamente'
    ]);
} else {
    jsonResponse([
        'success' => false, 
        'message' => 'Error al eliminar el cliente: ' . mysqli_error($GLOBALS['conn'])
    ], 500);
} 
<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

// Verificar si es una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener ID del cliente de la URL
$clienteId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Verificar si se proporcionó un ID
if (!$clienteId) {
    jsonResponse(['success' => false, 'message' => 'ID del cliente es requerido'], 400);
}

// Consultar el cliente por ID
$sql = "SELECT * FROM cliente WHERE id = ?";
$cliente = fetchOne($sql, [$clienteId], "i");

// Verificar si se encontró el cliente
if (!$cliente) {
    jsonResponse(['success' => false, 'message' => 'Cliente no encontrado'], 404);
}

// Consultar el total de ventas del cliente
$sqlVentas = "SELECT COUNT(*) as total FROM venta WHERE cliente_id = ?";
$resultVentas = fetchOne($sqlVentas, [$clienteId], "i");
$totalVentas = $resultVentas ? $resultVentas['total'] : 0;

// Añadir total de ventas a la información del cliente
$cliente['total_ventas'] = $totalVentas;

// Devolver la información del cliente
jsonResponse([
    'success' => true,
    'data' => $cliente
]); 
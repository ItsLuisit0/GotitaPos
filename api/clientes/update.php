<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

// Verificar si es una solicitud PUT o PATCH
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que se proporcione un ID de cliente
if (empty($data['id'])) {
    jsonResponse(['success' => false, 'message' => 'Se requiere el ID del cliente'], 400);
}

$cliente_id = $data['id'];

// Verificar que el cliente exista
$sql = "SELECT * FROM cliente WHERE id = ?";
$cliente = fetchOne($sql, [$cliente_id], 'i');

if (!$cliente) {
    jsonResponse(['success' => false, 'message' => 'Cliente no encontrado'], 404);
}

// Verificar si se está intentando actualizar el teléfono y si ya existe
if (!empty($data['telefono']) && $data['telefono'] !== $cliente['telefono']) {
    $sql = "SELECT id FROM cliente WHERE telefono = ? AND id != ?";
    $telefono_existente = fetchOne($sql, [$data['telefono'], $cliente_id], 'si');
    
    if ($telefono_existente) {
        jsonResponse([
            'success' => false, 
            'message' => 'Ya existe otro cliente con este número de teléfono'
        ], 400);
    }
}

// Preparar los campos a actualizar
$campos_actualizables = [
    'nombre' => 's',
    'apellido' => 's',
    'telefono' => 's',
    'email' => 's',
    'direccion' => 's'
];

$updates = [];
$params = [];
$types = '';

foreach ($campos_actualizables as $campo => $tipo) {
    if (isset($data[$campo])) {
        $updates[] = "$campo = ?";
        $params[] = $data[$campo];
        $types .= $tipo;
    }
}

// Si no hay nada que actualizar
if (empty($updates)) {
    jsonResponse(['success' => false, 'message' => 'No se proporcionaron datos para actualizar'], 400);
}

// Construir la consulta SQL
$sql = "UPDATE cliente SET " . implode(', ', $updates) . " WHERE id = ?";
$params[] = $cliente_id;
$types .= 'i';

// Ejecutar la actualización
$result = execute($sql, $params, $types);

if ($result) {
    // Obtener los datos actualizados del cliente
    $sql = "SELECT * FROM cliente WHERE id = ?";
    $cliente_actualizado = fetchOne($sql, [$cliente_id], 'i');
    
    jsonResponse([
        'success' => true, 
        'message' => 'Cliente actualizado exitosamente',
        'data' => $cliente_actualizado
    ]);
} else {
    jsonResponse([
        'success' => false, 
        'message' => 'Error al actualizar el cliente: ' . mysqli_error($GLOBALS['conn'])
    ], 500);
} 
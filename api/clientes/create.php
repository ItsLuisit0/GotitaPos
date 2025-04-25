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

// Validar campos requeridos
$required_fields = ['nombre', 'apellido', 'telefono'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    jsonResponse([
        'success' => false, 
        'message' => 'Faltan campos requeridos: ' . implode(', ', $missing_fields)
    ], 400);
}

// Validar que el teléfono sea único
$sql = "SELECT id FROM cliente WHERE telefono = ?";
$cliente_existente = fetchOne($sql, [$data['telefono']], 's');
if ($cliente_existente) {
    jsonResponse([
        'success' => false, 
        'message' => 'Ya existe un cliente con este número de teléfono'
    ], 400);
}

// Preparar datos para inserción
$nombre = $data['nombre'];
$apellido = $data['apellido'];
$telefono = $data['telefono'];
$email = $data['email'] ?? '';
$direccion = $data['direccion'] ?? '';
$fecha_creacion = date('Y-m-d H:i:s');

// Insertar el nuevo cliente
$sql = "INSERT INTO cliente (nombre, apellido, telefono, email, direccion, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, ?)";
$params = [$nombre, $apellido, $telefono, $email, $direccion, $fecha_creacion];
$types = 'ssssss';

$result = execute($sql, $params, $types);

if ($result) {
    $cliente_id = mysqli_insert_id($GLOBALS['conn']);
    
    // Obtener los datos del cliente recién creado
    $sql = "SELECT * FROM cliente WHERE id = ?";
    $cliente = fetchOne($sql, [$cliente_id], 'i');
    
    jsonResponse([
        'success' => true, 
        'message' => 'Cliente creado exitosamente',
        'data' => $cliente
    ]);
} else {
    jsonResponse([
        'success' => false, 
        'message' => 'Error al crear el cliente: ' . mysqli_error($GLOBALS['conn'])
    ], 500);
} 
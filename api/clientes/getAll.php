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

// Obtener parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$offset = ($page - 1) * $limit;

// Construir la consulta SQL
$whereClauses = [];
$params = [];
$types = '';

if (!empty($buscar)) {
    $whereClauses[] = "(nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR telefono LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'ssss';
}

$whereSQL = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Consulta para obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM cliente $whereSQL";
$totalClientes = fetchOne($sqlTotal, $params, $types)['total'] ?? 0;

$totalPages = ceil($totalClientes / $limit);

// Consulta para obtener los clientes con límite y paginación
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM venta WHERE cliente_id = c.id) as total_compras,
               (SELECT SUM(montoTotal) FROM venta WHERE cliente_id = c.id) as total_gastado
        FROM cliente c
        $whereSQL
        ORDER BY c.id DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$clientes = fetchAll($sql, $params, $types);

// Preparar respuesta con metadatos de paginación
$response = [
    'success' => true,
    'data' => [
        'clientes' => $clientes,
        'pagination' => [
            'total' => $totalClientes,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $totalClientes)
        ]
    ]
];

// Enviar respuesta
jsonResponse($response); 
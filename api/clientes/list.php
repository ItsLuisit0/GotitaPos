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

// Parámetros de paginación y búsqueda
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = isset($_GET['porPagina']) ? (int)$_GET['porPagina'] : 10;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Validar parámetros de paginación
if ($pagina < 1) $pagina = 1;
if ($porPagina < 1 || $porPagina > 100) $porPagina = 10;

// Calcular desplazamiento para la paginación
$offset = ($pagina - 1) * $porPagina;

// Construir consulta base
$sql = "SELECT * FROM cliente WHERE 1=1";
$params = [];
$types = "";

// Añadir condiciones de búsqueda si se proporciona un término
if (!empty($busqueda)) {
    $busqueda = "%$busqueda%";
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR telefono LIKE ?)";
    $params = array_merge($params, [$busqueda, $busqueda, $busqueda]);
    $types .= "sss";
}

// Consulta para contar el total de registros (para la paginación)
$sqlCount = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$totalResult = fetchOne($sqlCount, $params, $types);
$total = $totalResult ? $totalResult['total'] : 0;

// Añadir límites para la paginación
$sql .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";
$params[] = $porPagina;
$params[] = $offset;
$types .= "ii";

// Ejecutar la consulta
$clientes = fetchAll($sql, $params, $types);

// Calcular información de paginación
$totalPaginas = ceil($total / $porPagina);

jsonResponse([
    'success' => true,
    'data' => [
        'clientes' => $clientes,
        'paginacion' => [
            'total' => $total,
            'porPagina' => $porPagina,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas
        ]
    ]
]); 
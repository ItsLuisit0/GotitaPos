<?php
// Título de la página
$pageTitle = 'Movimientos de Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('cajas_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver movimientos de cajas');
    redirect('/views/cajas/index.php');
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID de caja no especificado');
    redirect('/views/cajas/index.php');
}

$id = (int)$_GET['id'];

// Obtener los datos de la caja
$sql = "SELECT * FROM caja WHERE id = ?";
$caja = fetchOne($sql, [$id], 'i');

if (!$caja) {
    setFlashMessage('error', 'Caja no encontrada');
    redirect('/views/cajas/index.php');
}

// Configurar paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Configurar filtros
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroFechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtroFechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$filtroBusqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta base
$whereConditions = ["m.caja_id = ?", "m.finalizado = 1"];
$params = [$id];
$types = 'i';

// Aplicar filtros
if (!empty($filtroTipo)) {
    if ($filtroTipo === 'ingreso') {
        $whereConditions[] = "m.monto > 0";
    } else if ($filtroTipo === 'egreso') {
        $whereConditions[] = "m.monto < 0";
    }
}

if (!empty($filtroFechaInicio)) {
    $whereConditions[] = "m.fechaHora >= ?";
    $params[] = $filtroFechaInicio . ' 00:00:00';
    $types .= 's';
}

if (!empty($filtroFechaFin)) {
    $whereConditions[] = "m.fechaHora <= ?";
    $params[] = $filtroFechaFin . ' 23:59:59';
    $types .= 's';
}

if (!empty($filtroBusqueda)) {
    $whereConditions[] = "(m.concepto LIKE ? OR u.nombre LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
    $types .= 'ss';
}

$whereClause = implode(' AND ', $whereConditions);

// Obtener total de registros para paginación
$countSql = "SELECT COUNT(*) as total 
             FROM movimientocaja m 
             LEFT JOIN usuario u ON m.usuario_id = u.id 
             WHERE $whereClause";
$countResult = fetchOne($countSql, $params, $types);
$totalItems = $countResult['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Obtener movimientos con paginación
$sql = "SELECT m.*, u.nombre as responsable 
        FROM movimientocaja m 
        LEFT JOIN usuario u ON m.usuario_id = u.id 
        WHERE $whereClause 
        ORDER BY m.fechaHora DESC 
        LIMIT $offset, $itemsPerPage";
$movimientos = fetchAll($sql, $params, $types);

// Obtener resumen financiero
$resumenSql = "SELECT 
               SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
               SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_egresos,
               COUNT(*) as total_movimientos
               FROM movimientocaja 
               WHERE caja_id = ? AND finalizado = 1";
               
if (!empty($filtroTipo) || !empty($filtroFechaInicio) || !empty($filtroFechaFin) || !empty($filtroBusqueda)) {
    $resumenSql = "SELECT 
                  SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
                  SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_egresos,
                  COUNT(*) as total_movimientos
                  FROM movimientocaja m 
                  LEFT JOIN usuario u ON m.usuario_id = u.id 
                  WHERE $whereClause";
    $resumen = fetchOne($resumenSql, $params, $types);
} else {
    $resumen = fetchOne($resumenSql, [$id], 'i');
}

$totalIngresos = $resumen['total_ingresos'] ?? 0;
$totalEgresos = $resumen['total_egresos'] ?? 0;
$saldoActual = ($caja['saldoInicial'] + $totalIngresos) - $totalEgresos;

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Movimientos de Caja: <?php echo htmlspecialchars($caja['nombre']); ?></h2>
    <div class="flex space-x-2">
        <a href="<?php echo BASE_URL; ?>/views/cajas/ver.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            Volver a Detalles
        </a>
        <?php if ($caja['estado'] === 'activa' && hasPermission('cajas_movimientos')): ?>
        <a href="<?php echo BASE_URL; ?>/views/cajas/nuevo-movimiento.php?id=<?php echo $id; ?>" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
            </svg>
            Nuevo Movimiento
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Resumen financiero -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Ingresos</p>
                <p class="text-2xl font-bold text-green-600">$<?php echo number_format($totalIngresos, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Egresos</p>
                <p class="text-2xl font-bold text-red-600">$<?php echo number_format($totalEgresos, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Balance</p>
                <p class="text-2xl font-bold <?php echo $saldoActual >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    $<?php echo number_format($saldoActual, 2); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-md mb-6">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Filtros</h3>
    </div>
    <div class="p-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" id="tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="ingreso" <?php echo $filtroTipo === 'ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                    <option value="egreso" <?php echo $filtroTipo === 'egreso' ? 'selected' : ''; ?>>Egreso</option>
                </select>
            </div>
            
            <div>
                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $filtroFechaInicio; ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
            </div>
            
            <div>
                <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $filtroFechaFin; ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
            </div>
            
            <div>
                <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" id="busqueda" name="busqueda" placeholder="Concepto o responsable" value="<?php echo htmlspecialchars($filtroBusqueda); ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="h-10 bg-primary-600 hover:bg-primary-700 w-full text-white py-2 px-4 rounded-lg inline-flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filtrar
                </button>
            </div>
        </form>
        
        <?php if (!empty($filtroTipo) || !empty($filtroFechaInicio) || !empty($filtroFechaFin) || !empty($filtroBusqueda)): ?>
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <span class="font-medium"><?php echo $totalItems; ?></span> resultados encontrados
                </div>
                <a href="?id=<?php echo $id; ?>" class="text-sm text-primary-600 hover:text-primary-800">
                    Limpiar filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabla de movimientos -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Movimientos</h3>
    </div>
    
    <?php if (count($movimientos) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($movimiento['fechaHora'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $movimiento['tipo'] === 'ingreso' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($movimiento['tipo']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate">
                                    <?php echo htmlspecialchars($movimiento['concepto']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($movimiento['responsable'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $movimiento['tipo'] === 'ingreso' ? 'text-green-600' : 'text-red-600'; ?>">
                                $<?php echo number_format($movimiento['monto'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?php echo BASE_URL; ?>/views/cajas/ver-movimiento.php?id=<?php echo $movimiento['id']; ?>" 
                                   class="text-primary-600 hover:text-primary-900 mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <?php if (hasPermission('cajas_movimientos_imprimir')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/cajas/imprimir-movimiento.php?id=<?php echo $movimiento['id']; ?>" 
                                   class="text-gray-600 hover:text-gray-900" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Mostrando <span class="font-medium"><?php echo (($page - 1) * $itemsPerPage) + 1; ?></span> a 
                    <span class="font-medium"><?php echo min($page * $itemsPerPage, $totalItems); ?></span> de 
                    <span class="font-medium"><?php echo $totalItems; ?></span> resultados
                </div>
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $id; ?>&page=<?php echo $page - 1; ?>&tipo=<?php echo urlencode($filtroTipo); ?>&fecha_inicio=<?php echo urlencode($filtroFechaInicio); ?>&fecha_fin=<?php echo urlencode($filtroFechaFin); ?>&busqueda=<?php echo urlencode($filtroBusqueda); ?>" 
                           class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">
                            Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) {
                        echo '<a href="?id=' . $id . '&page=1&tipo=' . urlencode($filtroTipo) . '&fecha_inicio=' . urlencode($filtroFechaInicio) . '&fecha_fin=' . urlencode($filtroFechaFin) . '&busqueda=' . urlencode($filtroBusqueda) . '" 
                                class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="px-3 py-1 text-gray-500">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i == $page) {
                            echo '<span class="px-3 py-1 border rounded bg-primary-600 text-white">' . $i . '</span>';
                        } else {
                            echo '<a href="?id=' . $id . '&page=' . $i . '&tipo=' . urlencode($filtroTipo) . '&fecha_inicio=' . urlencode($filtroFechaInicio) . '&fecha_fin=' . urlencode($filtroFechaFin) . '&busqueda=' . urlencode($filtroBusqueda) . '" 
                                    class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">' . $i . '</a>';
                        }
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="px-3 py-1 text-gray-500">...</span>';
                        }
                        echo '<a href="?id=' . $id . '&page=' . $totalPages . '&tipo=' . urlencode($filtroTipo) . '&fecha_inicio=' . urlencode($filtroFechaInicio) . '&fecha_fin=' . urlencode($filtroFechaFin) . '&busqueda=' . urlencode($filtroBusqueda) . '" 
                                class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $id; ?>&page=<?php echo $page + 1; ?>&tipo=<?php echo urlencode($filtroTipo); ?>&fecha_inicio=<?php echo urlencode($filtroFechaInicio); ?>&fecha_fin=<?php echo urlencode($filtroFechaFin); ?>&busqueda=<?php echo urlencode($filtroBusqueda); ?>" 
                           class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100">
                            Siguiente
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="p-6 text-center text-gray-500">
            No se encontraron movimientos 
            <?php if (!empty($filtroTipo) || !empty($filtroFechaInicio) || !empty($filtroFechaFin) || !empty($filtroBusqueda)): ?>
                con los filtros aplicados.
                <div class="mt-2">
                    <a href="?id=<?php echo $id; ?>" class="text-primary-600 hover:text-primary-800">
                        Limpiar filtros
                    </a>
                </div>
            <?php else: ?>
                para esta caja.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
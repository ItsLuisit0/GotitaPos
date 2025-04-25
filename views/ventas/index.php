<?php
// Título de la página
$pageTitle = 'Ventas';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Incluir el encabezado
require_once '../components/header.php';

// Parámetros de búsqueda y filtrado
$search = isset($_GET['search']) ? $_GET['search'] : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Consulta base
$sql = "SELECT v.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, ca.nombre as caja_nombre 
        FROM venta v
        JOIN cliente c ON v.cliente_id = c.id
        JOIN caja ca ON v.caja_id = ca.id
        WHERE 1=1";
$params = [];
$types = "";

// Aplicar filtro de búsqueda
if (!empty($search)) {
    $sql .= " AND (c.nombre LIKE ? OR c.apellido LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Aplicar filtro de fecha inicio
if (!empty($fechaInicio)) {
    $sql .= " AND DATE(v.fecha) >= ?";
    $params[] = $fechaInicio;
    $types .= "s";
}

// Aplicar filtro de fecha fin
if (!empty($fechaFin)) {
    $sql .= " AND DATE(v.fecha) <= ?";
    $params[] = $fechaFin;
    $types .= "s";
}

// Aplicar filtro de estado
if (!empty($estado)) {
    $sql .= " AND v.estadoVenta = ?";
    $params[] = $estado;
    $types .= "s";
}

// Ordenar
$sql .= " ORDER BY v.fecha DESC";

// Obtener ventas
$ventas = fetchAll($sql, $params, $types);

// Obtener total de ventas
$totalVentas = 0;
foreach ($ventas as $venta) {
    if ($venta['estadoVenta'] === 'Completada') {
        $totalVentas += $venta['montoTotal'];
    }
}
?>

<!-- Cabecera de la página con mensaje de éxito/error -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Listado de Ventas</h2>
    <a href="<?php echo BASE_URL; ?>/views/ventas/create.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nueva Venta
    </a>
</div>

<!-- Mensajes de sesión -->
<?php if (isset($_SESSION['success'])): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm"><?php echo $_SESSION['success']; ?></p>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm"><?php echo $_SESSION['error']; ?></p>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Filtros y buscador -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar cliente</label>
            <input 
                type="text" 
                name="search" 
                id="search" 
                placeholder="Nombre del cliente..."
                value="<?php echo htmlspecialchars($search); ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        
        <div>
            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
            <input 
                type="date" 
                name="fecha_inicio" 
                id="fecha_inicio" 
                value="<?php echo htmlspecialchars($fechaInicio); ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        
        <div>
            <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
            <input 
                type="date" 
                name="fecha_fin" 
                id="fecha_fin" 
                value="<?php echo htmlspecialchars($fechaFin); ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        
        <div>
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select 
                name="estado" 
                id="estado" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">Todos los estados</option>
                <option value="Pendiente" <?php echo $estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Completada" <?php echo $estado === 'Completada' ? 'selected' : ''; ?>>Completada</option>
                <option value="Cancelada" <?php echo $estado === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
        </div>
        
        <div class="flex items-end space-x-2">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                Filtrar
            </button>
            
            <?php if (!empty($search) || !empty($fechaInicio) || !empty($fechaFin) || !empty($estado)): ?>
            <a href="<?php echo BASE_URL; ?>/views/ventas/index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpiar
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Resumen de ventas -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
        <div>
            <span class="text-gray-600 text-sm">Total ventas completadas:</span>
            <span class="text-xl font-bold text-gray-800 ml-2">$<?php echo number_format($totalVentas, 2); ?></span>
        </div>
        <div>
            <span class="text-gray-600 text-sm">Cantidad de ventas:</span>
            <span class="text-xl font-bold text-gray-800 ml-2"><?php echo count($ventas); ?></span>
        </div>
    </div>
</div>

<!-- Tabla de ventas -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Folio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Cliente
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Caja
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($ventas) > 0): ?>
                    <?php foreach ($ventas as $venta): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">#<?php echo $venta['id']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($venta['caja_nombre']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">$<?php echo number_format($venta['montoTotal'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($venta['estadoVenta'] === 'Completada'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Completada
                                    </span>
                                <?php elseif ($venta['estadoVenta'] === 'Pendiente'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Cancelada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="<?php echo BASE_URL; ?>/views/ventas/view.php?id=<?php echo $venta['id']; ?>" class="text-primary-600 hover:text-primary-900 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Ver
                                </a>
                                
                                <?php if ($venta['estadoVenta'] === 'Pendiente'): ?>
                                <a href="<?php echo BASE_URL; ?>/views/ventas/complete.php?id=<?php echo $venta['id']; ?>" 
                                   class="text-green-600 hover:text-green-900 inline-flex items-center ml-2"
                                   onclick="return confirm('¿Está seguro de completar esta venta?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Completar
                                </a>
                                
                                <a href="<?php echo BASE_URL; ?>/views/ventas/cancel.php?id=<?php echo $venta['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 inline-flex items-center ml-2"
                                   onclick="return confirm('¿Está seguro de cancelar esta venta?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Cancelar
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($venta['estadoVenta'] === 'Completada'): ?>
                                <a href="<?php echo BASE_URL; ?>/views/ventas/ticket.php?id=<?php echo $venta['id']; ?>" 
                                   class="text-gray-600 hover:text-gray-900 inline-flex items-center ml-2"
                                   target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                    </svg>
                                    Ticket
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No se encontraron ventas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
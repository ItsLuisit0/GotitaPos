<?php
// Título de la página
$pageTitle = 'Gestión de Cajas';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
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
    $whereClauses[] = "(nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'ss';
}

$whereSQL = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Consulta para obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM caja $whereSQL";
$totalCajas = fetchOne($sqlTotal, $params, $types)['total'] ?? 0;

$totalPages = ceil($totalCajas / $limit);

// Consulta para obtener las cajas con límite y paginación
$sql = "SELECT c.*, 
               (SELECT SUM(monto) FROM movimientocaja WHERE caja_id = c.id AND monto > 0 AND finalizado = 1) as total_ingresos,
               (SELECT SUM(monto) FROM movimientocaja WHERE caja_id = c.id AND monto < 0 AND finalizado = 1) as total_egresos,
               (SELECT COUNT(*) FROM movimientocaja WHERE caja_id = c.id) as total_movimientos
        FROM caja c
        $whereSQL
        ORDER BY c.id DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$cajas = fetchAll($sql, $params, $types);

// Calcular saldo actual para cada caja
foreach ($cajas as &$caja) {
    $totalIngresos = $caja['total_ingresos'] ?? 0;
    $totalEgresos = $caja['total_egresos'] ?? 0;
    $caja['saldo_actual'] = ($caja['saldoInicial'] + $totalIngresos) - $totalEgresos;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Verificar permisos
$tienePermisoVer = hasPermission('cajas_ver');
$tienePermisoCrear = hasPermission('cajas_crear');
$tienePermisoEditar = hasPermission('cajas_editar');
$tienePermisoEliminar = hasPermission('cajas_eliminar');

if (!$tienePermisoVer) {
    header('Location: ../../dashboard.php?error=No tiene permisos para ver cajas');
    exit;
}

// Procesar eliminación si se recibe el parámetro
if (isset($_GET['eliminar']) && $tienePermisoEliminar) {
    $id = $_GET['eliminar'];
    
    // Verificar que la caja no tenga movimientos
    $stmt = $conn->prepare("SELECT COUNT(*) FROM movimientocaja WHERE caja_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        $mensaje = "No se puede eliminar la caja porque tiene movimientos registrados";
        $tipo = "error";
    } else {
        // Eliminar la caja
        $stmt = $conn->prepare("DELETE FROM cajas WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Caja eliminada correctamente";
            $tipo = "success";
        } else {
            $mensaje = "Error al eliminar la caja: " . $conn->error;
            $tipo = "error";
        }
        $stmt->close();
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Gestión de Cajas</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/crear.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Nueva Caja
    </a>
</div>

<!-- Alertas de éxito o error -->
<?php if (isset($_SESSION['success'])): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $_SESSION['success']; ?></p>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $_SESSION['error']; ?></p>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Filtros y búsqueda -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
        <div class="flex-grow">
            <label for="buscar" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <div class="relative">
                <input 
                    type="text" 
                    id="buscar" 
                    name="buscar" 
                    value="<?php echo htmlspecialchars($buscar); ?>" 
                    placeholder="Nombre o descripción..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="w-full md:w-1/6">
            <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Mostrar</label>
            <select 
                id="limit" 
                name="limit" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                onchange="this.form.submit()"
            >
                <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10 por página</option>
                <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25 por página</option>
                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 por página</option>
                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 por página</option>
            </select>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="h-full w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
                Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Tabla de cajas -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Actual</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($cajas)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                        No se encontraron cajas registradas
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($cajas as $caja): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $caja['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($caja['nombre']); ?>
                            </div>
                            <?php if (!empty($caja['descripcion'])): ?>
                            <div class="text-xs text-gray-500 truncate max-w-xs">
                                <?php echo htmlspecialchars($caja['descripcion']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($caja['responsable']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium <?php echo $caja['saldo_actual'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                $<?php echo number_format($caja['saldo_actual'], 2); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo $caja['total_movimientos']; ?> movimientos
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (isset($caja['estado']) && $caja['estado'] === 'activa'): ?>
                                <?php if (isset($caja['estaAbierta']) && $caja['estaAbierta']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Abierta
                                </span>
                                <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Cerrada
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactiva
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo isset($caja['fechaCreacion']) ? date('d/m/Y H:i', strtotime($caja['fechaCreacion'])) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="<?php echo BASE_URL; ?>/views/cajas/ver.php?id=<?php echo $caja['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Ver detalles">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                
                                <a href="<?php echo BASE_URL; ?>/views/cajas/editar.php?id=<?php echo $caja['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>
                                
                                <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $caja['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Movimientos">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                </a>
                                
                                <?php if (isset($caja['estado']) && $caja['estado'] === 'activa'): ?>
                                    <?php if (isset($caja['estaAbierta']) && $caja['estaAbierta']): ?>
                                    <a href="<?php echo BASE_URL; ?>/views/cajas/cerrar.php?id=<?php echo $caja['id']; ?>" class="text-yellow-600 hover:text-yellow-900" title="Cerrar caja">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                    </a>
                                    <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/views/cajas/abrir.php?id=<?php echo $caja['id']; ?>" class="text-green-600 hover:text-green-900" title="Abrir caja">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-gray-500">
        Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> 
        a <span class="font-medium"><?php echo min($offset + $limit, $totalCajas); ?></span> 
        de <span class="font-medium"><?php echo $totalCajas; ?></span> cajas
    </div>
    
    <div class="flex space-x-1">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Anterior
        </a>
        <?php endif; ?>
        
        <?php 
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1) {
            echo '<span class="px-3 py-1 text-gray-500">...</span>';
        }
        
        for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
        <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>" 
           class="px-3 py-1 rounded-md <?php echo $i === $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
        <span class="px-3 py-1 text-gray-500">...</span>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Siguiente
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
    // Auto-dismiss de las alertas después de 5 segundos
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
</script>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
<?php
// Título de la página
$pageTitle = 'Gestión de Clientes';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('clientes_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver clientes');
    redirect('/views/dashboard/index.php');
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
    $whereClauses[] = "(nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR telefono LIKE ?)";
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

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Gestión de Clientes</h2>
    <a href="<?php echo BASE_URL; ?>/views/clientes/crear.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Nuevo Cliente
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
                    placeholder="Nombre, apellidos, correo o teléfono..."
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

<!-- Tabla de clientes -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Historial</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Registro</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($clientes)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        No se encontraron clientes registrados
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $cliente['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? '')); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">
                                <div class="flex items-center mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    <?php echo htmlspecialchars($cliente['correo'] ?? 'N/A'); ?>
                                </div>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                    </svg>
                                    <?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo $cliente['total_compras'] ?? 0; ?> compras
                            </div>
                            <div class="text-sm text-gray-500">
                                $<?php echo number_format($cliente['total_gastado'] ?? 0, 2); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            N/A
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="<?php echo BASE_URL; ?>/views/clientes/ver.php?id=<?php echo $cliente['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Ver detalles">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                
                                <a href="<?php echo BASE_URL; ?>/views/clientes/editar.php?id=<?php echo $cliente['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>
                                
                                <a href="<?php echo BASE_URL; ?>/views/ventas/historial.php?cliente_id=<?php echo $cliente['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Historial de compras">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                </a>
                                
                                <?php if (hasPermission('clientes_eliminar')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/clientes/eliminar.php?id=<?php echo $cliente['id']; ?>" class="text-red-600 hover:text-red-900" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este cliente?');">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </a>
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
        a <span class="font-medium"><?php echo min($offset + $limit, $totalClientes); ?></span> 
        de <span class="font-medium"><?php echo $totalClientes; ?></span> clientes
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
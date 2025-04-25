<?php
// Título de la página
$pageTitle = 'Gestión de Empleados';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('empleados_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver empleados');
    redirect('/dashboard.php');
}

// Obtener parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$offset = ($page - 1) * $limit;

// Construir la consulta SQL
$whereClauses = [];
$params = [];
$types = '';

if (!empty($buscar)) {
    $whereClauses[] = "(nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'sss';
}

if (!empty($estado) && in_array($estado, ['activo', 'inactivo'])) {
    $whereClauses[] = "estado = ?";
    $params[] = $estado;
    $types .= 's';
}

$whereSQL = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Consulta para obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM empleado $whereSQL";
$totalEmpleados = fetchOne($sqlTotal, $params, $types)['total'] ?? 0;

$totalPages = ceil($totalEmpleados / $limit);

// Consulta para obtener los empleados con límite y paginación
$sql = "SELECT e.*
        FROM empleado e
        $whereSQL
        ORDER BY e.nombre ASC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$empleados = fetchAll($sql, $params, $types);

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Gestión de Empleados</h2>
    <?php if (hasPermission('empleados_crear')): ?>
    <a href="<?php echo BASE_URL; ?>/views/empleados/crear.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Nuevo Empleado
    </a>
    <?php endif; ?>
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
                    placeholder="Nombre, email o teléfono..."
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
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select 
                id="estado" 
                name="estado" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">Todos</option>
                <option value="activo" <?php echo $estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                <option value="inactivo" <?php echo $estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
            </select>
        </div>
        
        <div class="w-full md:w-1/6">
            <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Mostrar</label>
            <select 
                id="limit" 
                name="limit" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
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

<!-- Tabla de empleados -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($empleados)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        No se encontraron empleados
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($empleados as $empleado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $empleado['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                    <?php echo strtoupper(substr($empleado['nombre'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($empleado['nombre']); ?>
                                    </div>
                                    <?php if (!empty($empleado['puesto'])): ?>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($empleado['puesto']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($empleado['email'] ?? ''); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo isset($empleado['telefono']) ? htmlspecialchars($empleado['telefono']) : 'N/A'; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Empleado
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo (isset($empleado['estado']) && $empleado['estado'] === 'activo') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo isset($empleado['estado']) ? ucfirst($empleado['estado']) : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <?php if (hasPermission('empleados_ver')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/empleados/ver.php?id=<?php echo $empleado['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Ver detalles">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('empleados_editar')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/empleados/editar.php?id=<?php echo $empleado['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('usuarios_crear')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/usuarios/crear.php?empleado_id=<?php echo $empleado['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Crear cuenta de usuario">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
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
        a <span class="font-medium"><?php echo min($offset + $limit, $totalEmpleados); ?></span> 
        de <span class="font-medium"><?php echo $totalEmpleados; ?></span> empleados
    </div>
    
    <div class="flex space-x-1">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&estado=<?php echo urlencode($estado); ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
        <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&estado=<?php echo urlencode($estado); ?>" 
           class="px-3 py-1 rounded-md <?php echo $i === $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
        <span class="px-3 py-1 text-gray-500">...</span>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&estado=<?php echo urlencode($estado); ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
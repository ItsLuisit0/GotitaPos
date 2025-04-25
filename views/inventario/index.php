<?php
// Título de la página
$pageTitle = 'Gestión de Inventario';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('inventario_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver el inventario');
    redirect('/views/dashboard/index.php');
}

// Obtener parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$stock = isset($_GET['stock']) ? $_GET['stock'] : '';

$offset = ($page - 1) * $limit;

// Construir la consulta SQL
$whereClauses = [];
$params = [];
$types = '';

if (!empty($buscar)) {
    $whereClauses[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'ss';
}

if ($categoria > 0) {
    $whereClauses[] = "p.categoria_id = ?";
    $params[] = $categoria;
    $types .= 'i';
}

if ($stock === 'bajo') {
    $whereClauses[] = "p.stockDisponible <= 10";
} elseif ($stock === 'agotado') {
    $whereClauses[] = "p.stockDisponible = 0";
}

$whereSQL = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Consulta para obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM producto p $whereSQL";
$totalProductos = fetchOne($sqlTotal, $params, $types)['total'] ?? 0;

$totalPages = ceil($totalProductos / $limit);

// Consulta para obtener los productos con límite y paginación
$sql = "SELECT p.*, c.nombre as categoria_nombre
        FROM producto p
        LEFT JOIN categoria c ON p.categoria_id = c.id
        $whereSQL
        ORDER BY p.nombre ASC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$productos = fetchAll($sql, $params, $types);

// Obtener categorías para el filtro
$sqlCategorias = "SELECT id, nombre FROM categoria ORDER BY nombre";
$categorias = fetchAll($sqlCategorias);

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Gestión de Inventario</h2>
    <div class="flex space-x-2">
        <?php if (hasPermission('inventario_ajustar')): ?>
        <a href="<?php echo BASE_URL; ?>/views/inventario/ajustar.php" class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Ajustar Inventario
        </a>
        <?php endif; ?>
        
        <?php if (hasPermission('productos_crear')): ?>
        <a href="<?php echo BASE_URL; ?>/views/productos/crear.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Nuevo Producto
        </a>
        <?php endif; ?>
    </div>
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
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
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
        
        <div>
            <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
            <select 
                id="categoria" 
                name="categoria" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Estado del Stock</label>
            <select 
                id="stock" 
                name="stock" 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="" <?php echo $stock === '' ? 'selected' : ''; ?>>Todos</option>
                <option value="bajo" <?php echo $stock === 'bajo' ? 'selected' : ''; ?>>Stock bajo (≤ 10)</option>
                <option value="agotado" <?php echo $stock === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="h-10 w-full bg-gray-500 hover:bg-gray-600 text-white px-4 rounded-lg inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
                Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Resumen de inventario -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <?php 
    // Obtener totales para el dashboard
    $stockTotal = fetchOne("SELECT SUM(stockDisponible) as total FROM producto")['total'] ?? 0;
    $productosAgotados = fetchOne("SELECT COUNT(*) as total FROM producto WHERE stockDisponible = 0")['total'] ?? 0;
    $stockBajo = fetchOne("SELECT COUNT(*) as total FROM producto WHERE stockDisponible > 0 AND stockDisponible <= 10")['total'] ?? 0;
    ?>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Total en Inventario</div>
                <div class="text-xl font-semibold"><?php echo number_format($stockTotal); ?> unidades</div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-yellow-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Stock Bajo</div>
                <div class="text-xl font-semibold"><?php echo $stockBajo; ?> productos</div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Agotados</div>
                <div class="text-xl font-semibold"><?php echo $productosAgotados; ?> productos</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de inventario -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        No se encontraron productos
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $producto['id']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50) . (strlen($producto['descripcion']) > 50 ? '...' : '')); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            $<?php echo number_format($producto['precioUnitario'], 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($producto['stockDisponible'] <= 0): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Agotado
                            </span>
                            <?php elseif ($producto['stockDisponible'] <= 10): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <?php echo $producto['stockDisponible']; ?> unidades
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <?php echo $producto['stockDisponible']; ?> unidades
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="<?php echo BASE_URL; ?>/views/productos/ver.php?id=<?php echo $producto['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Ver detalles">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                
                                <?php if (hasPermission('productos_editar')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/productos/editar.php?id=<?php echo $producto['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('inventario_ajustar')): ?>
                                <a href="<?php echo BASE_URL; ?>/views/inventario/ajustar.php?id=<?php echo $producto['id']; ?>" class="text-yellow-600 hover:text-yellow-900" title="Ajustar stock">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
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
        a <span class="font-medium"><?php echo min($offset + $limit, $totalProductos); ?></span> 
        de <span class="font-medium"><?php echo $totalProductos; ?></span> productos
    </div>
    
    <div class="flex space-x-1">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&categoria=<?php echo $categoria; ?>&stock=<?php echo $stock; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
        <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&categoria=<?php echo $categoria; ?>&stock=<?php echo $stock; ?>" 
           class="px-3 py-1 rounded-md <?php echo $i === $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
        <span class="px-3 py-1 text-gray-500">...</span>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&buscar=<?php echo urlencode($buscar); ?>&categoria=<?php echo $categoria; ?>&stock=<?php echo $stock; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
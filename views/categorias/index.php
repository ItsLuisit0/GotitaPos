<?php
// Título de la página
$pageTitle = "Gestión de Categorías";

// Incluir el archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    // Redirigir al usuario a la página de inicio de sesión
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para ver categorías
if (!hasPermission('ver_categorias')) {
    // Establecer mensaje de error
    setFlashMessage('error', 'No tienes permisos para ver categorías');
    // Redirigir a la página principal
    redirect('/index.php');
}

// Parámetros de paginación y búsqueda
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
$buscar = isset($_GET['buscar']) ? htmlspecialchars(trim($_GET['buscar'])) : '';

// Calcular offset para la consulta SQL
$offset = ($page - 1) * $limit;

// Construir consulta base
$baseQuery = "FROM categoria WHERE 1=1";
$countQuery = "SELECT COUNT(*) " . $baseQuery;
$dataQuery = "SELECT * " . $baseQuery;
$params = [];
$types = "";

// Añadir condiciones de búsqueda si se proporciona un término
if (!empty($buscar)) {
    $baseQuery .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $buscarParam = "%{$buscar}%";
    $params[] = $buscarParam;
    $params[] = $buscarParam;
    $types .= "ss";
    
    // Actualizar las consultas
    $countQuery = "SELECT COUNT(*) " . $baseQuery;
    $dataQuery = "SELECT * " . $baseQuery;
}

// Obtener el total de registros
$totalRegistros = fetchOne($countQuery, $params, $types)['COUNT(*)'] ?? 0;

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $limit);

// Asegurar que la página actual está dentro del rango válido
if ($page > $totalPaginas && $totalPaginas > 0) {
    // Redirigir a la última página disponible
    redirect("/views/categorias/index.php?page={$totalPaginas}&limit={$limit}" . (!empty($buscar) ? "&buscar={$buscar}" : ""));
}

// Añadir ordenamiento y límites a la consulta de datos
$dataQuery .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Obtener las categorías
$categorias = fetchAll($dataQuery, $params, $types);

// Incluir el header
include_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Gestión de Categorías</h1>
        
        <?php if (hasPermission('crear_categoria')): ?>
        <a href="/views/categorias/crear.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            <i class="fas fa-plus mr-2"></i>Nueva Categoría
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="alert-message" class="mb-6 <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded">
            <div class="flex items-center">
                <div class="py-1">
                    <i class="fas <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                </div>
                <div>
                    <p class="font-bold"><?php echo $_SESSION['flash_message']['type'] === 'success' ? 'Éxito' : 'Error'; ?></p>
                    <p><?php echo $_SESSION['flash_message']['message']; ?></p>
                </div>
            </div>
        </div>
        <script>
            // Auto-dismiss the alert after 5 seconds
            setTimeout(function() {
                var alertMessage = document.getElementById('alert-message');
                if (alertMessage) {
                    alertMessage.style.display = 'none';
                }
            }, 5000);
        </script>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Filtros y búsqueda -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form action="" method="GET" class="flex flex-wrap items-end">
            <div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4">
                <label for="buscar" class="block text-gray-700 font-bold mb-2">Buscar Categoría</label>
                <input type="text" id="buscar" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="Nombre o descripción">
            </div>
            
            <div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4">
                <label for="limit" class="block text-gray-700 font-bold mb-2">Mostrar</label>
                <select id="limit" name="limit" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            
            <div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4 flex items-center">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
                <a href="/views/categorias/index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-redo-alt mr-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Categorías -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <?php if (empty($categorias)): ?>
            <div class="p-6 text-center">
                <p class="text-gray-700">No se encontraron categorías<?php echo !empty($buscar) ? ' para la búsqueda "' . htmlspecialchars($buscar) . '"' : ''; ?>.</p>
                <?php if (!empty($buscar)): ?>
                    <a href="/views/categorias/index.php" class="text-blue-500 hover:underline mt-2 inline-block">Ver todas las categorías</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Creación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($categorias as $categoria): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $categoria['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo !empty($categoria['descripcion']) ? htmlspecialchars(substr($categoria['descripcion'], 0, 100)) . (strlen($categoria['descripcion']) > 100 ? '...' : '') : '<span class="text-gray-400 italic">Sin descripción</span>'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo isset($categoria['fecha_creacion']) ? date('d/m/Y H:i', strtotime($categoria['fecha_creacion'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <?php if (hasPermission('editar_categoria')): ?>
                                        <a href="/views/categorias/editar.php?id=<?php echo $categoria['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('eliminar_categoria')): ?>
                                        <a href="#" onclick="confirmarEliminacion(<?php echo $categoria['id']; ?>, '<?php echo addslashes(htmlspecialchars($categoria['nombre'])); ?>')" 
                                           class="text-red-600 hover:text-red-900" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Mostrando <span class="font-medium"><?php echo min(($page - 1) * $limit + 1, $totalRegistros); ?></span> 
                            a <span class="font-medium"><?php echo min($page * $limit, $totalRegistros); ?></span> 
                            de <span class="font-medium"><?php echo $totalRegistros; ?></span> resultados
                        </div>
                        
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="/views/categorias/index.php?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?><?php echo !empty($buscar) ? '&buscar=' . urlencode($buscar) : ''; ?>" 
                                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2 text-sm font-medium rounded-md">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPaginas): ?>
                                <a href="/views/categorias/index.php?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?><?php echo !empty($buscar) ? '&buscar=' . urlencode($buscar) : ''; ?>" 
                                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2 text-sm font-medium rounded-md">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Script para confirmar eliminación -->
<script>
function confirmarEliminacion(id, nombre) {
    if (confirm('¿Estás seguro de que deseas eliminar la categoría "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = '/views/categorias/eliminar.php?id=' + id;
    }
}
</script>

<?php include_once '../components/footer.php'; ?> 
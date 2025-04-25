<?php
// Título de la página
$pageTitle = 'Productos';

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
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

// Consulta base
$sql = "SELECT p.*, c.nombre as categoria_nombre, i.cantidad as stock
        FROM producto p
        LEFT JOIN categoria c ON p.categoria_id = c.id
        LEFT JOIN inventario i ON p.id = i.producto_id
        WHERE 1=1";
$params = [];
$types = "";

// Aplicar filtro de búsqueda
if (!empty($search)) {
    $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Aplicar filtro de categoría
if ($categoria > 0) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoria;
    $types .= "i";
}

// Ordenar
$sql .= " ORDER BY p.nombre";

// Obtener productos
$productos = fetchAll($sql, $params, $types);

// Obtener categorías para el filtro
$categoriasQuery = "SELECT id, nombre FROM categoria ORDER BY nombre";
$categorias = fetchAll($categoriasQuery);
?>

<!-- Cabecera de la página con mensaje de éxito/error -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Listado de Productos</h2>
    <a href="<?php echo BASE_URL; ?>/views/productos/create.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nuevo Producto
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
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-grow">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar producto</label>
            <input 
                type="text" 
                name="search" 
                id="search" 
                placeholder="Nombre o descripción..."
                value="<?php echo htmlspecialchars($search); ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        
        <div class="w-full md:w-1/4">
            <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
            <select 
                name="categoria" 
                id="categoria" 
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
        
        <div class="flex items-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                Filtrar
            </button>
        </div>
        
        <?php if (!empty($search) || $categoria > 0): ?>
        <div class="flex items-end">
            <a href="<?php echo BASE_URL; ?>/views/productos/index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpiar
            </a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla de productos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Producto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categoría
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Precio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Stock
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $producto): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($producto['descripcion']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">$<?php echo number_format($producto['precioUnitario'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($producto['stock'] <= 10): ?>
                                <div class="text-sm text-red-600 font-medium"><?php echo $producto['stock']; ?> unidades</div>
                                <?php else: ?>
                                <div class="text-sm text-gray-900"><?php echo $producto['stock']; ?> unidades</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="<?php echo BASE_URL; ?>/views/productos/edit.php?id=<?php echo $producto['id']; ?>" class="text-primary-600 hover:text-primary-900 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    Editar
                                </a>
                                <a href="<?php echo BASE_URL; ?>/views/productos/delete.php?id=<?php echo $producto['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 inline-flex items-center ml-2"
                                   onclick="return confirm('¿Está seguro de eliminar este producto?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            No se encontraron productos.
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
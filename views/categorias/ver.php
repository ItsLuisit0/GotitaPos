<?php
// Título de la página
$pageTitle = "Detalles de Categoría";

// Incluir archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para ver categorías
if (!hasPermission('ver_categoria')) {
    setFlashMessage('error', 'No tienes permisos para ver categorías');
    redirect('/views/dashboard.php');
}

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de categoría inválido');
    redirect('/views/categorias/index.php');
}

$categoria_id = (int)$_GET['id'];

// Obtener datos de la categoría
$sqlCategoria = "SELECT c.*, 
                      u.nombre AS creado_por_nombre,
                      (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) AS total_productos
               FROM categorias c
               LEFT JOIN usuarios u ON c.creado_por = u.id
               WHERE c.id = ?";
               
$categoria = fetchOne($sqlCategoria, [$categoria_id], "i");

// Verificar si la categoría existe
if (!$categoria) {
    setFlashMessage('error', 'La categoría no existe');
    redirect('/views/categorias/index.php');
}

// Incluir el encabezado
include_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Alertas -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="alert" class="mb-4 <?php echo getAlertClass($_SESSION['flash_message']['type']); ?>">
            <div class="flex items-center">
                <?php echo getAlertIcon($_SESSION['flash_message']['type']); ?>
                <span><?php echo $_SESSION['flash_message']['message']; ?></span>
            </div>
        </div>
        <?php clearFlashMessage(); ?>
    <?php endif; ?>

    <!-- Encabezado y acciones -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detalles de Categoría</h1>
        <div class="flex space-x-2">
            <a href="/views/categorias/index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            <?php if (hasPermission('editar_categoria')): ?>
                <a href="/views/categorias/editar.php?id=<?php echo $categoria_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
            <?php endif; ?>
            <?php if (hasPermission('eliminar_categoria')): ?>
                <button id="btn-eliminar" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información de la categoría -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Información General</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nombre</h3>
                    <p class="text-lg font-semibold"><?php echo htmlspecialchars($categoria['nombre']); ?></p>
                </div>
                <!-- Estado -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Estado</h3>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $categoria['estado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $categoria['estado'] ? 'Activa' : 'Inactiva'; ?>
                    </span>
                </div>
                <!-- Fecha de creación -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Fecha de Creación</h3>
                    <p><?php echo formatFecha($categoria['fecha_creacion']); ?></p>
                </div>
                <!-- Creado por -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Creado por</h3>
                    <p><?php echo htmlspecialchars($categoria['creado_por_nombre'] ?? 'Usuario desconocido'); ?></p>
                </div>
                <!-- Total de productos -->
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Total de Productos</h3>
                    <p class="text-lg font-semibold"><?php echo $categoria['total_productos']; ?> productos</p>
                </div>
                <!-- Descripción -->
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Descripción</h3>
                    <p class="mt-2 whitespace-pre-line">
                        <?php echo htmlspecialchars($categoria['descripcion'] ?: 'No hay descripción disponible'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de productos -->
    <?php if ($categoria['total_productos'] > 0): ?>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Productos en esta Categoría</h2>
        </div>
        <div class="p-6">
            <?php
            // Consultar productos de esta categoría (limitado a 10)
            $sqlProductos = "SELECT id, codigo, nombre, precio_venta, stock, imagen
                           FROM productos
                           WHERE categoria_id = ?
                           ORDER BY nombre ASC
                           LIMIT 10";
            $productos = fetchAll($sqlProductos, [$categoria_id], "i");
            ?>

            <?php if (empty($productos)): ?>
                <p class="text-gray-500">No hay productos en esta categoría.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Código
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Producto
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
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($producto['codigo']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($producto['imagen'])): ?>
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" src="<?php echo $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo formatMoney($producto['precio_venta']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $producto['stock'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $producto['stock']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if (hasPermission('ver_producto')): ?>
                                            <a href="/views/productos/ver.php?id=<?php echo $producto['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('editar_producto')): ?>
                                            <a href="/views/productos/editar.php?id=<?php echo $producto['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($categoria['total_productos'] > 10): ?>
                    <div class="mt-4 text-center">
                        <a href="/views/productos/index.php?categoria=<?php echo $categoria_id; ?>" class="text-blue-600 hover:text-blue-800">
                            Ver todos los productos de esta categoría
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmación para eliminar -->
<?php if (hasPermission('eliminar_categoria')): ?>
<div id="modal-eliminar" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Eliminar Categoría
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.
                                <?php if ($categoria['total_productos'] > 0): ?>
                                    <br><br>
                                    <strong class="text-red-600">Advertencia:</strong> Esta categoría tiene <?php echo $categoria['total_productos']; ?> productos asociados. Si la eliminas, estos productos quedarán sin categoría.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="/views/categorias/eliminar.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $categoria_id; ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Confirmar Eliminación
                    </button>
                </form>
                <button type="button" id="btn-cancelar" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(function() {
        const alert = document.getElementById('alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);

    // Funcionalidad del modal para eliminar categoría
    document.addEventListener('DOMContentLoaded', function() {
        const btnEliminar = document.getElementById('btn-eliminar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const modalEliminar = document.getElementById('modal-eliminar');

        if (btnEliminar && modalEliminar) {
            btnEliminar.addEventListener('click', function() {
                modalEliminar.classList.remove('hidden');
            });
        }

        if (btnCancelar && modalEliminar) {
            btnCancelar.addEventListener('click', function() {
                modalEliminar.classList.add('hidden');
            });
        }
    });
</script>

<?php
// Incluir el pie de página
include_once '../components/footer.php';
?> 
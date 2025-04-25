<?php
// Título de la página
$pageTitle = 'Detalle de Venta';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/ventas/index.php');
}

$id = (int) $_GET['id'];

// Obtener los datos de la venta
$sql = "SELECT v.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.telefono as cliente_telefono, 
               c.direccion as cliente_direccion, ca.nombre as caja_nombre
        FROM venta v
        JOIN cliente c ON v.cliente_id = c.id
        JOIN caja ca ON v.caja_id = ca.id
        WHERE v.id = ?";
$venta = fetchOne($sql, [$id], "i");

// Si la venta no existe, redirigir
if (!$venta) {
    redirect('/views/ventas/index.php');
}

// Obtener los detalles de la venta
$sqlDetalles = "SELECT dv.*, p.nombre as producto_nombre, p.descripcion as producto_descripcion
                FROM detalleventa dv
                JOIN producto p ON dv.producto_id = p.id
                WHERE dv.venta_id = ?
                ORDER BY dv.id";
$detalles = fetchAll($sqlDetalles, [$id], "i");

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Detalle de Venta #<?php echo $venta['id']; ?></h2>
        <p class="text-gray-600 text-sm">
            Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?>
        </p>
    </div>
    <div class="flex space-x-2">
        <a href="<?php echo BASE_URL; ?>/views/ventas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            Volver
        </a>
        
        <?php if ($venta['estadoVenta'] === 'Completada'): ?>
        <a href="<?php echo BASE_URL; ?>/views/ventas/ticket.php?id=<?php echo $venta['id']; ?>" 
           class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center"
           target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
            Imprimir Ticket
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Estado de la venta -->
<div class="mb-6">
    <?php if ($venta['estadoVenta'] === 'Completada'): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="font-medium">Venta completada</p>
                </div>
            </div>
        </div>
    <?php elseif ($venta['estadoVenta'] === 'Pendiente'): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="font-medium">Venta pendiente</p>
                    <div class="mt-2 flex space-x-2">
                        <a href="<?php echo BASE_URL; ?>/views/ventas/complete.php?id=<?php echo $venta['id']; ?>" 
                           class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-lg text-sm inline-flex items-center"
                           onclick="return confirm('¿Está seguro de completar esta venta?')">
                            Completar Venta
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/ventas/cancel.php?id=<?php echo $venta['id']; ?>" 
                           class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm inline-flex items-center"
                           onclick="return confirm('¿Está seguro de cancelar esta venta?')">
                            Cancelar Venta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="font-medium">Venta cancelada</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Información del cliente -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Información del Cliente</h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-500">Nombre:</p>
                <p class="font-medium"><?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Teléfono:</p>
                <p class="font-medium"><?php echo htmlspecialchars($venta['cliente_telefono']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Dirección:</p>
                <p class="font-medium"><?php echo htmlspecialchars($venta['cliente_direccion']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Información de la venta -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Información de la Venta</h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-500">Folio:</p>
                <p class="font-medium">#<?php echo $venta['id']; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha:</p>
                <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Caja:</p>
                <p class="font-medium"><?php echo htmlspecialchars($venta['caja_nombre']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Totales -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Totales</h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-500">Subtotal:</p>
                <p class="font-medium">$<?php echo number_format($venta['montoTotal'] / 1.16, 2); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">IVA (16%):</p>
                <p class="font-medium">$<?php echo number_format($venta['montoTotal'] - ($venta['montoTotal'] / 1.16), 2); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total:</p>
                <p class="font-medium text-lg text-primary-600">$<?php echo number_format($venta['montoTotal'], 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de productos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Detalle de Productos</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Producto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Precio Unitario
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Cantidad
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Subtotal
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($detalles as $detalle): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($detalle['producto_descripcion']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">$<?php echo number_format($detalle['precioUnitario'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $detalle['cantidad']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">$<?php echo number_format($detalle['subtotal'], 2); ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right font-medium">
                        Total:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-bold text-primary-600">
                        $<?php echo number_format($venta['montoTotal'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
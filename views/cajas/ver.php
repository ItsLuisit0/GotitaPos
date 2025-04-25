<?php
// Título de la página
$pageTitle = 'Detalles de Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('cajas_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver detalles de cajas');
    redirect('/views/cajas/index.php');
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID de caja no especificado');
    redirect('/views/cajas/index.php');
}

$id = (int)$_GET['id'];

// Obtener los datos de la caja
$sql = "SELECT * 
        FROM caja 
        WHERE id = ?";
$caja = fetchOne($sql, [$id], 'i');

if (!$caja) {
    setFlashMessage('error', 'Caja no encontrada');
    redirect('/views/cajas/index.php');
}

// Obtener información financiera de la caja
$sql = "SELECT 
        COALESCE(SUM(CASE WHEN monto > 0 THEN monto ELSE 0 END), 0) as total_ingresos,
        COALESCE(SUM(CASE WHEN monto < 0 THEN ABS(monto) ELSE 0 END), 0) as total_egresos,
        COUNT(*) as total_movimientos
        FROM movimientocaja 
        WHERE caja_id = ? AND finalizado = 1";
$financieros = fetchOne($sql, [$id], 'i');

$totalIngresos = $financieros['total_ingresos'] ?? 0;
$totalEgresos = $financieros['total_egresos'] ?? 0;
$totalMovimientos = $financieros['total_movimientos'] ?? 0;
$saldoActual = ($caja['saldoInicial'] + $totalIngresos) - $totalEgresos;

// Obtener los últimos 5 movimientos
$sql = "SELECT m.*
        FROM movimientocaja m
        WHERE m.caja_id = ? AND m.finalizado = 1
        ORDER BY m.fechaHora DESC
        LIMIT 5";
$ultimosMovimientos = fetchAll($sql, [$id], 'i');

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Detalles de Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Información básica de la caja -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php echo htmlspecialchars($caja['nombre']); ?>
                <span class="ml-2 px-2 py-1 text-xs rounded-full <?php echo $caja['estado'] === 'activa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $caja['estado'] === 'activa' ? 'Activa' : 'Inactiva'; ?>
                </span>
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php if (!empty($caja['descripcion'])): ?>
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Descripción</h4>
                            <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($caja['descripcion'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($caja['responsable'])): ?>
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Responsable</h4>
                            <p class="text-gray-800"><?php echo htmlspecialchars($caja['responsable']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha de Creación</h4>
                        <p class="text-gray-800"><?php echo date('d/m/Y H:i', strtotime($caja['fechaCreacion'])); ?></p>
                    </div>
                    
                    <?php if ($caja['fechaActualizacion']): ?>
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Última Actualización</h4>
                            <p class="text-gray-800"><?php echo date('d/m/Y H:i', strtotime($caja['fechaActualizacion'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Saldo Inicial</h4>
                        <p class="text-gray-800 font-semibold">$<?php echo number_format($caja['saldoInicial'], 2); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Total de Ingresos</h4>
                        <p class="text-green-600 font-semibold">$<?php echo number_format($totalIngresos, 2); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Total de Egresos</h4>
                        <p class="text-red-600 font-semibold">$<?php echo number_format($totalEgresos, 2); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Saldo Actual</h4>
                        <p class="text-xl font-bold <?php echo $saldoActual >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            $<?php echo number_format($saldoActual, 2); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones y estadísticas -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Acciones</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <a href="<?php echo BASE_URL; ?>/views/cajas/editar.php?id=<?php echo $id; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Caja
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $id; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Ver Todos los Movimientos
                </a>
                
                <?php if ($caja['estado'] === 'activa' && hasPermission('cajas_movimientos')): ?>
                <a href="<?php echo BASE_URL; ?>/views/cajas/nuevo-movimiento.php?id=<?php echo $id; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Registrar Movimiento
                </a>
                <?php else: ?>
                <button disabled 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-400 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Registrar Movimiento
                </button>
                <p class="text-xs text-center text-gray-500 mt-1">
                    <?php echo $caja['estado'] === 'inactiva' ? 'La caja está inactiva' : 'No tienes permisos suficientes'; ?>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="mt-8">
                <h4 class="text-sm font-medium text-gray-500 mb-2">Información Adicional</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Total de Movimientos</span>
                        <span class="font-semibold"><?php echo $totalMovimientos; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Estado</span>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $caja['estado'] === 'activa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $caja['estado'] === 'activa' ? 'Activa' : 'Inactiva'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimos movimientos -->
<div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Últimos Movimientos</h3>
        <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $id; ?>" class="text-primary-600 hover:text-primary-800 text-sm">
            Ver todos
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($ultimosMovimientos) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($ultimosMovimientos as $movimiento): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($movimiento['fechaHora'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $movimiento['monto'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $movimiento['monto'] > 0 ? 'Ingreso' : 'Egreso'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($movimiento['concepto']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($movimiento['responsable'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $movimiento['monto'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                $<?php echo number_format(abs($movimiento['monto']), 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                No hay movimientos registrados para esta caja.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
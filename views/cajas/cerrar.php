<?php
// Título de la página
$pageTitle = 'Cerrar Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/cajas/index.php');
}

$id = (int) $_GET['id'];

// Obtener los datos de la caja
$sql = "SELECT c.*, 
        (SELECT SUM(m.monto) FROM movimientocaja m WHERE m.caja_id = c.id AND m.finalizado = 1) as totalMovimientos
        FROM caja c
        WHERE c.id = ?";
$caja = fetchOne($sql, [$id], "i");

// Si la caja no existe o no está abierta, redirigir
if (!$caja) {
    $_SESSION['error'] = "La caja seleccionada no existe.";
    redirect('/views/cajas/index.php');
}

if (!$caja['estaAbierta']) {
    $_SESSION['error'] = "La caja ya está cerrada.";
    redirect('/views/cajas/index.php');
}

// Calcular saldo final
$saldoInicial = $caja['saldoInicial'];
$totalMovimientos = $caja['totalMovimientos'] ?? 0;
$saldoFinal = $saldoInicial + $totalMovimientos;

// Variables para el formulario
$saldoFinalDeclarado = $saldoFinal;
$diferencia = 0;
$observaciones = '';
$errors = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $saldoFinalDeclarado = isset($_POST['saldoFinalDeclarado']) ? (float) $_POST['saldoFinalDeclarado'] : 0;
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Calcular diferencia
    $diferencia = $saldoFinalDeclarado - $saldoFinal;
    
    // Validaciones
    if ($saldoFinalDeclarado < 0) {
        $errors['saldoFinalDeclarado'] = 'El saldo final no puede ser negativo';
    }
    
    // Si no hay errores, cerrar la caja
    if (empty($errors)) {
        // Iniciar transacción
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Actualizar la caja
            $sqlUpdateCaja = "UPDATE caja SET estaAbierta = 0, fechaCierre = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sqlUpdateCaja);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Registrar un movimiento si hay diferencia
            if ($diferencia != 0) {
                $concepto = "Ajuste al cierre de caja" . ($observaciones ? ": $observaciones" : "");
                $sqlMovimiento = "INSERT INTO movimientocaja (caja_id, monto, concepto, fechaHora, finalizado) VALUES (?, ?, ?, NOW(), 1)";
                $stmt = $conn->prepare($sqlMovimiento);
                $stmt->bind_param("ids", $id, $diferencia, $concepto);
                $stmt->execute();
            }
            
            // Confirmar la transacción
            $conn->commit();
            
            // Caja cerrada exitosamente
            $_SESSION['success'] = "Caja cerrada exitosamente.";
            redirect('/views/cajas/index.php');
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollback();
            $errors['general'] = 'Error al cerrar la caja: ' . $e->getMessage();
        }
    }
}

// Obtener movimientos de la caja
$movimientosQuery = "SELECT * FROM movimientocaja WHERE caja_id = ? AND finalizado = 1 ORDER BY fechaHora DESC";
$movimientos = fetchAll($movimientosQuery, [$id], "i");

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Cerrar Caja: <?php echo htmlspecialchars($caja['nombre']); ?></h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Información de la caja -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <h3 class="text-sm font-medium text-gray-500">Información de la Caja</h3>
            <dl class="mt-2 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Nombre:</dt>
                    <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($caja['nombre']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Responsable:</dt>
                    <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($caja['responsable']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Apertura:</dt>
                    <dd class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y H:i', strtotime($caja['fechaApertura'])); ?></dd>
                </div>
            </dl>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">Movimientos</h3>
            <dl class="mt-2 space-y-1">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Saldo Inicial:</dt>
                    <dd class="text-sm font-medium text-gray-900">$<?php echo number_format($saldoInicial, 2); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Movimientos:</dt>
                    <dd class="text-sm font-medium text-gray-900">$<?php echo number_format($totalMovimientos, 2); ?></dd>
                </div>
            </dl>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">Totales</h3>
            <dl class="mt-2 space-y-1">
                <div class="flex justify-between text-lg">
                    <dt class="text-gray-500">Saldo Final:</dt>
                    <dd class="font-bold text-primary-600">$<?php echo number_format($saldoFinal, 2); ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<!-- Formulario para cerrar caja -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Cerrar Caja</h3>
    
    <form method="POST" action="">
        <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $errors['general']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Saldo Final Declarado -->
            <div>
                <label for="saldoFinalDeclarado" class="block text-sm font-medium text-gray-700 mb-1">Saldo Final Declarado <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="number" 
                        name="saldoFinalDeclarado" 
                        id="saldoFinalDeclarado"
                        step="0.01"
                        min="0"
                        value="<?php echo htmlspecialchars($saldoFinalDeclarado); ?>" 
                        class="pl-7 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['saldoFinalDeclarado']) ? 'border-red-500' : ''; ?>"
                        required
                        onchange="calcularDiferencia()"
                        onkeyup="calcularDiferencia()"
                    >
                </div>
                <?php if (isset($errors['saldoFinalDeclarado'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['saldoFinalDeclarado']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Diferencia -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Diferencia</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="text" 
                        id="diferencia"
                        value="<?php echo number_format($diferencia, 2); ?>" 
                        class="pl-7 w-full rounded-md border-gray-300 bg-gray-50 shadow-sm text-gray-700"
                        readonly
                    >
                </div>
                <p class="mt-1 text-xs text-gray-500">Valor positivo: Sobra dinero | Valor negativo: Falta dinero</p>
            </div>
            
            <!-- Observaciones -->
            <div class="md:col-span-2">
                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea 
                    name="observaciones" 
                    id="observaciones" 
                    rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Observaciones sobre el cierre de caja, especialmente si hay diferencias..."
                ><?php echo htmlspecialchars($observaciones); ?></textarea>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cerrar Caja
            </button>
        </div>
    </form>
</div>

<!-- Listado de movimientos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Movimientos de la Caja</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fecha y Hora
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Concepto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Monto
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($movimientos) > 0): ?>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($movimiento['fechaHora'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($movimiento['concepto']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($movimiento['monto'] > 0): ?>
                                <div class="text-sm text-green-600">+$<?php echo number_format($movimiento['monto'], 2); ?></div>
                                <?php else: ?>
                                <div class="text-sm text-red-600">-$<?php echo number_format(abs($movimiento['monto']), 2); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                            No hay movimientos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function calcularDiferencia() {
        const saldoFinal = <?php echo $saldoFinal; ?>;
        const saldoDeclarado = parseFloat(document.getElementById('saldoFinalDeclarado').value) || 0;
        const diferencia = saldoDeclarado - saldoFinal;
        
        document.getElementById('diferencia').value = diferencia.toFixed(2);
        
        // Cambiar color según la diferencia
        const diferenciaInput = document.getElementById('diferencia');
        
        if (diferencia > 0) {
            diferenciaInput.classList.remove('text-red-600', 'text-gray-700');
            diferenciaInput.classList.add('text-green-600');
        } else if (diferencia < 0) {
            diferenciaInput.classList.remove('text-green-600', 'text-gray-700');
            diferenciaInput.classList.add('text-red-600');
        } else {
            diferenciaInput.classList.remove('text-green-600', 'text-red-600');
            diferenciaInput.classList.add('text-gray-700');
        }
    }
    
    // Calcular diferencia al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        calcularDiferencia();
    });
</script>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
<?php
// Título de la página
$pageTitle = 'Nuevo Movimiento de Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('cajas_movimientos')) {
    setFlashMessage('error', 'No tienes permiso para registrar movimientos en cajas');
    redirect('/views/cajas/index.php');
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID de caja no especificado');
    redirect('/views/cajas/index.php');
}

$id = (int)$_GET['id'];

// Obtener los datos de la caja
$sql = "SELECT * FROM caja WHERE id = ?";
$caja = fetchOne($sql, [$id], 'i');

if (!$caja) {
    setFlashMessage('error', 'Caja no encontrada');
    redirect('/views/cajas/index.php');
}

// Verificar que la caja esté activa
if ($caja['estado'] !== 'activa') {
    setFlashMessage('error', 'Solo puedes registrar movimientos en cajas activas');
    redirect('/views/cajas/ver.php?id=' . $id);
}

// Inicializar variables
$tipo = 'ingreso';
$monto = '';
$concepto = '';
$referencia = '';
$errors = [];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $tipo = trim($_POST['tipo'] ?? 'ingreso');
    $monto = floatval($_POST['monto'] ?? 0);
    $concepto = trim($_POST['concepto'] ?? '');
    $referencia = trim($_POST['referencia'] ?? '');
    
    // Validaciones
    if ($monto <= 0) {
        $errors['monto'] = 'El monto debe ser mayor que cero';
    }
    
    if (empty($concepto)) {
        $errors['concepto'] = 'El concepto es obligatorio';
    } elseif (strlen($concepto) > 255) {
        $errors['concepto'] = 'El concepto no puede tener más de 255 caracteres';
    }
    
    if (strlen($referencia) > 100) {
        $errors['referencia'] = 'La referencia no puede tener más de 100 caracteres';
    }
    
    // Si no hay errores, guardar el movimiento
    if (empty($errors)) {
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Obtener el ID del usuario actual
            $usuario_id = $_SESSION['user_id'];
            
            // Insertar el movimiento
            $insertSql = "INSERT INTO movimientocaja 
                         (caja_id, usuario_id, tipo, monto, concepto, referencia, fechaHora, finalizado) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)";
                         
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param('iisdss', $id, $usuario_id, $tipo, $monto, $concepto, $referencia);
            $stmt->execute();
            $movimiento_id = $conn->insert_id;
            
            // Confirmar la transacción
            $conn->commit();
            
            // Redirigir a la página de movimientos
            setFlashMessage('success', 'Movimiento registrado correctamente');
            redirect('/views/cajas/movimientos.php?id=' . $id);
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollback();
            $errors['general'] = 'Error al registrar el movimiento: ' . $e->getMessage();
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Nuevo Movimiento de Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Información de la caja -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500">Información de la Caja</h3>
            <p class="text-lg font-semibold text-gray-800 mt-1"><?php echo htmlspecialchars($caja['nombre']); ?></p>
            <p class="text-sm text-gray-600">
                Estado: <span class="text-green-600 font-medium">Activa</span>
            </p>
        </div>
        <div class="md:text-right">
            <h3 class="text-sm font-medium text-gray-500 md:text-right">Saldo Actual</h3>
            <?php
            // Obtener saldo actual
            $saldoSql = "SELECT 
                        (saldoInicial + COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0)) as saldo_actual
                        FROM caja 
                        LEFT JOIN movimientocaja ON caja.id = movimientocaja.caja_id AND movimientocaja.finalizado = 1
                        WHERE caja.id = ?
                        GROUP BY caja.id";
            $saldoResult = fetchOne($saldoSql, [$id], 'i');
            $saldoActual = $saldoResult['saldo_actual'] ?? $caja['saldoInicial'];
            ?>
            <p class="text-2xl font-bold text-primary-600 mt-1">$<?php echo number_format($saldoActual, 2); ?></p>
        </div>
    </div>
</div>

<!-- Formulario de nuevo movimiento -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Registrar Movimiento</h3>
    </div>
    
    <form method="POST" action="" class="p-6">
        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $errors['general']; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tipo de movimiento -->
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento <span class="text-red-500">*</span></label>
                <div class="mt-1 flex">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                id="tipo_ingreso" 
                                name="tipo" 
                                type="radio" 
                                value="ingreso" 
                                <?php echo $tipo === 'ingreso' ? 'checked' : ''; ?>
                                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="tipo_ingreso" class="font-medium text-gray-700">Ingreso</label>
                            <p class="text-gray-500">Dinero que entra a la caja</p>
                        </div>
                    </div>
                    <div class="relative flex items-start ml-6">
                        <div class="flex items-center h-5">
                            <input 
                                id="tipo_egreso" 
                                name="tipo" 
                                type="radio" 
                                value="egreso" 
                                <?php echo $tipo === 'egreso' ? 'checked' : ''; ?>
                                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="tipo_egreso" class="font-medium text-gray-700">Egreso</label>
                            <p class="text-gray-500">Dinero que sale de la caja</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monto -->
            <div>
                <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="number" 
                        name="monto" 
                        id="monto" 
                        step="0.01" 
                        min="0.01"
                        value="<?php echo htmlspecialchars($monto); ?>"
                        placeholder="0.00"
                        class="focus:ring-primary-500 focus:border-primary-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md <?php echo isset($errors['monto']) ? 'border-red-500' : ''; ?>"
                        required
                    >
                </div>
                <?php if (isset($errors['monto'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['monto']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Concepto -->
            <div class="md:col-span-2">
                <label for="concepto" class="block text-sm font-medium text-gray-700 mb-1">Concepto <span class="text-red-500">*</span></label>
                <textarea 
                    name="concepto" 
                    id="concepto" 
                    rows="2"
                    class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md <?php echo isset($errors['concepto']) ? 'border-red-500' : ''; ?>"
                    placeholder="Describe el motivo de este movimiento"
                    required
                ><?php echo htmlspecialchars($concepto); ?></textarea>
                <?php if (isset($errors['concepto'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['concepto']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Referencia (opcional) -->
            <div class="md:col-span-2">
                <label for="referencia" class="block text-sm font-medium text-gray-700 mb-1">Referencia <span class="text-gray-400">(opcional)</span></label>
                <input 
                    type="text" 
                    name="referencia" 
                    id="referencia" 
                    value="<?php echo htmlspecialchars($referencia); ?>"
                    placeholder="Factura, recibo, número de orden, etc."
                    class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md <?php echo isset($errors['referencia']) ? 'border-red-500' : ''; ?>"
                >
                <?php if (isset($errors['referencia'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['referencia']; ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">Por ejemplo: número de factura, recibo, orden de compra, etc.</p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $id; ?>" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 mr-3">
                Cancelar
            </a>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Registrar Movimiento
            </button>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
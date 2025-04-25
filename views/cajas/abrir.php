<?php
// Título de la página
$pageTitle = 'Abrir Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('cajas_movimientos')) {
    setFlashMessage('error', 'No tienes permiso para abrir cajas');
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

// Verificar si la caja está activa
if ($caja['estado'] !== 'activa') {
    setFlashMessage('error', 'Solo se pueden abrir cajas activas');
    redirect('/views/cajas/ver.php?id=' . $id);
}

// Verificar si la caja ya está abierta
$sql = "SELECT * FROM aperturacierrecaja WHERE caja_id = ? AND fechaCierre IS NULL";
$apertura = fetchOne($sql, [$id], 'i');

if ($apertura) {
    setFlashMessage('error', 'La caja ya se encuentra abierta');
    redirect('/views/cajas/ver.php?id=' . $id);
}

// Procesar el formulario
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar el monto inicial
    $montoInicial = isset($_POST['montoInicial']) ? (float)$_POST['montoInicial'] : 0;
    
    if ($montoInicial < 0) {
        $errors[] = 'El monto inicial no puede ser negativo';
    }
    
    // Validar observaciones
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    if (empty($errors)) {
        // Registrar la apertura de caja
        $usuarioId = $_SESSION['user_id'];
        $fecha = date('Y-m-d H:i:s');
        
        // Iniciar transacción
        db()->begin_transaction();
        
        try {
            // Insertar el registro de apertura
            $sql = "INSERT INTO aperturacierrecaja (caja_id, usuario_id, fechaApertura, montoInicial, observacionesApertura) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = db()->prepare($sql);
            $stmt->bind_param('iisds', $id, $usuarioId, $fecha, $montoInicial, $observaciones);
            $stmt->execute();
            
            // Si todo fue exitoso, confirmar la transacción
            db()->commit();
            
            setFlashMessage('success', 'Caja abierta correctamente');
            redirect('/views/cajas/ver.php?id=' . $id);
            
        } catch (Exception $e) {
            // Si hubo un error, revertir la transacción
            db()->rollback();
            $errors[] = 'Error al abrir la caja: ' . $e->getMessage();
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Abrir Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/ver.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Información de la caja -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">
            Información de la Caja: <?php echo htmlspecialchars($caja['nombre']); ?>
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Nombre de la Caja</h4>
                <p class="text-gray-800"><?php echo htmlspecialchars($caja['nombre']); ?></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Responsable</h4>
                <p class="text-gray-800"><?php echo htmlspecialchars($caja['responsable'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                <p class="text-gray-800">
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                        Cerrada
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Errores -->
<?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
        <div class="font-medium">Por favor corrige los siguientes errores:</div>
        <ul class="mt-1 ml-5 list-disc">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Formulario de apertura -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <form method="POST" action="">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Formulario de Apertura</h3>
        </div>
        <div class="p-6">
            <div class="mb-6">
                <label for="montoInicial" class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="number" 
                        name="montoInicial" 
                        id="montoInicial" 
                        step="0.01" 
                        value="<?php echo isset($montoInicial) ? $montoInicial : '0.00'; ?>" 
                        class="focus:ring-primary-500 focus:border-primary-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                        placeholder="0.00" 
                        required
                    >
                </div>
                <p class="mt-1 text-sm text-gray-500">Ingrese el monto con el que se inicia la operación de la caja</p>
            </div>
            
            <div class="mb-6">
                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea 
                    id="observaciones" 
                    name="observaciones" 
                    rows="3" 
                    class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="Observaciones sobre la apertura de la caja (opcional)"
                ><?php echo isset($observaciones) ? htmlspecialchars($observaciones) : ''; ?></textarea>
            </div>
            
            <div class="flex justify-end">
                <a href="<?php echo BASE_URL; ?>/views/cajas/ver.php?id=<?php echo $id; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg mr-2">
                    Cancelar
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg">
                    Abrir Caja
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
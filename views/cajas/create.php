<?php
// Título de la página
$pageTitle = 'Nueva Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Variables para el formulario
$nombre = '';
$saldoInicial = '0.00';
$responsable = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$errors = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $saldoInicial = isset($_POST['saldoInicial']) ? (float) $_POST['saldoInicial'] : 0;
    $responsable = trim($_POST['responsable'] ?? '');
    $estaAbierta = isset($_POST['estaAbierta']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es obligatorio';
    }
    
    if ($saldoInicial < 0) {
        $errors['saldoInicial'] = 'El saldo inicial no puede ser negativo';
    }
    
    if (empty($responsable)) {
        $errors['responsable'] = 'El responsable es obligatorio';
    }
    
    // Si no hay errores, guardar la caja
    if (empty($errors)) {
        // Preparar la consulta
        $fechaApertura = $estaAbierta ? 'NOW()' : 'NULL';
        
        // Iniciar transacción
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Si la caja se crea abierta, establecer fecha de apertura
            if ($estaAbierta) {
                $sql = "INSERT INTO caja (nombre, responsable, saldoInicial, estaAbierta, fechaApertura) 
                        VALUES (?, ?, ?, 1, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssd", $nombre, $responsable, $saldoInicial);
            } else {
                $sql = "INSERT INTO caja (nombre, responsable, saldoInicial, estaAbierta) 
                        VALUES (?, ?, ?, 0)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssd", $nombre, $responsable, $saldoInicial);
            }
            
            $stmt->execute();
            $cajaId = $conn->insert_id;
            
            // Confirmar la transacción
            $conn->commit();
            
            // Caja guardada exitosamente
            $_SESSION['success'] = "Caja creada exitosamente.";
            redirect('/views/cajas/index.php');
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollback();
            $errors['general'] = 'Error al crear la caja: ' . $e->getMessage();
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Crear Nueva Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Formulario para crear caja -->
<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="">
        <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $errors['general']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre de la caja -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Caja <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="nombre" 
                    id="nombre" 
                    value="<?php echo htmlspecialchars($nombre); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['nombre']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['nombre'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['nombre']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Responsable -->
            <div>
                <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">Responsable <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="responsable" 
                    id="responsable" 
                    value="<?php echo htmlspecialchars($responsable); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['responsable']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['responsable'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['responsable']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Saldo Inicial -->
            <div>
                <label for="saldoInicial" class="block text-sm font-medium text-gray-700 mb-1">Saldo Inicial <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="number" 
                        name="saldoInicial" 
                        id="saldoInicial"
                        step="0.01"
                        min="0"
                        value="<?php echo htmlspecialchars($saldoInicial); ?>" 
                        class="pl-7 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['saldoInicial']) ? 'border-red-500' : ''; ?>"
                        required
                    >
                </div>
                <?php if (isset($errors['saldoInicial'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['saldoInicial']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Estado de la caja -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    name="estaAbierta" 
                    id="estaAbierta" 
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    checked
                >
                <label for="estaAbierta" class="ml-2 block text-sm text-gray-900">
                    Abrir caja inmediatamente
                </label>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Crear Caja
            </button>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
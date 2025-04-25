<?php
// Título de la página
$pageTitle = 'Crear Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
$userType = $_SESSION['user_type'] ?? '';

// Solo administradores pueden crear cajas
$tienePermiso = ($userType === 'admin');

if (!$tienePermiso) {
    $_SESSION['error'] = "No tienes permisos para crear cajas.";
    redirect('/views/cajas/index.php');
}

// Variables para el formulario
$nombre = '';
$saldoInicial = '0.00';
$responsable = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$descripcion = '';
$estado = 'activa';
$errors = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $saldoInicial = isset($_POST['saldoInicial']) ? (float) $_POST['saldoInicial'] : 0;
    $responsable = trim($_POST['responsable'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'activa';
    
    // Validaciones
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre de la caja es obligatorio';
    } elseif (strlen($nombre) > 100) {
        $errors['nombre'] = 'El nombre no puede exceder 100 caracteres';
    }
    
    if ($saldoInicial < 0) {
        $errors['saldoInicial'] = 'El saldo inicial no puede ser negativo';
    }
    
    if (empty($responsable)) {
        $errors['responsable'] = 'El responsable es obligatorio';
    }
    
    // Verificar si ya existe una caja con el mismo nombre
    $sqlVerificar = "SELECT id FROM caja WHERE nombre = ?";
    $cajaExistente = fetchOne($sqlVerificar, [$nombre], "s");
    
    if ($cajaExistente) {
        $errors['nombre'] = 'Ya existe una caja con ese nombre';
    }
    
    // Si no hay errores, guardar la caja
    if (empty($errors)) {
        // Iniciar transacción
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Insertar la caja
            $sqlCaja = "INSERT INTO caja (nombre, responsable, saldoInicial, descripcion, estado, estaAbierta, fechaCreacion) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            // Si el estado es activa, la caja está abierta por defecto
            $estaAbierta = ($estado === 'activa') ? 1 : 0;
            
            $stmt = $conn->prepare($sqlCaja);
            $stmt->bind_param("ssdssi", $nombre, $responsable, $saldoInicial, $descripcion, $estado, $estaAbierta);
            $stmt->execute();
            
            $cajaId = $conn->insert_id;
            
            // Si tiene saldo inicial mayor a cero, crear un movimiento inicial
            if ($saldoInicial > 0 && $estaAbierta) {
                $sqlMovimiento = "INSERT INTO movimientocaja (caja_id, tipo, monto, concepto, fechaHora, usuario_id, finalizado) 
                                VALUES (?, 'ingreso', ?, 'Saldo inicial', NOW(), ?, 1)";
                
                $userId = $_SESSION['user_id'];
                
                $stmt = $conn->prepare($sqlMovimiento);
                $stmt->bind_param("idi", $cajaId, $saldoInicial, $userId);
                $stmt->execute();
            }
            
            // Confirmar transacción
            $conn->commit();
            
            // Redirigir con mensaje de éxito
            $_SESSION['success'] = "Caja '{$nombre}' creada exitosamente.";
            redirect('/views/cajas/index.php');
            
        } catch (Exception $e) {
            // Revertir en caso de error
            $conn->rollback();
            $errors['general'] = 'Error al crear la caja: ' . $e->getMessage();
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Crear Nueva Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Mensaje de error general -->
<?php if (isset($errors['general'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $errors['general']; ?></p>
</div>
<?php endif; ?>

<!-- Formulario de creación de caja -->
<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre de la caja -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Caja <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
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
                    id="responsable" 
                    name="responsable" 
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
                        id="saldoInicial" 
                        name="saldoInicial" 
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
                <p class="mt-1 text-xs text-gray-500">Cantidad de dinero con la que inicia la caja.</p>
            </div>
            
            <!-- Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                <select 
                    id="estado" 
                    name="estado" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="activa" <?php echo $estado === 'activa' ? 'selected' : ''; ?>>Activa</option>
                    <option value="inactiva" <?php echo $estado === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Las cajas inactivas no permiten realizar movimientos.</p>
            </div>
            
            <!-- Descripción -->
            <div class="md:col-span-2">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea 
                    id="descripcion" 
                    name="descripcion" 
                    rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                ><?php echo htmlspecialchars($descripcion); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">Información adicional sobre la caja (opcional).</p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg mr-2">
                Cancelar
            </a>
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Crear Caja
            </button>
        </div>
    </form>
</div>

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
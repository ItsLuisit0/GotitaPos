<?php
// Título de la página
$pageTitle = 'Editar Caja';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('cajas_editar')) {
    setFlashMessage('error', 'No tienes permiso para editar cajas');
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

// Inicializar variables
$nombre = $caja['nombre'];
$descripcion = $caja['descripcion'];
$responsable = $caja['responsable'];
$estado = $caja['estado'];
$errors = [];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar los datos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $estado = $_POST['estado'] ?? 'activa';
    
    // Validar nombre
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre de la caja es obligatorio';
    } else if (strlen($nombre) > 50) {
        $errors['nombre'] = 'El nombre no puede tener más de 50 caracteres';
    } else {
        // Verificar si ya existe otra caja con el mismo nombre
        $sql = "SELECT COUNT(*) as count FROM caja WHERE nombre = ? AND id != ?";
        $result = fetchOne($sql, [$nombre, $id], 'si');
        if ($result['count'] > 0) {
            $errors['nombre'] = 'Ya existe una caja con este nombre';
        }
    }
    
    // Validar estado
    if (!in_array($estado, ['activa', 'inactiva'])) {
        $errors['estado'] = 'El estado seleccionado no es válido';
    }
    
    // Si no hay errores, actualizar la caja
    if (empty($errors)) {
        $sql = "UPDATE caja SET 
                nombre = ?, 
                descripcion = ?, 
                responsable = ?, 
                estado = ?, 
                fechaActualizacion = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $params = [$nombre, $descripcion, $responsable, $estado, $id];
        $types = 'ssssi';
        
        $result = executeQuery($sql, $params, $types);
        
        if ($result) {
            setFlashMessage('success', 'Caja actualizada correctamente');
            redirect('/views/cajas/index.php');
        } else {
            setFlashMessage('error', 'Error al actualizar la caja');
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Editar Caja</h2>
    <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="" class="space-y-6">
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Se encontraron errores:</p>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Caja <span class="text-red-500">*</span></label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['nombre']) ? 'border-red-300' : ''; ?>">
                <?php if (isset($errors['nombre'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['nombre']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                <input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($responsable); ?>"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                <select id="estado" name="estado" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['estado']) ? 'border-red-300' : ''; ?>">
                    <option value="activa" <?php echo $estado === 'activa' ? 'selected' : ''; ?>>Activa</option>
                    <option value="inactiva" <?php echo $estado === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                </select>
                <?php if (isset($errors['estado'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['estado']; ?></p>
                <?php endif; ?>
            </div>

            <div class="md:col-span-2">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>
        </div>

        <div class="flex justify-end space-x-4 pt-4">
            <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">Guardar Cambios</button>
        </div>
    </form>
</div>

<div class="mt-8 bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Información de la Caja</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <p class="text-sm text-gray-500">Fecha de Creación</p>
            <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($caja['fechaCreacion'])); ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Última Actualización</p>
            <p class="font-medium">
                <?php echo $caja['fechaActualizacion'] ? date('d/m/Y H:i', strtotime($caja['fechaActualizacion'])) : 'N/A'; ?>
            </p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Saldo Inicial</p>
            <p class="font-medium">$<?php echo number_format($caja['saldoInicial'], 2); ?></p>
        </div>
        
        <?php
        // Obtener saldo actual
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) as total_egresos
                FROM movimientocaja 
                WHERE caja_id = ? AND finalizado = 1";
        $result = fetchOne($sql, [$id], 'i');
        $totalIngresos = $result['total_ingresos'] ?? 0;
        $totalEgresos = $result['total_egresos'] ?? 0;
        $saldoActual = ($caja['saldoInicial'] + $totalIngresos) - $totalEgresos;
        ?>
        
        <div>
            <p class="text-sm text-gray-500">Total Ingresos</p>
            <p class="font-medium text-green-600">$<?php echo number_format($totalIngresos, 2); ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Total Egresos</p>
            <p class="font-medium text-red-600">$<?php echo number_format($totalEgresos, 2); ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Saldo Actual</p>
            <p class="font-medium <?php echo $saldoActual >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                $<?php echo number_format($saldoActual, 2); ?>
            </p>
        </div>
    </div>
    
    <div class="mt-6">
        <a href="<?php echo BASE_URL; ?>/views/cajas/movimientos.php?id=<?php echo $id; ?>" class="inline-flex items-center text-primary-600 hover:text-primary-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            Ver historial de movimientos
        </a>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
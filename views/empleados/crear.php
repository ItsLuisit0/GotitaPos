<?php
// Título de la página
$pageTitle = 'Nuevo Empleado';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('empleados_crear')) {
    setFlashMessage('error', 'No tienes permiso para crear empleados');
    redirect('/views/empleados/index.php');
}

// Inicializar variables
$nombre = '';
$email = '';
$telefono = '';
$direccion = '';
$puesto = '';
$fechaNacimiento = '';
$fechaContratacion = date('Y-m-d');
$estado = 'activo';
$errors = [];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $puesto = trim($_POST['puesto'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $fechaContratacion = trim($_POST['fechaContratacion'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    
    // Validaciones
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es obligatorio';
    } elseif (strlen($nombre) > 100) {
        $errors['nombre'] = 'El nombre no puede exceder los 100 caracteres';
    }
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo electrónico no es válido';
        } elseif (strlen($email) > 100) {
            $errors['email'] = 'El correo electrónico no puede exceder los 100 caracteres';
        }
        
        // Verificar si el email ya existe
        $checkEmailSQL = "SELECT id FROM empleado WHERE email = ? LIMIT 1";
        $existente = fetchOne($checkEmailSQL, [$email], 's');
        if ($existente) {
            $errors['email'] = 'Este correo electrónico ya está registrado';
        }
    }
    
    if (!empty($telefono) && strlen($telefono) > 20) {
        $errors['telefono'] = 'El teléfono no puede exceder los 20 caracteres';
    }
    
    if (!empty($direccion) && strlen($direccion) > 255) {
        $errors['direccion'] = 'La dirección no puede exceder los 255 caracteres';
    }
    
    if (!empty($puesto) && strlen($puesto) > 50) {
        $errors['puesto'] = 'El puesto no puede exceder los 50 caracteres';
    }
    
    if (!empty($fechaNacimiento)) {
        $date = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$date || $date->format('Y-m-d') !== $fechaNacimiento) {
            $errors['fechaNacimiento'] = 'La fecha de nacimiento no es válida';
        }
    }
    
    if (empty($fechaContratacion)) {
        $errors['fechaContratacion'] = 'La fecha de contratación es obligatoria';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $fechaContratacion);
        if (!$date || $date->format('Y-m-d') !== $fechaContratacion) {
            $errors['fechaContratacion'] = 'La fecha de contratación no es válida';
        }
    }
    
    if (!in_array($estado, ['activo', 'inactivo'])) {
        $errors['estado'] = 'El estado seleccionado no es válido';
    }
    
    // Si no hay errores, guardar el empleado
    if (empty($errors)) {
        $sql = "INSERT INTO empleado (nombre, email, telefono, direccion, puesto, fechaNacimiento, fechaContratacion, estado, fechaCreacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [$nombre, $email, $telefono, $direccion, $puesto, $fechaNacimiento ?: null, $fechaContratacion, $estado];
        $types = 'ssssssss';
        
        $result = execute($sql, $params, $types);
        
        if ($result) {
            $empleadoId = $result;
            setFlashMessage('success', 'Empleado creado correctamente');
            
            // Redirigir a la página de detalles o al listado
            if (isset($_POST['crear_usuario']) && $_POST['crear_usuario'] == '1' && hasPermission('usuarios_crear')) {
                redirect('/views/usuarios/crear.php?empleado_id=' . $empleadoId);
            } else {
                redirect('/views/empleados/index.php');
            }
        } else {
            $errors['general'] = 'Error al guardar el empleado. Intente nuevamente.';
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Nuevo Empleado</h2>
    <a href="<?php echo BASE_URL; ?>/views/empleados/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Alertas de error general -->
<?php if (isset($errors['general'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $errors['general']; ?></p>
</div>
<?php endif; ?>

<!-- Formulario para crear empleado -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Información del Empleado</h3>
    </div>
    
    <form method="POST" action="" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
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
            
            <!-- Correo electrónico -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                >
                <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['email']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Teléfono -->
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input 
                    type="text" 
                    name="telefono" 
                    id="telefono" 
                    value="<?php echo htmlspecialchars($telefono); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['telefono']) ? 'border-red-500' : ''; ?>"
                >
                <?php if (isset($errors['telefono'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['telefono']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Puesto -->
            <div>
                <label for="puesto" class="block text-sm font-medium text-gray-700 mb-1">Puesto</label>
                <input 
                    type="text" 
                    name="puesto" 
                    id="puesto" 
                    value="<?php echo htmlspecialchars($puesto); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['puesto']) ? 'border-red-500' : ''; ?>"
                >
                <?php if (isset($errors['puesto'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['puesto']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Fecha de Nacimiento -->
            <div>
                <label for="fechaNacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                <input 
                    type="date" 
                    name="fechaNacimiento" 
                    id="fechaNacimiento" 
                    value="<?php echo htmlspecialchars($fechaNacimiento); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['fechaNacimiento']) ? 'border-red-500' : ''; ?>"
                >
                <?php if (isset($errors['fechaNacimiento'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['fechaNacimiento']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Fecha de Contratación -->
            <div>
                <label for="fechaContratacion" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Contratación <span class="text-red-500">*</span></label>
                <input 
                    type="date" 
                    name="fechaContratacion" 
                    id="fechaContratacion" 
                    value="<?php echo htmlspecialchars($fechaContratacion); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['fechaContratacion']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['fechaContratacion'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['fechaContratacion']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                <select 
                    name="estado" 
                    id="estado" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['estado']) ? 'border-red-500' : ''; ?>"
                    required
                >
                    <option value="activo" <?php echo $estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo $estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
                <?php if (isset($errors['estado'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['estado']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Dirección -->
            <div class="md:col-span-2">
                <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <textarea 
                    name="direccion" 
                    id="direccion" 
                    rows="2" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['direccion']) ? 'border-red-500' : ''; ?>"
                ><?php echo htmlspecialchars($direccion); ?></textarea>
                <?php if (isset($errors['direccion'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['direccion']; ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (hasPermission('usuarios_crear')): ?>
            <!-- Opción para crear usuario -->
            <div class="md:col-span-2">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input 
                            id="crear_usuario" 
                            name="crear_usuario" 
                            type="checkbox" 
                            value="1"
                            class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                        >
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="crear_usuario" class="font-medium text-gray-700">Crear cuenta de usuario después de guardar</label>
                        <p class="text-gray-500">Serás redirigido para crear las credenciales de acceso al sistema</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-6 flex justify-end">
            <a href="<?php echo BASE_URL; ?>/views/empleados/index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2">
                Cancelar
            </a>
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-lg">
                Guardar Empleado
            </button>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
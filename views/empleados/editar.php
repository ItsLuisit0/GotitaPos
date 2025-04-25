<?php
// Título de la página
$pageTitle = "Editar Empleado";

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('empleados_editar')) {
    setFlashMessage('error', 'No tienes permiso para editar empleados');
    redirect('/views/empleados/index.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID de empleado no proporcionado');
    redirect('/views/empleados/index.php');
}

$id = (int)$_GET['id'];

// Obtener los datos del empleado
$sql = "SELECT * FROM empleado WHERE id = ?";
$empleado = fetchOne($sql, [$id], 'i');

if (!$empleado) {
    setFlashMessage('error', 'Empleado no encontrado');
    redirect('/views/empleados/index.php');
}

// Inicializar variables
$nombre = $empleado['nombre'];
$email = $empleado['email'];
$telefono = $empleado['telefono'];
$direccion = $empleado['direccion'];
$puesto = $empleado['puesto'];
$fecha_nacimiento = $empleado['fecha_nacimiento'];
$fecha_contratacion = $empleado['fecha_contratacion'];
$estado = $empleado['estado'];
$errors = [];

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $puesto = trim($_POST['puesto'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    
    // Validar campos requeridos
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es requerido';
    }
    
    if (empty($email)) {
        $errors['email'] = 'El correo electrónico es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo electrónico no es válido';
    }
    
    if (empty($telefono)) {
        $errors['telefono'] = 'El teléfono es requerido';
    }
    
    if (empty($puesto)) {
        $errors['puesto'] = 'El puesto es requerido';
    }
    
    if (empty($fecha_contratacion)) {
        $errors['fecha_contratacion'] = 'La fecha de contratación es requerida';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_contratacion)) {
        $errors['fecha_contratacion'] = 'La fecha de contratación debe tener formato YYYY-MM-DD';
    }
    
    if (!empty($fecha_nacimiento) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
        $errors['fecha_nacimiento'] = 'La fecha de nacimiento debe tener formato YYYY-MM-DD';
    }
    
    // Verificar si ya existe otro empleado con el mismo email o teléfono
    $sqlCheck = "SELECT id FROM empleado WHERE (email = ? OR telefono = ?) AND id != ?";
    $empleadoExistente = fetchOne($sqlCheck, [$email, $telefono, $id], 'ssi');
    
    if ($empleadoExistente) {
        $errors['general'] = 'Ya existe un empleado con el mismo correo electrónico o teléfono';
    }
    
    // Si no hay errores, actualizar el empleado
    if (empty($errors)) {
        try {
            // Preparar la consulta SQL para actualizar el empleado
            $sql = "UPDATE empleado SET 
                    nombre = ?, 
                    email = ?, 
                    telefono = ?, 
                    direccion = ?, 
                    puesto = ?, 
                    fecha_nacimiento = ?, 
                    fecha_contratacion = ?, 
                    estado = ?,
                    actualizado_en = NOW()
                    WHERE id = ?";
            
            // Ejecutar la consulta
            $conn = getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", 
                $nombre, 
                $email, 
                $telefono, 
                $direccion, 
                $puesto, 
                $fecha_nacimiento, 
                $fecha_contratacion, 
                $estado,
                $id
            );
            $stmt->execute();
            
            // Verificar si la actualización fue exitosa
            if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                setFlashMessage('success', 'Empleado actualizado correctamente');
                redirect('/views/empleados/ver.php?id=' . $id);
            } else {
                $errors['general'] = 'No se realizaron cambios en el empleado';
            }
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al actualizar el empleado: ' . $e->getMessage();
        }
    }
}

// Incluir el encabezado
include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Editar Empleado</h1>
            <a href="/views/empleados/ver.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver
            </a>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $errors['general']; ?></p>
            </div>
        <?php endif; ?>
        
        <form action="/views/empleados/editar.php?id=<?php echo $id; ?>" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($nombre); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <?php if (isset($errors['nombre'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['nombre']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <?php if (isset($errors['email'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['email']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Teléfono -->
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                    <input type="text" name="telefono" id="telefono" value="<?php echo htmlspecialchars($telefono); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <?php if (isset($errors['telefono'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['telefono']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Puesto -->
                <div>
                    <label for="puesto" class="block text-sm font-medium text-gray-700 mb-1">Puesto *</label>
                    <input type="text" name="puesto" id="puesto" value="<?php echo htmlspecialchars($puesto); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <?php if (isset($errors['puesto'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['puesto']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Dirección -->
                <div class="md:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea name="direccion" id="direccion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($direccion); ?></textarea>
                </div>
                
                <!-- Fecha de nacimiento -->
                <div>
                    <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?php echo htmlspecialchars($fecha_nacimiento); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <?php if (isset($errors['fecha_nacimiento'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['fecha_nacimiento']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Fecha de contratación -->
                <div>
                    <label for="fecha_contratacion" class="block text-sm font-medium text-gray-700 mb-1">Fecha de contratación *</label>
                    <input type="date" name="fecha_contratacion" id="fecha_contratacion" value="<?php echo htmlspecialchars($fecha_contratacion); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <?php if (isset($errors['fecha_contratacion'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $errors['fecha_contratacion']; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Estado -->
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" id="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="activo" <?php echo $estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4">
                <a href="/views/empleados/ver.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">Cancelar</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../components/footer.php'; ?> 
<?php
// Título de la página
$pageTitle = 'Editar Cliente';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/clientes/index.php');
}

$id = (int) $_GET['id'];

// Obtener los datos del cliente
$sql = "SELECT * FROM cliente WHERE id = ?";
$cliente = fetchOne($sql, [$id], "i");

// Si el cliente no existe, redirigir
if (!$cliente) {
    redirect('/views/clientes/index.php');
}

// Variables para el formulario
$nombre = $cliente['nombre'];
$apellido = $cliente['apellido'];
$email = $cliente['email'];
$telefono = $cliente['telefono'];
$direccion = $cliente['direccion'];
$errors = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es obligatorio';
    }
    
    if (empty($apellido)) {
        $errors['apellido'] = 'El apellido es obligatorio';
    }
    
    if (empty($email)) {
        $errors['email'] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El email no es válido';
    }
    
    if (empty($telefono)) {
        $errors['telefono'] = 'El teléfono es obligatorio';
    }
    
    if (empty($direccion)) {
        $errors['direccion'] = 'La dirección es obligatoria';
    }
    
    // Si no hay errores, actualizar el cliente
    if (empty($errors)) {
        // Preparar la consulta
        $sql = "UPDATE cliente SET nombre = ?, apellido = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?";
        $params = [$nombre, $apellido, $email, $telefono, $direccion, $id];
        $types = "sssssi";
        
        // Ejecutar la consulta
        $result = execute($sql, $params, $types);
        
        if ($result !== false) {
            // Cliente actualizado exitosamente
            $success = true;
        } else {
            $errors['general'] = 'Error al actualizar el cliente. Intente nuevamente.';
        }
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Editar Cliente</h2>
    <a href="<?php echo BASE_URL; ?>/views/clientes/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Alerta de éxito -->
<?php if ($success): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm">Cliente actualizado exitosamente.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de cliente -->
<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="">
        <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $errors['general']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
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
            
            <!-- Apellido -->
            <div>
                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="apellido" 
                    id="apellido" 
                    value="<?php echo htmlspecialchars($apellido); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['apellido']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['apellido'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['apellido']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['email']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Teléfono -->
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="telefono" 
                    id="telefono" 
                    value="<?php echo htmlspecialchars($telefono); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['telefono']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['telefono'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['telefono']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Dirección -->
            <div class="md:col-span-2">
                <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección <span class="text-red-500">*</span></label>
                <textarea 
                    name="direccion" 
                    id="direccion" 
                    rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['direccion']) ? 'border-red-500' : ''; ?>"
                    required
                ><?php echo htmlspecialchars($direccion); ?></textarea>
                <?php if (isset($errors['direccion'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['direccion']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Actualizar Cliente
            </button>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
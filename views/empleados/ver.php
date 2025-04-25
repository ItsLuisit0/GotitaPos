<?php
// Título de la página
$pageTitle = 'Detalles de Empleado';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('empleados_ver')) {
    setFlashMessage('error', 'No tienes permiso para ver detalles de empleados');
    redirect('/views/empleados/index.php');
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID de empleado no especificado');
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

// Verificar si el empleado tiene una cuenta de usuario
$sqlUsuario = "SELECT u.id, u.username, u.tipo, u.ultima_sesion 
               FROM usuario u 
               WHERE u.empleado_id = ?";
$usuario = fetchOne($sqlUsuario, [$id], 'i');

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Detalles del Empleado</h2>
    <a href="<?php echo BASE_URL; ?>/views/empleados/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Alertas de éxito o error -->
<?php if (isset($_SESSION['success'])): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $_SESSION['success']; ?></p>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
    <p><?php echo $_SESSION['error']; ?></p>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Información personal -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Información Personal</h3>
            <?php if (hasPermission('empleados_editar')): ?>
            <a href="<?php echo BASE_URL; ?>/views/empleados/editar.php?id=<?php echo $id; ?>" class="text-primary-600 hover:text-primary-800 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </a>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <div class="mb-6 flex items-center">
                <div class="h-16 w-16 bg-gray-200 rounded-full flex items-center justify-center text-2xl text-gray-600 mr-4">
                    <?php echo strtoupper(substr($empleado['nombre'], 0, 1)); ?>
                </div>
                <div>
                    <h4 class="text-xl font-medium text-gray-900"><?php echo htmlspecialchars($empleado['nombre']); ?></h4>
                    <?php if (!empty($empleado['puesto'])): ?>
                    <p class="text-gray-500"><?php echo htmlspecialchars($empleado['puesto']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Información de contacto</h5>
                    <div class="space-y-3">
                        <?php if (!empty($empleado['email'])): ?>
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Correo electrónico</p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($empleado['email']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($empleado['telefono'])): ?>
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Teléfono</p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($empleado['telefono']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($empleado['direccion'])): ?>
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Dirección</p>
                                <p class="text-sm text-gray-500"><?php echo nl2br(htmlspecialchars($empleado['direccion'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Información laboral</h5>
                    <div class="space-y-3">
                        <?php if (!empty($empleado['fechaContratacion'])): ?>
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Fecha de contratación</p>
                                <p class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($empleado['fechaContratacion'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($empleado['fechaNacimiento'])): ?>
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Fecha de nacimiento</p>
                                <p class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($empleado['fechaNacimiento'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Estado</p>
                                <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php echo $empleado['estado'] === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"
                                >
                                    <?php echo ucfirst($empleado['estado']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones y cuenta de usuario -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Acciones</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if (hasPermission('empleados_editar')): ?>
                <a href="<?php echo BASE_URL; ?>/views/empleados/editar.php?id=<?php echo $id; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Información
                </a>
                <?php endif; ?>
                
                <?php if (!$usuario && hasPermission('usuarios_crear')): ?>
                <a href="<?php echo BASE_URL; ?>/views/usuarios/crear.php?empleado_id=<?php echo $id; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Crear Cuenta de Usuario
                </a>
                <?php elseif ($usuario && hasPermission('usuarios_editar')): ?>
                <a href="<?php echo BASE_URL; ?>/views/usuarios/editar.php?id=<?php echo $usuario['id']; ?>" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Administrar Cuenta de Usuario
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('empleados_cambiar_estado')): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>/views/empleados/cambiar_estado.php">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="estado" value="<?php echo $empleado['estado'] === 'activo' ? 'inactivo' : 'activo'; ?>">
                    <button type="submit" 
                        class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white <?php echo $empleado['estado'] === 'activo' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $empleado['estado'] === 'activo' ? 'focus:ring-red-500' : 'focus:ring-green-500'; ?>">
                        <?php if ($empleado['estado'] === 'activo'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Desactivar Empleado
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Activar Empleado
                        <?php endif; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <?php if ($usuario): ?>
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-500 mb-3">Información de Cuenta</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-500">Usuario</p>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($usuario['username']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Rol</p>
                            <p class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                <?php 
                                    if ($usuario['tipo'] == 'admin') echo 'bg-red-100 text-red-800';
                                    elseif ($usuario['tipo'] == 'supervisor') echo 'bg-blue-100 text-blue-800';
                                    else echo 'bg-green-100 text-green-800';
                                ?>"
                            >
                                <?php echo ucfirst($usuario['tipo']); ?>
                            </p>
                        </div>
                        <?php if (!empty($usuario['ultima_sesion'])): ?>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500">Última sesión</p>
                            <p class="text-sm"><?php echo date('d/m/Y H:i:s', strtotime($usuario['ultima_sesion'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
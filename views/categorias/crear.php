<?php
// Título de la página
$pageTitle = "Crear Nueva Categoría";

// Incluir archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para crear categorías
if (!hasPermission('crear_categoria')) {
    setFlashMessage('error', 'No tienes permisos para crear categorías');
    redirect('/views/dashboard.php');
}

// Inicializar variables
$errores = [];
$nombre = '';
$descripcion = '';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanear los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre de la categoría es obligatorio';
    } elseif (strlen($nombre) > 100) {
        $errores['nombre'] = 'El nombre no puede tener más de 100 caracteres';
    }

    if (strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede tener más de 500 caracteres';
    }

    // Verificar si ya existe una categoría con el mismo nombre
    $sqlCheck = "SELECT id FROM categorias WHERE nombre = ?";
    $existeCategoria = fetchOne($sqlCheck, [$nombre], "s");
    
    if ($existeCategoria) {
        $errores['nombre'] = 'Ya existe una categoría con este nombre';
    }

    // Guardar si no hay errores
    if (empty($errores)) {
        // Iniciar transacción
        beginTransaction();
        
        try {
            // Insertar la nueva categoría
            $sqlInsert = "INSERT INTO categorias (
                nombre, 
                descripcion, 
                creado_por, 
                fecha_creacion,
                estado
            ) VALUES (?, ?, ?, NOW(), 1)";
            
            $result = execute($sqlInsert, [
                $nombre, 
                $descripcion, 
                $_SESSION['user_id']
            ], "ssi");
            
            if ($result) {
                $categoria_id = getLastInsertId();
                
                // Confirmar la transacción
                commit();
                setFlashMessage('success', 'Categoría creada correctamente');
                redirect('/views/categorias/ver.php?id=' . $categoria_id);
            } else {
                throw new Exception("Error al crear la categoría");
            }
        } catch (Exception $e) {
            // Deshacer cambios en caso de error
            rollback();
            setFlashMessage('error', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }
}

// Incluir el encabezado
include_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Alertas -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="alert" class="mb-4 <?php echo getAlertClass($_SESSION['flash_message']['type']); ?>">
            <div class="flex items-center">
                <?php echo getAlertIcon($_SESSION['flash_message']['type']); ?>
                <span><?php echo $_SESSION['flash_message']['message']; ?></span>
            </div>
        </div>
        <?php clearFlashMessage(); ?>
    <?php endif; ?>

    <!-- Encabezado y acciones -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Crear Nueva Categoría</h1>
        <div>
            <a href="/views/categorias/index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Formulario de creación -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="p-6">
            <form method="POST" action="" class="space-y-4">
                <!-- Errores generales -->
                <?php if (!empty($errores) && isset($errores['general'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $errores['general']; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Nombre -->
                <div class="mb-4">
                    <label for="nombre" class="block text-gray-700 text-sm font-bold mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nombre" name="nombre" 
                           class="shadow appearance-none border <?php echo isset($errores['nombre']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <?php if (isset($errores['nombre'])): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $errores['nombre']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Descripción -->
                <div class="mb-6">
                    <label for="descripcion" class="block text-gray-700 text-sm font-bold mb-2">
                        Descripción
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="4"
                              class="shadow appearance-none border <?php echo isset($errores['descripcion']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              ><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <?php if (isset($errores['descripcion'])): ?>
                        <p class="text-red-500 text-xs italic mt-1"><?php echo $errores['descripcion']; ?></p>
                    <?php endif; ?>
                    <p class="text-gray-500 text-xs mt-1">Máximo 500 caracteres</p>
                </div>

                <!-- Botones -->
                <div class="flex items-center justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-save mr-1"></i> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(function() {
        const alert = document.getElementById('alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);
</script>

<?php
// Incluir el pie de página
include_once '../components/footer.php';
?> 
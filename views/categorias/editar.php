<?php
// Título de la página
$title = "Editar Categoría";
// Incluir archivo de configuración
require_once '../../config/config.php';
// Incluir el header
include_once '../components/header.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para editar categorías
if (!hasPermission('editar_categoria')) {
    setFlashMessage('error', 'No tienes permisos para editar categorías');
    redirect('/views/dashboard.php');
}

// Inicializar variables
$errores = [];
$nombre = '';
$descripcion = '';
$estado = 1;
$categoria = null;

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de categoría inválido');
    redirect('/views/categorias/index.php');
}

$categoria_id = (int)$_GET['id'];

// Obtener los datos de la categoría
$sql = "SELECT * FROM categorias WHERE id = ?";
$categoria = fetchOne($sql, [$categoria_id], "i");

if (!$categoria) {
    setFlashMessage('error', 'La categoría no existe');
    redirect('/views/categorias/index.php');
}

// Establecer valores iniciales
$nombre = $categoria['nombre'];
$descripcion = $categoria['descripcion'] ?? '';
$estado = $categoria['estado'];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar nombre (requerido y único)
    if (empty($_POST['nombre'])) {
        $errores['nombre'] = 'El nombre es obligatorio';
    } else {
        $nombre = trim($_POST['nombre']);
        
        // Verificar si ya existe una categoría con ese nombre (excluyendo la actual)
        $sqlCheck = "SELECT id FROM categorias WHERE nombre = ? AND id != ?";
        $result = fetchOne($sqlCheck, [$nombre, $categoria_id], "si");
        
        if ($result) {
            $errores['nombre'] = 'Ya existe una categoría con este nombre';
        }
    }
    
    // Validar descripción (opcional)
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validar estado
    $estado = isset($_POST['estado']) ? 1 : 0;
    
    // Si no hay errores, actualizar la categoría
    if (empty($errores)) {
        $sql = "UPDATE categorias SET 
                nombre = ?, 
                descripcion = ?, 
                estado = ?, 
                actualizado_por = ?,
                fecha_actualizacion = NOW()
                WHERE id = ?";
        
        $params = [$nombre, $descripcion, $estado, $_SESSION['user_id'], $categoria_id];
        $types = "ssiii";
        
        if (executeQuery($sql, $params, $types)) {
            setFlashMessage('success', 'Categoría actualizada correctamente');
            redirect('/views/categorias/ver.php?id=' . $categoria_id);
        } else {
            setFlashMessage('error', 'Error al actualizar la categoría');
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Categoría</h1>
        <div>
            <a href="/views/categorias/index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                Volver al Listado
            </a>
            <a href="/views/categorias/ver.php?id=<?php echo $categoria_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded ml-2">
                Ver Detalles
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="alert" class="mb-4 rounded-lg p-4 <?php echo $_SESSION['flash_message_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php echo $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form action="/views/categorias/editar.php?id=<?php echo $categoria_id; ?>" method="POST">
            <div class="mb-4">
                <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline 
                    <?php echo isset($errores['nombre']) ? 'border-red-500' : ''; ?>">
                <?php if (isset($errores['nombre'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errores['nombre']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="descripcion" class="block text-gray-700 font-bold mb-2">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">Estado</label>
                <div class="flex items-center">
                    <input type="checkbox" id="estado" name="estado" <?php echo $estado ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600">
                    <label for="estado" class="ml-2 text-gray-700">Activa</label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Actualizar Categoría
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('alert');
        if (alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = 0;
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }, 5000);
        }
    });
</script>

<?php include_once '../components/footer.php'; ?> 
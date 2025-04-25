<?php
// Título de la página
$pageTitle = 'Crear Producto';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Obtener las categorías
$categoriasQuery = "SELECT id, nombre FROM categoria ORDER BY nombre";
$categorias = fetchAll($categoriasQuery);

// Variables para el formulario
$nombre = '';
$descripcion = '';
$precioUnitario = '';
$stockDisponible = '';
$categoria_id = '';
$errors = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precioUnitario = trim($_POST['precioUnitario'] ?? '');
    $stockDisponible = trim($_POST['stockDisponible'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    
    // Validaciones
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es obligatorio';
    }
    
    if (empty($descripcion)) {
        $errors['descripcion'] = 'La descripción es obligatoria';
    }
    
    if (empty($precioUnitario)) {
        $errors['precioUnitario'] = 'El precio es obligatorio';
    } elseif (!is_numeric($precioUnitario) || $precioUnitario <= 0) {
        $errors['precioUnitario'] = 'El precio debe ser un número mayor a 0';
    }
    
    if (empty($stockDisponible)) {
        $errors['stockDisponible'] = 'El stock es obligatorio';
    } elseif (!is_numeric($stockDisponible) || $stockDisponible < 0) {
        $errors['stockDisponible'] = 'El stock debe ser un número mayor o igual a 0';
    }
    
    if (empty($categoria_id)) {
        $errors['categoria_id'] = 'La categoría es obligatoria';
    }
    
    // Si no hay errores, guardar el producto
    if (empty($errors)) {
        // Iniciar transacción
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Insertar el producto
            $sql = "INSERT INTO producto (nombre, descripcion, precioUnitario, stockDisponible, categoria_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdii", $nombre, $descripcion, $precioUnitario, $stockDisponible, $categoria_id);
            $stmt->execute();
            
            $productoId = $conn->insert_id;
            
            // Actualizar el inventario
            $sqlInventario = "INSERT INTO inventario (producto_id, cantidad) VALUES (?, ?)";
            $stmtInventario = $conn->prepare($sqlInventario);
            $stmtInventario->bind_param("ii", $productoId, $stockDisponible);
            $stmtInventario->execute();
            
            // Confirmar la transacción
            $conn->commit();
            
            // Producto guardado exitosamente
            $success = true;
            
            // Limpiar el formulario
            $nombre = '';
            $descripcion = '';
            $precioUnitario = '';
            $stockDisponible = '';
            $categoria_id = '';
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollback();
            $errors['general'] = 'Error al guardar el producto: ' . $e->getMessage();
        }
        
        $conn->close();
    }
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Crear Nuevo Producto</h2>
    <a href="<?php echo BASE_URL; ?>/views/productos/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
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
            <p class="text-sm">Producto guardado exitosamente.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de producto -->
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
            
            <!-- Categoría -->
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                <select 
                    name="categoria_id" 
                    id="categoria_id" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['categoria_id']) ? 'border-red-500' : ''; ?>"
                    required
                >
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['categoria_id'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['categoria_id']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Precio -->
            <div>
                <label for="precioUnitario" class="block text-sm font-medium text-gray-700 mb-1">Precio Unitario <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input 
                        type="number" 
                        name="precioUnitario" 
                        id="precioUnitario" 
                        step="0.01"
                        min="0"
                        value="<?php echo htmlspecialchars($precioUnitario); ?>" 
                        class="pl-7 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['precioUnitario']) ? 'border-red-500' : ''; ?>"
                        required
                    >
                </div>
                <?php if (isset($errors['precioUnitario'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['precioUnitario']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Stock -->
            <div>
                <label for="stockDisponible" class="block text-sm font-medium text-gray-700 mb-1">Stock Disponible <span class="text-red-500">*</span></label>
                <input 
                    type="number" 
                    name="stockDisponible" 
                    id="stockDisponible" 
                    min="0"
                    value="<?php echo htmlspecialchars($stockDisponible); ?>" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['stockDisponible']) ? 'border-red-500' : ''; ?>"
                    required
                >
                <?php if (isset($errors['stockDisponible'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['stockDisponible']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Descripción -->
            <div class="md:col-span-2">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <textarea 
                    name="descripcion" 
                    id="descripcion" 
                    rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php echo isset($errors['descripcion']) ? 'border-red-500' : ''; ?>"
                    required
                ><?php echo htmlspecialchars($descripcion); ?></textarea>
                <?php if (isset($errors['descripcion'])): ?>
                <p class="mt-1 text-sm text-red-500"><?php echo $errors['descripcion']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Guardar Producto
            </button>
        </div>
    </form>
</div>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
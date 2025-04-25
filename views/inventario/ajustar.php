<?php
// Título de la página
$pageTitle = 'Ajustar Inventario';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('inventario_ajustar')) {
    setFlashMessage('error', 'No tienes permiso para ajustar el inventario');
    redirect('/views/dashboard/index.php');
}

// Variables para el formulario
$productoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$producto = null;
$errores = [];

// Si hay un ID de producto, obtener sus datos
if ($productoId > 0) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM producto p 
            LEFT JOIN categoria c ON p.categoria_id = c.id
            WHERE p.id = ?";
    $producto = fetchOne($sql, [$productoId], 'i');
    
    if (!$producto) {
        setFlashMessage('error', 'El producto no existe');
        redirect('/views/inventario/index.php');
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    $productoId = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    
    // Validaciones
    if ($productoId <= 0) {
        $errores[] = 'Debe seleccionar un producto válido';
    }
    
    if ($cantidad <= 0) {
        $errores[] = 'La cantidad debe ser mayor a cero';
    }
    
    if (!in_array($tipo, ['entrada', 'salida'])) {
        $errores[] = 'El tipo de ajuste debe ser entrada o salida';
    }
    
    if (empty($motivo)) {
        $errores[] = 'Debe ingresar un motivo para el ajuste';
    }
    
    // Si no hay errores, realizar el ajuste
    if (empty($errores)) {
        // Verificar si la tabla historial_inventario existe
        $checkTableSql = "SHOW TABLES LIKE 'historial_inventario'";
        $tableExists = fetchAll($checkTableSql);
        
        // Si la tabla no existe, crearla
        if (empty($tableExists)) {
            $createTableSql = "CREATE TABLE historial_inventario (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                producto_id INT UNSIGNED NOT NULL,
                tipo ENUM('entrada', 'salida') NOT NULL,
                cantidad INT NOT NULL,
                motivo VARCHAR(255) NOT NULL,
                usuario_id INT UNSIGNED,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES producto(id)
            )";
            execute($createTableSql);
        }
        
        // Iniciar transacción
        $conn = getConnection();
        mysqli_begin_transaction($conn);
        
        try {
            // Actualizar el stock
            if ($tipo === 'entrada') {
                $nuevoStock = $producto['stockDisponible'] + $cantidad;
            } else {
                $nuevoStock = $producto['stockDisponible'] - $cantidad;
                
                // Verificar que no quede stock negativo
                if ($nuevoStock < 0) {
                    throw new Exception("No hay suficiente stock disponible para realizar esta operación");
                }
            }
            
            // Actualizar el stock en la tabla de productos
            $sqlUpdate = "UPDATE producto SET stockDisponible = ? WHERE id = ?";
            if (!execute($sqlUpdate, [$nuevoStock, $productoId], 'ii')) {
                throw new Exception('Error al actualizar el stock del producto');
            }
            
            // Registrar en el historial
            $sqlHistorial = "INSERT INTO historial_inventario (producto_id, tipo, cantidad, motivo, usuario_id, fecha) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmtHistorial = $conn->prepare($sqlHistorial);
            $stmtHistorial->bind_param("isisi", $productoId, $tipo, $cantidad, $motivo, $_SESSION['user_id']);
            $stmtHistorial->execute();
            
            // Commitear la transacción
            mysqli_commit($conn);
            
            setFlashMessage('success', "Stock actualizado correctamente. Nuevo stock: {$nuevoStock}");
            redirect("/views/inventario/ajustar.php?id={$productoId}");
            
        } catch (Exception $e) {
            // Rollback en caso de error
            mysqli_rollback($conn);
            $errores[] = "Error al actualizar el inventario: " . $e->getMessage();
        }
    }
}

// Si no hay producto seleccionado, obtener la lista de productos
$productos = [];
if (!$producto) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM producto p 
            LEFT JOIN categoria c ON p.categoria_id = c.id
            ORDER BY p.nombre ASC";
    $productos = fetchAll($sql);
}

// Obtener el historial de ajustes
$sql = "SHOW TABLES LIKE 'historial_inventario'";
$tableExists = fetchAll($sql);

if (!empty($tableExists)) {
    $sql = "SELECT h.*, p.nombre as producto_nombre, u.nombre as usuario_nombre
            FROM historial_inventario h
            LEFT JOIN producto p ON h.producto_id = p.id
            LEFT JOIN usuario u ON h.usuario_id = u.id
            ORDER BY h.fecha DESC
            LIMIT 10";
    $historial = fetchAll($sql);
} else {
    $historial = [];
}

// Incluir el encabezado
require_once '../components/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">
        <?php echo $producto ? 'Ajustar Inventario: ' . htmlspecialchars($producto['nombre']) : 'Ajustar Inventario'; ?>
    </h2>
    <a href="<?php echo BASE_URL; ?>/views/inventario/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver al Inventario
    </a>
</div>

<?php if (!empty($errores)): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
    <p class="font-bold">Errores:</p>
    <ul class="list-disc list-inside">
        <?php foreach ($errores as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-md p-6">
    <form method="POST" action="" class="space-y-6">
        <?php if (!$producto): ?>
        <!-- Selección de producto si no viene uno por GET -->
        <div>
            <label for="producto_id" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Producto</label>
            <select 
                id="producto_id" 
                name="producto_id" 
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">-- Seleccione un producto --</option>
                <?php foreach ($productos as $prod): ?>
                <option value="<?php echo $prod['id']; ?>">
                    <?php echo htmlspecialchars($prod['nombre'] . ' - ' . $prod['categoria_nombre'] . ' (Stock: ' . $prod['stockDisponible'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <!-- Información del producto seleccionado -->
        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Información del Producto</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Producto:</p>
                    <p class="font-medium"><?php echo htmlspecialchars($producto['nombre']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Categoría:</p>
                    <p class="font-medium"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Stock Actual:</p>
                    <p class="font-medium"><?php echo $producto['stockDisponible']; ?> unidades</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Precio:</p>
                    <p class="font-medium">$<?php echo number_format($producto['precioUnitario'], 2); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Campos para el ajuste -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                <input 
                    type="number" 
                    id="cantidad" 
                    name="cantidad" 
                    min="1" 
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                <p class="mt-1 text-sm text-gray-500">Ingrese la cantidad de unidades a ajustar</p>
            </div>
            
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ajuste</label>
                <select 
                    id="tipo" 
                    name="tipo" 
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">-- Seleccione el tipo --</option>
                    <option value="entrada">Entrada de stock</option>
                    <option value="salida">Salida de stock</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Indique si es un aumento o disminución de inventario</p>
            </div>
        </div>
        
        <div>
            <label for="motivo" class="block text-sm font-medium text-gray-700 mb-1">Motivo del Ajuste</label>
            <textarea 
                id="motivo" 
                name="motivo" 
                rows="3" 
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Describa el motivo del ajuste de inventario..."
            ></textarea>
            <p class="mt-1 text-sm text-gray-500">Ejemplos: Nueva compra, Inventario inicial, Producto dañado, Error en conteo, etc.</p>
        </div>
        
        <div class="pt-4 border-t border-gray-200">
            <button type="submit" class="w-full md:w-auto bg-primary-600 hover:bg-primary-700 text-white py-2 px-6 rounded-lg inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Realizar Ajuste
            </button>
        </div>
    </form>
</div>

<div class="mt-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Últimos Ajustes de Inventario</h3>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            No hay registros de ajustes de inventario
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $ajuste): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($ajuste['fecha'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($ajuste['producto_nombre']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($ajuste['tipo'] === 'entrada'): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Entrada
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Salida
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $ajuste['cantidad']; ?> unidades
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars(substr($ajuste['motivo'], 0, 50) . (strlen($ajuste['motivo']) > 50 ? '...' : '')); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($ajuste['usuario_nombre']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-dismiss de las alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // Validación adicional al enviar el formulario
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const cantidad = document.getElementById('cantidad').value;
            const tipo = document.getElementById('tipo').value;
            
            if (parseInt(cantidad) <= 0) {
                event.preventDefault();
                alert('La cantidad debe ser mayor a cero');
            }
            
            if (tipo === 'salida') {
                <?php if ($producto): ?>
                const stockActual = <?php echo $producto['stockDisponible']; ?>;
                if (parseInt(cantidad) > stockActual) {
                    event.preventDefault();
                    alert('No puede retirar más unidades de las que hay en stock');
                }
                <?php endif; ?>
            }
        });
    });
</script>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 
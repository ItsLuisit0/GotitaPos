<?php
// Título de la página
$pageTitle = "Configuración del Sistema";

// Incluir el archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    // Redirigir al usuario a la página de inicio de sesión si no está autenticado
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos de administrador
if (!hasPermission('ver_configuracion')) {
    // Establecer mensaje de error
    setFlashMessage('error', 'No tienes permisos para acceder a esta página');
    // Redirigir al dashboard
    redirect('/views/dashboard.php');
}

// Verificar si la tabla de configuración existe y crearla si no existe
$sqlCheckTable = "SHOW TABLES LIKE 'configuracion'";
$tableExists = fetchAll($sqlCheckTable);

if (empty($tableExists)) {
    // La tabla no existe, crearla
    $sqlCreateTable = "CREATE TABLE configuracion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_negocio VARCHAR(255) NOT NULL,
        telefono VARCHAR(20),
        direccion TEXT,
        correo VARCHAR(100),
        moneda VARCHAR(10) DEFAULT 'MXN',
        simbolo_moneda VARCHAR(5) DEFAULT '$',
        impuestos TINYINT(1) DEFAULT 0,
        porcentaje_impuesto DECIMAL(5,2) DEFAULT 16.00
    )";
    
    execute($sqlCreateTable);
    
    // Insertar datos por defecto
    $sqlInsertDefault = "INSERT INTO configuracion (id, nombre_negocio, telefono, direccion, correo, moneda, simbolo_moneda, impuestos, porcentaje_impuesto) 
                          VALUES (1, 'Mi Negocio', '', '', '', 'MXN', '$', 0, 16.00)";
    execute($sqlInsertDefault);
}

// Obtener la configuración actual del sistema
$sql = "SELECT * FROM configuracion WHERE id = 1";
$config = fetchOne($sql);

// Si no existe la configuración, crear un registro por defecto
if (!$config) {
    $sqlInsertDefault = "INSERT INTO configuracion (id, nombre_negocio, telefono, direccion, correo, moneda, simbolo_moneda, impuestos, porcentaje_impuesto) 
                          VALUES (1, 'Mi Negocio', '', '', '', 'MXN', '$', 0, 16.00)";
    execute($sqlInsertDefault);
    $config = fetchOne($sql);
}

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar los datos del formulario
    $nombreNegocio = htmlspecialchars(trim($_POST['nombre_negocio'] ?? ''));
    $telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''));
    $direccion = htmlspecialchars(trim($_POST['direccion'] ?? ''));
    $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
    $moneda = htmlspecialchars(trim($_POST['moneda'] ?? ''));
    $simboloMoneda = htmlspecialchars(trim($_POST['simbolo_moneda'] ?? ''));
    $impuestos = isset($_POST['impuestos']) ? 1 : 0;
    $porcentajeImpuesto = filter_var($_POST['porcentaje_impuesto'] ?? 0, FILTER_VALIDATE_FLOAT);
    
    // Validar los datos
    $errores = [];
    
    if (empty($nombreNegocio)) {
        $errores[] = "El nombre del negocio es obligatorio";
    }
    
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if ($impuestos && ($porcentajeImpuesto <= 0 || $porcentajeImpuesto > 100)) {
        $errores[] = "El porcentaje de impuesto debe ser mayor a 0 y menor o igual a 100";
    }
    
    // Si no hay errores, actualizar la configuración
    if (empty($errores)) {
        $sql = "UPDATE configuracion SET 
                nombre_negocio = ?, 
                telefono = ?, 
                direccion = ?, 
                correo = ?, 
                moneda = ?, 
                simbolo_moneda = ?, 
                impuestos = ?, 
                porcentaje_impuesto = ? 
                WHERE id = 1";
        
        $params = [
            $nombreNegocio,
            $telefono,
            $direccion,
            $correo,
            $moneda,
            $simboloMoneda,
            $impuestos,
            $porcentajeImpuesto
        ];
        
        $result = execute($sql, $params, "ssssssid");
        
        if ($result) {
            // Actualizar la configuración en la sesión
            setFlashMessage('success', 'Configuración actualizada correctamente');
            
            // En lugar de redireccionar, refrescar los datos de configuración
            $config = fetchOne("SELECT * FROM configuracion WHERE id = 1");
        } else {
            setFlashMessage('error', 'Error al actualizar la configuración');
        }
    }
}

// Incluir el header DESPUÉS del procesamiento
include_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Configuración del Sistema</h1>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="alert" class="mb-6 <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded">
            <p><?php echo $_SESSION['flash_message']; ?></p>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        <script>
            setTimeout(function() {
                document.getElementById('alert').style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="mb-6 bg-red-100 border-red-500 text-red-700 border-l-4 p-4 rounded">
            <ul class="list-disc pl-4">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información General -->
                <div class="col-span-2">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">Información General</h2>
                </div>
                
                <div class="mb-4">
                    <label for="nombre_negocio" class="block text-gray-700 font-bold mb-2">Nombre del Negocio *</label>
                    <input type="text" id="nombre_negocio" name="nombre_negocio" 
                           value="<?php echo htmlspecialchars($config['nombre_negocio'] ?? ''); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           required>
                </div>
                
                <div class="mb-4">
                    <label for="telefono" class="block text-gray-700 font-bold mb-2">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" 
                           value="<?php echo htmlspecialchars($config['telefono'] ?? ''); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label for="direccion" class="block text-gray-700 font-bold mb-2">Dirección</label>
                    <textarea id="direccion" name="direccion" 
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              rows="3"><?php echo htmlspecialchars($config['direccion'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="correo" class="block text-gray-700 font-bold mb-2">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" 
                           value="<?php echo htmlspecialchars($config['correo'] ?? ''); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <!-- Configuración de Moneda e Impuestos -->
                <div class="col-span-2 mt-4">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">Configuración de Moneda e Impuestos</h2>
                </div>
                
                <div class="mb-4">
                    <label for="moneda" class="block text-gray-700 font-bold mb-2">Moneda</label>
                    <input type="text" id="moneda" name="moneda" 
                           value="<?php echo htmlspecialchars($config['moneda'] ?? 'MXN'); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-sm text-gray-600 mt-1">Ejemplo: MXN, USD, EUR</p>
                </div>
                
                <div class="mb-4">
                    <label for="simbolo_moneda" class="block text-gray-700 font-bold mb-2">Símbolo de Moneda</label>
                    <input type="text" id="simbolo_moneda" name="simbolo_moneda" 
                           value="<?php echo htmlspecialchars($config['simbolo_moneda'] ?? '$'); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-sm text-gray-600 mt-1">Ejemplo: $, €, £</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Impuestos</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="impuestos" name="impuestos" 
                               <?php echo ($config['impuestos'] ?? 0) ? 'checked' : ''; ?> 
                               class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <label for="impuestos">Habilitar impuestos en ventas</label>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="porcentaje_impuesto" class="block text-gray-700 font-bold mb-2">Porcentaje de Impuesto (%)</label>
                    <input type="number" id="porcentaje_impuesto" name="porcentaje_impuesto" 
                           value="<?php echo htmlspecialchars($config['porcentaje_impuesto'] ?? '16'); ?>" 
                           step="0.01" min="0" max="100"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../components/footer.php'; ?> 
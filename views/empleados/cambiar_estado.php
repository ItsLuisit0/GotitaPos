<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar permisos
if (!hasPermission('empleados_cambiar_estado')) {
    setFlashMessage('error', 'No tienes permiso para cambiar el estado de empleados');
    redirect('/views/empleados/index.php');
}

// Verificar que se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/views/empleados/index.php');
}

// Verificar que se han proporcionado los datos necesarios
if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['estado']) || empty($_POST['estado'])) {
    setFlashMessage('error', 'Datos incompletos');
    redirect('/views/empleados/index.php');
}

$id = (int)$_POST['id'];
$estado = $_POST['estado'];

// Validar el estado
if (!in_array($estado, ['activo', 'inactivo'])) {
    setFlashMessage('error', 'Estado no válido');
    redirect('/views/empleados/index.php');
}

// Verificar que el empleado existe
$sqlCheck = "SELECT id, nombre FROM empleado WHERE id = ?";
$empleado = fetchOne($sqlCheck, [$id], 'i');

if (!$empleado) {
    setFlashMessage('error', 'Empleado no encontrado');
    redirect('/views/empleados/index.php');
}

// Iniciar transacción
$conn = getConnection();
$conn->begin_transaction();

try {
    // Actualizar el estado del empleado
    $sql = "UPDATE empleado SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $estado, $id);
    $stmt->execute();
    
    // Si el estado es inactivo, también desactivamos su cuenta de usuario si tiene una
    if ($estado === 'inactivo') {
        $sqlUsuario = "UPDATE usuario SET activo = 0 WHERE empleado_id = ?";
        $stmt = $conn->prepare($sqlUsuario);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    
    // Confirmar la transacción
    $conn->commit();
    
    // Mensaje de éxito
    setFlashMessage('success', 'Estado del empleado "' . $empleado['nombre'] . '" cambiado a ' . ucfirst($estado));
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    setFlashMessage('error', 'Error al cambiar el estado del empleado: ' . $e->getMessage());
}

// Redirigir a la página de detalles del empleado o al listado
if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index') {
    redirect('/views/empleados/index.php');
} else {
    redirect('/views/empleados/ver.php?id=' . $id);
}
?> 
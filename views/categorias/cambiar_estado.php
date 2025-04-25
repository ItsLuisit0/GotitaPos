<?php
// Incluir archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    // Redirigir al usuario a la página de inicio de sesión si no está autenticado
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para cambiar el estado de categorías
if (!hasPermission('editar_categoria')) {
    // Establecer mensaje de error
    setFlashMessage('error', 'No tienes permisos para cambiar el estado de categorías');
    // Redirigir a la página principal
    redirect('/views/dashboard.php');
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Método no permitido');
    redirect('/views/categorias/index.php');
}

// Validar los datos de entrada
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['estado'])) {
    setFlashMessage('error', 'Datos incompletos o inválidos');
    redirect('/views/categorias/index.php');
}

$categoria_id = (int) $_POST['id'];
$nuevo_estado = (int) $_POST['estado']; // 0 = inactivo, 1 = activo

// Verificar si el nuevo estado es válido
if ($nuevo_estado !== 0 && $nuevo_estado !== 1) {
    setFlashMessage('error', 'Estado inválido');
    redirect('/views/categorias/ver.php?id=' . $categoria_id);
}

// Verificar si la categoría existe
$sql = "SELECT id, nombre, estado FROM categorias WHERE id = ?";
$categoria = fetchOne($sql, [$categoria_id], "i");

if (!$categoria) {
    setFlashMessage('error', 'La categoría no existe');
    redirect('/views/categorias/index.php');
}

// Si el estado actual es igual al nuevo estado, no hacer cambios
if ((int)$categoria['estado'] === $nuevo_estado) {
    $estado_texto = $nuevo_estado ? 'activa' : 'inactiva';
    setFlashMessage('info', 'La categoría ya está ' . $estado_texto);
    redirect('/views/categorias/ver.php?id=' . $categoria_id);
}

// Iniciar transacción
beginTransaction();

try {
    // Actualizar el estado de la categoría
    $sql = "UPDATE categorias SET 
            estado = ?, 
            actualizado_por = ?, 
            fecha_actualizacion = NOW() 
            WHERE id = ?";
    
    $resultado = executeQuery($sql, [
        $nuevo_estado, 
        $_SESSION['usuario_id'], 
        $categoria_id
    ], "iii");
    
    if (!$resultado) {
        throw new Exception('Error al actualizar el estado de la categoría');
    }

    // Si la categoría se está desactivando, verificar si hay productos activos con esta categoría
    if ($nuevo_estado === 0) {
        $sql = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND estado = 1";
        $productos = fetchOne($sql, [$categoria_id], "i");
        
        if ($productos && $productos['total'] > 0) {
            // Opcionalmente, se podría desactivar automáticamente los productos de esta categoría
            // Por ahora, solo se emite una advertencia
            setFlashMessage('warning', 'Hay ' . $productos['total'] . ' productos activos asociados a esta categoría que podrían verse afectados');
        }
    }
    
    // Confirmar transacción
    commit();
    
    $estado_texto = $nuevo_estado ? 'activada' : 'desactivada';
    setFlashMessage('success', 'La categoría ha sido ' . $estado_texto . ' correctamente');
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    rollback();
    setFlashMessage('error', $e->getMessage());
}

// Redirigir a la página de detalles de la categoría o a la página de origen
$redirect_url = isset($_POST['redirect']) && !empty($_POST['redirect']) 
    ? $_POST['redirect'] 
    : '/views/categorias/ver.php?id=' . $categoria_id;

redirect($redirect_url); 
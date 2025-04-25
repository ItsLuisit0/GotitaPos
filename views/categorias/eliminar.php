<?php
// Incluir archivo de configuración
require_once '../../config/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    redirect('/views/login.php');
}

// Verificar si el usuario tiene permisos para eliminar categorías
if (!hasPermission('eliminar_categoria')) {
    setFlashMessage('error', 'No tienes permisos para eliminar categorías');
    redirect('/views/dashboard.php');
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Método no permitido');
    redirect('/views/categorias/index.php');
}

// Verificar si se proporcionó un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID de categoría inválido');
    redirect('/views/categorias/index.php');
}

$categoria_id = (int)$_POST['id'];

// Verificar si la categoría existe
$sqlCheck = "SELECT id, nombre FROM categorias WHERE id = ?";
$categoria = fetchOne($sqlCheck, [$categoria_id], "i");

if (!$categoria) {
    setFlashMessage('error', 'La categoría no existe');
    redirect('/views/categorias/index.php');
}

// Contar productos asociados a esta categoría
$sqlProductos = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ?";
$resultProductos = fetchOne($sqlProductos, [$categoria_id], "i");
$totalProductos = $resultProductos['total'] ?? 0;

// Iniciar transacción
$conn = getConnection();
$conn->begin_transaction();

try {
    // Si hay productos asociados, establecer su categoría a NULL
    if ($totalProductos > 0) {
        $sqlUpdateProductos = "UPDATE productos SET categoria_id = NULL WHERE categoria_id = ?";
        $stmtUpdateProductos = $conn->prepare($sqlUpdateProductos);
        $stmtUpdateProductos->bind_param("i", $categoria_id);
        
        if (!$stmtUpdateProductos->execute()) {
            throw new Exception("Error al actualizar los productos: " . $stmtUpdateProductos->error);
        }
        $stmtUpdateProductos->close();
    }
    
    // Eliminar la categoría
    $sqlDelete = "DELETE FROM categorias WHERE id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $categoria_id);
    
    if (!$stmtDelete->execute()) {
        throw new Exception("Error al eliminar la categoría: " . $stmtDelete->error);
    }
    
    if ($stmtDelete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar la categoría");
    }
    
    $stmtDelete->close();
    $conn->commit();
    
    // Mensaje de éxito
    $mensaje = "Categoría '" . htmlspecialchars($categoria['nombre']) . "' eliminada correctamente";
    if ($totalProductos > 0) {
        $mensaje .= ". Se han actualizado " . $totalProductos . " productos.";
    }
    
    setFlashMessage('success', $mensaje);
    
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage('error', 'Error al eliminar la categoría: ' . $e->getMessage());
}

$conn->close();

// Redireccionar al listado de categorías
redirect('/views/categorias/index.php');
?> 
<?php
// Funciones de ayuda para la aplicación

/**
 * Establece un mensaje flash para ser mostrado en la siguiente petición
 * 
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje a mostrar
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION[$type] = $message;
}

/**
 * Verifica si un usuario tiene un permiso específico
 * 
 * @param string $permission Permiso a verificar
 * @return bool True si tiene permiso, false en caso contrario
 */
function hasPermission($permission) {
    // Esta es una implementación temporal, en producción debería consultar a la base de datos
    $userType = $_SESSION['user_type'] ?? '';
    
    // Permisos para administradores
    if ($userType === 'admin') {
        return true; // Los administradores tienen todos los permisos
    }
    
    // Permisos para empleados básicos
    $employeePermissions = [
        'ventas_ver', 'ventas_crear', 
        'clientes_ver', 
        'productos_ver',
        'inventario_ver',
        'cajas_ver', 'cajas_movimientos'
    ];
    
    // Permisos para supervisores
    $supervisorPermissions = array_merge($employeePermissions, [
        'clientes_crear', 'clientes_editar',
        'productos_ver', 
        'inventario_ver', 'inventario_ajustar',
        'cajas_crear', 'cajas_editar', 'cajas_eliminar'
    ]);
    
    if ($userType === 'supervisor' && in_array($permission, $supervisorPermissions)) {
        return true;
    }
    
    if ($userType === 'empleado' && in_array($permission, $employeePermissions)) {
        return true;
    }
    
    return false;
}

/**
 * Obtiene los permisos de un usuario según su rol en el sistema
 * 
 * @param int $userId ID del usuario
 * @return array Arreglo con los permisos del usuario
 */
function obtenerPermisosUsuario($userId) {
    // Esta es una implementación simplificada que devuelve permisos basados en el tipo de usuario
    // En un sistema real, esto debería consultar la base de datos
    
    $userType = $_SESSION['user_type'] ?? '';
    $permisos = [];
    
    // Módulos disponibles
    $modulos = ['ventas', 'clientes', 'productos', 'inventario', 'cajas', 'empleados', 'configuracion'];
    
    foreach ($modulos as $modulo) {
        // Por defecto, ningún permiso
        $permiso = [
            'modulo' => $modulo,
            'ver' => 0,
            'crear' => 0,
            'editar' => 0,
            'eliminar' => 0
        ];
        
        // Asignar permisos según el rol
        if ($userType === 'admin') {
            // Administradores tienen todos los permisos
            $permiso['ver'] = 1;
            $permiso['crear'] = 1;
            $permiso['editar'] = 1;
            $permiso['eliminar'] = 1;
        } elseif ($userType === 'supervisor') {
            // Supervisores tienen permisos limitados
            $permiso['ver'] = 1;
            $permiso['crear'] = ($modulo !== 'configuracion') ? 1 : 0;
            $permiso['editar'] = ($modulo !== 'configuracion') ? 1 : 0;
            $permiso['eliminar'] = 0;
        } elseif ($userType === 'empleado') {
            // Empleados solo pueden ver ciertos módulos
            $permiso['ver'] = in_array($modulo, ['ventas', 'clientes', 'productos', 'inventario', 'cajas']) ? 1 : 0;
            $permiso['crear'] = in_array($modulo, ['ventas']) ? 1 : 0;
            $permiso['editar'] = 0;
            $permiso['eliminar'] = 0;
        }
        
        $permisos[] = $permiso;
    }
    
    return $permisos;
} 
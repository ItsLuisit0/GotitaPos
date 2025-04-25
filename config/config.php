<?php
// Configuración global de la aplicación
session_start();

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de la aplicación
define('APP_NAME', 'La Gotita H2O');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/pospurificadora');

// Incluir la conexión a la base de datos
require_once __DIR__ . '/database.php';

// Incluir archivo de funciones auxiliares
require_once __DIR__ . '/functions.php';

// Función para redirigir
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Función para verificar si un usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Función para manejar errores API
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?> 
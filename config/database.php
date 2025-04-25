<?php
// Configuración de la conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gotitaoficial1');

// Establecer la conexión
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer codificación UTF-8
    $conn->set_charset("utf8");
    
    return $conn;
}

// Función para ejecutar consultas con manejo de errores
function executeQuery($sql, $params = [], $types = "") {
    $conn = getConnection();
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    
    // Bind de parámetros si existen
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Ejecutar consulta
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Función para obtener un solo registro
function fetchOne($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Función para obtener múltiples registros
function fetchAll($sql, $params = [], $types = "") {
    $result = executeQuery($sql, $params, $types);
    
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Función para insertar o actualizar registros
function execute($sql, $params = [], $types = "") {
    $conn = getConnection();
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    
    // Bind de parámetros si existen
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Ejecutar consulta
    $result = $stmt->execute();
    
    $lastId = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    if ($lastId) {
        return $lastId;
    }
    
    return $result;
}
?> 
<?php
// Incluir configuración
require_once '../../config/config.php';

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir al login
redirect('/views/auth/login.php');
?> 
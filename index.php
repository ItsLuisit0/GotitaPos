<?php
// Incluir configuración
require_once 'config/config.php';

// Verificar si hay sesión iniciada
if (!isAuthenticated()) {
    // Redirigir al login
    redirect('/views/auth/login.php');
}

// Si hay sesión, redirigir al dashboard
redirect('/views/dashboard/index.php');
?> 
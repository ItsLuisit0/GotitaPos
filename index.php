<?php
require_once 'config/config.php';

if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

redirect('/views/dashboard/index.php');
?> 

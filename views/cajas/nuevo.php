<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Verificar permisos
if (!hasPermission('cajas_crear')) {
    header('Location: ../../dashboard.php?error=No tiene permisos para crear cajas');
    exit;
}

$errores = [];
$exito = false;

// Valores por defecto para el formulario
$datosForm = [
    'nombre' => '',
    'descripcion' => '',
    'estado' => 'activa',
    'saldo_inicial' => 0
];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar nombre
    $datosForm['nombre'] = trim($_POST['nombre'] ?? '');
    if (empty($datosForm['nombre'])) {
        $errores['nombre'] = 'El nombre es obligatorio';
    } elseif (strlen($datosForm['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede tener más de 100 caracteres';
    } else {
        // Verificar si ya existe una caja con ese nombre
        $stmtCheck = $conn->prepare("SELECT id FROM cajas WHERE nombre = ?");
        $stmtCheck->bind_param("s", $datosForm['nombre']);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        
        if ($resultCheck->num_rows > 0) {
            $errores['nombre'] = 'Ya existe una caja con este nombre';
        }
    }
    
    // Validar descripción (opcional)
    $datosForm['descripcion'] = trim($_POST['descripcion'] ?? '');
    if (strlen($datosForm['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede tener más de 255 caracteres';
    }
    
    // Validar estado
    $datosForm['estado'] = $_POST['estado'] ?? 'activa';
    if (!in_array($datosForm['estado'], ['activa', 'inactiva'])) {
        $errores['estado'] = 'El estado seleccionado no es válido';
    }
    
    // Validar saldo inicial
    $datosForm['saldo_inicial'] = floatval(str_replace(['$', ','], '', $_POST['saldo_inicial'] ?? 0));
    if ($datosForm['saldo_inicial'] < 0) {
        $errores['saldo_inicial'] = 'El saldo inicial no puede ser negativo';
    }
    
    // Si no hay errores, crear la caja
    if (empty($errores)) {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar la nueva caja
            $stmtInsert = $conn->prepare("INSERT INTO caja (nombre, responsable, saldoInicial, estaAbierta, fechaApertura) 
                                         VALUES (?, ?, ?, ?, NOW())");
            
            $estaAbierta = $datosForm['estado'] == 'activa' ? 1 : 0;
            $stmtInsert->bind_param("ssdi", $datosForm['nombre'], $_SESSION['user_nombre'], 
                              $datosForm['saldo_inicial'], $estaAbierta);
            
            $stmtInsert->execute();
            $nuevaCajaId = $conn->insert_id;
            
            // Si hay saldo inicial, registrar el movimiento
            if ($datosForm['saldo_inicial'] > 0) {
                $stmtMovimiento = $conn->prepare("INSERT INTO movimientocaja (caja_id, monto, concepto, fechaHora, finalizado) 
                                                 VALUES (?, ?, 'Saldo inicial', NOW(), 1)");
                
                $stmtMovimiento->bind_param("id", $nuevaCajaId, $datosForm['saldo_inicial']);
                $stmtMovimiento->execute();
            }
            
            // Registrar la acción en el log
            registrarLog('Creación de caja', 'El usuario ' . $_SESSION['user_nombre'] . ' ha creado la caja ' . $datosForm['nombre'], $_SESSION['user_id']);
            
            $conn->commit();
            $exito = true;
            
            // Limpiar el formulario después de un éxito
            $datosForm = [
                'nombre' => '',
                'descripcion' => '',
                'estado' => 'activa',
                'saldo_inicial' => 0
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            $errores['general'] = 'Error al crear la caja: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Caja</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nueva Caja</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if ($exito): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Caja creada correctamente.
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-sm btn-outline-success">Ver todas las cajas</a>
                            <a href="nuevo.php" class="btn btn-sm btn-success">Crear otra caja</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $errores['general']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información de la Caja</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>" 
                                       id="nombre" name="nombre" value="<?php echo htmlspecialchars($datosForm['nombre']); ?>" required>
                                <?php if (isset($errores['nombre'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errores['nombre']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control <?php echo isset($errores['descripcion']) ? 'is-invalid' : ''; ?>" 
                                          id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($datosForm['descripcion']); ?></textarea>
                                <?php if (isset($errores['descripcion'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errores['descripcion']; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">Máximo 255 caracteres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errores['estado']) ? 'is-invalid' : ''; ?>" 
                                        id="estado" name="estado" required>
                                    <option value="activa" <?php echo $datosForm['estado'] == 'activa' ? 'selected' : ''; ?>>Activa</option>
                                    <option value="inactiva" <?php echo $datosForm['estado'] == 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                                </select>
                                <?php if (isset($errores['estado'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errores['estado']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="saldo_inicial" class="form-label">Saldo Inicial</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control <?php echo isset($errores['saldo_inicial']) ? 'is-invalid' : ''; ?>" 
                                           id="saldo_inicial" name="saldo_inicial" value="<?php echo number_format($datosForm['saldo_inicial'], 2, '.', ''); ?>">
                                </div>
                                <?php if (isset($errores['saldo_inicial'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errores['saldo_inicial']; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">Si la caja inicia con un saldo, especifíquelo aquí.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i>Crear Caja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
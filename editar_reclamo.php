<?php
session_start();
require_once('../includes/config.php');
include('../includes/funciones.php');

// Verificar autenticación y permisos
if (!isset($_SESSION['admin']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Inicializar variables
$error = '';
$exito = '';
$reclamo = [];

try {
    // Obtener ID del reclamo
    $id_reclamo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id_reclamo === 0) {
        throw new Exception("ID de reclamo inválido");
    }

    // Obtener datos del reclamo
    $stmt = $conn->prepare("SELECT r.*, c.nombre as cliente_nombre, c.email, c.telefono 
                          FROM reclamos r
                          JOIN clientes c ON r.cliente_id = c.id
                          WHERE r.id = ?");
    $stmt->execute([$id_reclamo]);
    $reclamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reclamo) {
        throw new Exception("Reclamo no encontrado");
    }

    // Procesar actualización
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nuevo_estado = $_POST['estado'] ?? '';
        $comentario_admin = htmlspecialchars($_POST['comentario_admin'] ?? '');

        // Validar estado
        $estados_permitidos = ['Pendiente', 'En proceso', 'Resuelto'];
        if (!in_array($nuevo_estado, $estados_permitidos)) {
            throw new Exception("Estado seleccionado no válido");
        }

        // Actualizar en base de datos
        $stmt_update = $conn->prepare("UPDATE reclamos 
                                      SET estado = ?, comentario_admin = ?
                                      WHERE id = ?");
        $stmt_update->execute([$nuevo_estado, $comentario_admin, $id_reclamo]);

        $_SESSION['exito'] = "Estado actualizado correctamente";
        header("Location: editar_reclamo.php?id=$id_reclamo");
        exit();
    }

} catch(PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reclamo</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .editar-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .header-editar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .seccion-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-box {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .descripcion-container {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .descripcion-text {
            white-space: pre-wrap;
            line-height: 1.6;
            padding: 1rem;
            background: white;
            border-radius: 5px;
            border: 1px solid #eee;
            max-height: 300px;
            overflow-y: auto;
        }

        .form-editar {
            margin-top: 2rem;
        }

        .campo-formulario {
            margin-bottom: 1.5rem;
        }

        .estado-select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 150px;
        }

        .btn-actualizar {
            background: #27ae60;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        @media (max-width: 768px) {
            .seccion-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="editar-container">
        <?php if(isset($_SESSION['exito'])): ?>
            <div class="alerta exito"><?= $_SESSION['exito'] ?></div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alerta error"><?= $error ?></div>
        <?php else: ?>
            <div class="header-editar">
                <h1>Reclamo #<?= htmlspecialchars($reclamo['ticket_id']) ?></h1>
                <a href="dashboard.php" class="btn-volver">← Volver al Panel</a>
            </div>

            <div class="seccion-info">
                <div class="info-box">
                    <h3>Información del Cliente</h3>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($reclamo['cliente_nombre']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($reclamo['email']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($reclamo['telefono'] ?? 'No registrado') ?></p>
                </div>

                <div class="info-box">
                    <h3>Detalles del Reclamo</h3>
                    <p><strong>Fecha Incidente:</strong> <?= date('d/m/Y', strtotime($reclamo['fecha_incidente'])) ?></p>
                    <p><strong>Registrado el:</strong> <?= date('d/m/Y H:i', strtotime($reclamo['fecha_creacion'])) ?></p>
                    <p><strong>Estado actual:</strong> <?= formatearEstado($reclamo['estado']) ?></p>
                </div>
            </div>

            <div class="descripcion-container">
                <h3>Descripción del Incidente</h3>
                <div class="descripcion-text">
                    <?= nl2br(htmlspecialchars($reclamo['descripcion'])) ?>
                </div>
            </div>

            <form method="POST" class="form-editar">
                <div class="campo-formulario">
                    <label>Actualizar Estado</label>
                    <select name="estado" class="estado-select">
                        <option value="Pendiente" <?= $reclamo['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="En proceso" <?= $reclamo['estado'] === 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                        <option value="Resuelto" <?= $reclamo['estado'] === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                    </select>
                </div>

                <div class="campo-formulario">
                    <label>Comentarios del Administrador</label>
                    <textarea name="comentario_admin" placeholder="Ingrese observaciones internas..."><?= htmlspecialchars($reclamo['comentario_admin'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-actualizar">Guardar Cambios</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
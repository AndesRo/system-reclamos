<?php
require_once '../includes/config.php';
require_once '../includes/notificaciones.php';

// Solo permitir ejecución desde línea de comandos o con clave de seguridad
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'TU_CLAVE_SECRETA')) {
    die('Acceso no autorizado');
}

// Registrar hora de inicio
$inicio = date('Y-m-d H:i:s');
echo "Iniciando procesamiento de notificaciones: $inicio\n";

// Procesar notificaciones
$notificaciones = new Notificaciones($conn);
$procesadas = $notificaciones->procesarNotificacionesPendientes();

// Registrar hora de finalización
$fin = date('Y-m-d H:i:s');
echo "Procesamiento completado: $fin\n";
echo "Notificaciones procesadas: $procesadas\n";

// Guardar log en archivo
$log = "[$inicio] Procesadas: $procesadas\n";
file_put_contents('../logs/notificaciones.log', $log, FILE_APPEND);
?>
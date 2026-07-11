<?php

require_once __DIR__ . '/security.php';

const LM_CONTACT_EMAIL_TO = 'servicioalcliente@libreriamarquense.com';
const LM_CONTACT_EMAIL_FROM = 'no-reply@libreriamarquense.com';
const LM_CONTACT_EMAIL_FROM_NAME = 'Libreria Marquense';

lm_security_headers();
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Metodo no permitido.';
    exit;
}

$errors = array();

if (!lm_validate_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'contact_form')) {
    http_response_code(403);
    echo 'La sesion del formulario vencio. Recargue la pagina e intente nuevamente.';
    exit;
}

if (!empty($_POST['website'])) {
    echo 'success';
    exit;
}

if (!lm_rate_limit('contact:' . lm_client_ip(), 3, 60)) {
    http_response_code(429);
    echo 'Ha realizado varios intentos seguidos. Espere un momento e intente nuevamente.';
    exit;
}

$name = '';
$email = '';
$phoneNumber = '';
$subject = '';
$message = '';

$error = lm_validate_name(isset($_POST['name']) ? $_POST['name'] : '', $name);
if ($error !== '') {
    $errors[] = $error;
}

$error = lm_validate_email(isset($_POST['email']) ? $_POST['email'] : '', $email, true);
if ($error !== '') {
    $errors[] = $error;
}

$error = lm_validate_phone(isset($_POST['phone_number']) ? $_POST['phone_number'] : '', $phoneNumber, 8, 8);
if ($error !== '') {
    $errors[] = $error;
}

$error = lm_validate_text_field(isset($_POST['msg_subject']) ? $_POST['msg_subject'] : '', 'El asunto', 3, 120, $subject);
if ($error !== '') {
    $errors[] = $error;
}

$error = lm_validate_text_field(isset($_POST['message']) ? $_POST['message'] : '', 'El mensaje', 5, 1000, $message);
if ($error !== '') {
    $errors[] = $error;
}

if ($errors) {
    http_response_code(422);
    echo implode(' ', $errors);
    exit;
}

$emailTo = LM_CONTACT_EMAIL_TO;
$mailSubject = 'Nuevo mensaje para Libreria Marquense';

$body = "Nuevo mensaje recibido desde el formulario de contacto\n\n";
$body .= "===========================================\n";
$body .= "Nombre: " . $name . "\n";
$body .= "Email: " . $email . "\n";
$body .= "Telefono: " . $phoneNumber . "\n";
$body .= "Asunto: " . $subject . "\n";
$body .= "IP: " . lm_client_ip() . "\n";
$body .= "===========================================\n\n";
$body .= "Mensaje:\n" . $message . "\n\n";
$body .= "===========================================\n";
$body .= "Este mensaje fue enviado desde el formulario de contacto del sitio web.";

$logDir = dirname(__DIR__, 2) . '/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    $backupData = "==========================================\n";
    $backupData .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $backupData .= $body . "\n";
    $backupData .= "==========================================\n\n";
    file_put_contents($logDir . '/contactos_backup.log', $backupData, FILE_APPEND | LOCK_EX);
}

if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
    error_log('Correo de destino del formulario de contacto no valido.');
    http_response_code(500);
    echo 'No se pudo enviar el mensaje. El correo de destino no esta configurado correctamente.';
    exit;
}

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . LM_CONTACT_EMAIL_FROM_NAME . ' <' . LM_CONTACT_EMAIL_FROM . '>',
    'Reply-To: ' . $email,
);

$envelopeSender = preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', LM_CONTACT_EMAIL_FROM)
    ? LM_CONTACT_EMAIL_FROM
    : '';
$mailParameters = $envelopeSender !== '' && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN'
    ? '-f' . $envelopeSender
    : '';

$success = $mailParameters !== ''
    ? mail($emailTo, $mailSubject, $body, implode("\r\n", $headers), $mailParameters)
    : mail($emailTo, $mailSubject, $body, implode("\r\n", $headers));

if (!$success) {
    error_log('No se pudo enviar el correo del formulario de contacto a ' . $emailTo . '.');
    http_response_code(500);
    echo 'No se pudo enviar el mensaje en este momento. Por favor intenta nuevamente o escribenos a ' . $emailTo . '.';
    exit;
}

echo 'success';

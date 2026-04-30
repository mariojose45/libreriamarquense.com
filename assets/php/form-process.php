<?php

$errorMSG = "";

// Función para sanitizar datos
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// NAME
if (empty($_POST["name"])) {
    $errorMSG = "El nombre es requerido ";
} else {
    $name = sanitizeInput($_POST["name"]);
}

// EMAIL
if (empty($_POST["email"])) {
    $errorMSG .= "El email es requerido ";
} else {
    $email = sanitizeInput($_POST["email"]);
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMSG .= "El formato del email no es válido ";
    }
}

// MSG SUBJECT
if (empty($_POST["msg_subject"])) {
    $errorMSG .= "El asunto es requerido ";
} else {
    $msg_subject = sanitizeInput($_POST["msg_subject"]);
}

// Phone Number
if (empty($_POST["phone_number"])) {
    $errorMSG .= "El teléfono es requerido ";
} else {
    $phone_number = sanitizeInput($_POST["phone_number"]);
}

// MESSAGE
if (empty($_POST["message"])) {
    $errorMSG .= "El mensaje es requerido ";
} else {
    $message = sanitizeInput($_POST["message"]);
}


$EmailTo = "servicioalcliente@gmail.com";

$Subject = "Nuevo Mensaje para Librería Marquense";

// prepare email body text
$Body = "";
$Body .= "Nuevo mensaje recibido desde el formulario de contacto\n\n";
$Body .= "===========================================\n";
$Body .= "Nombre: ";
$Body .= $name;
$Body .= "\n";
$Body .= "Email: ";
$Body .= $email;
$Body .= "\n";
$Body .= "Teléfono: ";
$Body .= $phone_number;
$Body .= "\n";
$Body .= "Asunto: ";
$Body .= $msg_subject;
$Body .= "\n";
$Body .= "===========================================\n\n";
$Body .= "Mensaje:\n";
$Body .= $message;
$Body .= "\n\n";
$Body .= "===========================================\n";
$Body .= "Este mensaje fue enviado desde el formulario de contacto del sitio web.";

// Si hay errores de validación, retornarlos
if ($errorMSG != "") {
    echo $errorMSG;
    exit;
}

// Guardar los datos en un archivo de respaldo (útil para desarrollo)
$backupFile = __DIR__ . '/contactos_backup.txt';
$backupData = "==========================================\n";
$backupData .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
$backupData .= $Body . "\n";
$backupData .= "==========================================\n\n";
file_put_contents($backupFile, $backupData, FILE_APPEND);

// Configurar headers del email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
$headers .= "From: " . $email . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";

// Intentar enviar email solo si se configura una cuenta de destino.
// El formulario visible actualmente abre WhatsApp con el mensaje preparado.
$success = true;
if (!empty($EmailTo)) {
    @$success = mail($EmailTo, $Subject, $Body, $headers);
}

// En desarrollo local (XAMPP), mail() puede fallar pero los datos se guardaron
// Consideramos éxito si no hay errores de validación
// Nota: En producción necesitarás configurar SMTP o usar un servicio de correo
if ($errorMSG == "") {
    // En desarrollo, siempre retornamos éxito ya que guardamos el backup
    // En producción, descomenta la siguiente línea y comenta la otra
    // if ($success) {
    echo "success";
    // } else {
    //     echo "Ocurrió un error al enviar el mensaje. Los datos fueron guardados. Por favor contacta al administrador.";
    // }
} else {
    echo $errorMSG;
}

?>

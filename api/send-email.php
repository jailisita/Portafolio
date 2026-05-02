<?php
header('Content-Type: application/json');

// Cargar variables de entorno
$envFile = __DIR__ . '/../.env';
$apiKey = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'BREVO_API_KEY=') === 0) {
            $apiKey = trim(substr($line, 15));
            break;
        }
    }
}

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'API key no configurada']);
    exit;
}

// Obtener datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

// Validar datos
if (empty($nombre) || empty($email) || empty($mensaje)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido']);
    exit;
}

// Destinatario
$destino = 'gomezjaili20@gmail.com';

// Preparar email HTML
$subject = "Nuevo mensaje de contacto - $nombre";
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f472b6, #c084fc); color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #666; }
        .value { margin-top: 5px; padding: 10px; background: white; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2 style='margin:0;'>📬 Nuevo mensaje de contacto</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>👤 Nombre:</div>
                <div class='value'>" . htmlspecialchars($nombre) . "</div>
            </div>
            <div class='field'>
                <div class='label'>📧 Correo:</div>
                <div class='value'>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='field'>
                <div class='label'>💬 Mensaje:</div>
                <div class='value'>" . nl2br(htmlspecialchars($mensaje)) . "</div>
            </div>
        </div>
    </div>
</body>
</html>";

$textContent = "Nuevo mensaje de contacto\n\nNombre: $nombre\nCorreo: $email\nMensaje:\n$mensaje";

// Enviar con API de Brevo
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.brevo.com/v3/smtp/email",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'sender' => ['name' => 'Portafolio Jailiss', 'email' => 'noreply@jailiss.dev'],
        'to' => [['email' => $destino, 'name' => 'Jailiss Gomez']],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
        'textContent' => $textContent
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($httpCode === 201 || $httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
} else {
    $errorMsg = $error ?: 'Error al enviar el correo';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
}

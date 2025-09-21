<?php

// Librerías necesarias (ajusta según tu estructura real)
require_once('PHPExcel/PHPExcel.php');
require('phpmailer/class.phpmailer.php');

#  Configuración SFTP (variables genéricas, no reales)
$host = "SERVIDOR_SFTP";
$port = 22;
$usuario = "USUARIO_SFTP";
$contrasena = "CONTRASENA_SFTP";
$directorioRemoto = "/ruta/remota/archivos/";
$fechaActual = date('Ymd');
$nombreArchivo = "ARCHIVO_VALIDACION_{$fechaActual}.txt";
$rutaRemota = $directorioRemoto . $nombreArchivo;
$rutaLocal = __DIR__ . '/archivos/' . $nombreArchivo;
$resultados = "";

# Limpiar Log de errores
file_put_contents(__DIR__ . '/archivos/errores_validacion.log', '');

$conexion = ssh2_connect($host, $port);
if (!$conexion || !ssh2_auth_password($conexion, $usuario, $contrasena)) {
    $resultados .= "❌ Error: No se pudo conectar o autenticar con el servidor SFTP.\n";
    enviarCorreo($resultados, $fechaActual);
    exit;
}

$sftp = ssh2_sftp($conexion);
$archivoRemotoStream = "ssh2.sftp://$sftp$rutaRemota";

if (!file_exists($archivoRemotoStream)) {
    $resultados .= "❌ Error: El archivo '$nombreArchivo' no existe en el servidor SFTP.\n";
    enviarCorreo($resultados, $fechaActual);
    exit;
}

$contenidoRemoto = file_get_contents($archivoRemotoStream);
if ($contenidoRemoto === false || file_put_contents($rutaLocal, $contenidoRemoto) === false) {
    $resultados .= "❌ Error: No se pudo descargar o guardar el archivo localmente.\n";
    enviarCorreo($resultados, $fechaActual);
    exit;
}

$resultados .= "✅ Archivo '$nombreArchivo' descargado exitosamente.\n";

if (strpos($nombreArchivo, $fechaActual) === false) {
    $resultados .= "❌ Error: El nombre del archivo no contiene la fecha actual.\n";
    enviarCorreo($resultados, $fechaActual);
    exit;
} else {
    $resultados .= "✅ El nombre del archivo contiene la fecha actual.\n";
}

# Variables a utilizar
$lineas = file($rutaLocal);
$todasLasFechasValidas = true;
$prefijosInvalidos = 0;
$rutONumeroOperacionVacios = false;
$fonosInvalidos = 0;
$mailsInvalidos = 0;
$contactoFonosInvalidos = 0;
$contactoMailsInvalidos = 0;
$mailsRebote = 0;
$rebNoExisteDetectado = 0;
$codigosConPrefijo1 = [10, 27, 28, 29];
$codigosConPrefijo2 = ["09", "66", "76"];
$hayMailsEnElArchivo = false;
$tablaResumenHTML = "";

foreach ($lineas as $index => $linea) {
    if ($index === 0) continue;
    $linea = trim($linea);
    $codigo = substr($linea, 0, 2);
    $codigo2 = substr($linea, 2, 1);
    $rut = substr($linea, 3, 10);
    $numero_operacion = substr($linea, 13, 17);
    $cod_gestion = substr($linea, 30, 2);
    $fecha = substr($linea, 32, 8);
    $tipo_gestion = substr($linea, 54, 22);
    $contacto = trim(substr($linea, 76, 51));
    $prefijo = substr($linea, 142, 1);

    if (trim($rut) === '' || trim($numero_operacion) === '') {
        $rutONumeroOperacionVacios = true;
    }

    if (!preg_match('/^\d{8}$/', $fecha) || $fecha !== $fechaActual) {
        $todasLasFechasValidas = false;
    }

    # Validar Mails y Fonos
    $esMail = strpos($contacto, '@') !== false;
    $esFono = !$esMail && preg_match('/^\d{7,9}$/', $contacto);

    if ($esMail) {
        $hayMailsEnElArchivo = true;
    }

    if (($esMail || $esFono) && strtoupper(trim($tipo_gestion)) === 'REB NO EXISTE') {
        $rebNoExisteDetectado++;
    }

    # Validaciones específicas para fonos
    if ($esFono && in_array((int)$cod_gestion, $codigosConPrefijo1)) {
        if ($prefijo !== '1') {
            $prefijosInvalidos++;
        }
    }

    # Validar contacto vacío según tipo de gestión
    if (trim($contacto) === '') {
        if (in_array((int)$cod_gestion, $codigosConPrefijo1)) {
            $contactoFonosInvalidos++;
        } elseif (in_array((int)$cod_gestion, $codigosConPrefijo2)) {
            $contactoMailsInvalidos++;
        }
    }

    # Validaciones específicas para mails
    if ($esMail && in_array((int)$cod_gestion, $codigosConPrefijo2)) {
        if (trim($contacto) === '') {
            $mailsInvalidos++;
        } elseif (!filter_var($contacto, FILTER_VALIDATE_EMAIL)) {
            $mailsInvalidos++;
        }
    }

    # Validaciones adicionales para mails
    if ($esMail && $hayMailsEnElArchivo) {
        $tipoPermitido = ['ENTREGADO', 'REB BLOQUEO', 'LEIDO'];

        if (!in_array((int)$cod_gestion, $codigosConPrefijo2)) {
            $mailsInvalidos++;
        }

        if (!in_array(strtoupper(trim($tipo_gestion)), $tipoPermitido)) {
            $mailsRebote++;
        }
    }
}

# Mensajes de respuesta
$totalLineas = count($lineas);
$resultados .= "📊 Total líneas con encabezado: $totalLineas\n";
$resultados .= "📊 Total líneas sin encabezado: " . ($totalLineas - 1) . "\n";
$resultados .= $todasLasFechasValidas ? "✅ Todas las fechas son válidas.\n" : "❌ Hay fechas inválidas.\n";

# Eliminar archivo temporal
if (file_exists($rutaLocal)) {
    unlink($rutaLocal);
    $resultados .= "🗑️ Archivo $nombreArchivo eliminado al finalizar el proceso.\n";
}

enviarCorreo($resultados, $fechaActual);

function enviarCorreo($mensajeCuerpo, $fechaActual, $tablaResumenHTML = '') {
    $mail = new PHPMailer();
    try {
        $mail->CharSet = 'UTF-8'; 
        $mail->Encoding = 'base64'; 
        $mail->IsHTML(true);
        $mail->Host = 'SERVIDOR_SMTP';
        $mail->From     = 'correo@ejemplo.com';
        $mail->FromName = 'Sistema de Validación';
        $mail->AddAddress('destinatario@ejemplo.com', 'Usuario Destino');
        $mail->Subject = "Informe Validación ARCHIVO_VALIDACION_$fechaActual";

        $mensajeConvertido = htmlspecialchars($mensajeCuerpo);

        $mail->Body = "
            <p>Estimado/a,</p>
            <p>Se valida el archivo procesado:</p>
            <pre>$mensajeConvertido</pre>
            $tablaResumenHTML
        ";

        $mail->send();
        echo "📧 Correo enviado correctamente.\n";
    } catch (Exception $e) {
        echo "❌ Error al enviar correo: {$mail->ErrorInfo}\n";
    }
}

?>

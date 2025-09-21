Validador de Archivos en PHP

Este proyecto es un script de validación automática desarrollado en PHP para procesar archivos descargados desde un servidor SFTP. El objetivo es garantizar la integridad y consistencia de los datos antes de ser cargados en sistemas internos.

Características principales:

Conexión segura a servidor SFTP para descarga de archivos.

Validaciones sobre el archivo de texto: fechas correctas, campos obligatorios (RUT, número de operación), prefijos de fonos según reglas de negocio, correos válidos y detección de rebotes, tipos de gestión permitidos.

Resumen de validaciones con conteos y estado final.

Envío automático de informe por correo electrónico usando PHPMailer.

Eliminación automática de archivos temporales una vez procesados.

Tecnologías utilizadas:

PHP 5.4+(se puede adaptar a cualquier version de PHP)

PHPMailer (para el envío de correos)

SSH2 (para la conexión y descarga de archivos SFTP)

PHPExcel (opcional, para exportación a Excel en futuras versiones)

Estructura del proyecto

archivos/ → Carpeta de archivos descargados y logs

archivos/errores_validacion.log → Log de errores de validación

PHPExcel/ → Librería PHPExcel

phpmailer/ → Librería PHPMailer

validador.php → Script principal de validación

Configuración

Clonar el repositorio:

git clone https://github.com/tuusuario/validador-archivos.git
cd validador-archivos


Configurar accesos en validador.php:

$host = "SERVIDOR_SFTP";
$usuario = "USUARIO_SFTP";
$contrasena = "CONTRASENA_SFTP";
$directorioRemoto = "/ruta/remota/archivos/";

$mail->Host = "SERVIDOR_SMTP";
$mail->From = "correo@ejemplo.com";
$mail->AddAddress("destinatario@ejemplo.com");


Instalar dependencias (si se utiliza Composer):

composer require phpmailer/phpmailer

Uso

Ejecutar el script desde consola o navegador:

php validador.php


El script descargará el archivo desde SFTP, aplicará validaciones, generará un informe con los resultados y enviará un correo con el detalle.

Informe de validación

El informe incluye:

Cantidad total de registros procesados.

Registros con errores (y líneas específicas).

Resumen de validaciones exitosas y fallidas.

Estado final de la ejecución.

Ejemplo de salida:

Archivo 'ARCHIVO_VALIDACION_20250921.txt' descargado exitosamente.
Total líneas con encabezado: 1200
Total líneas sin encabezado: 1199
Todas las fechas son válidas.
Se encontraron 3 registros con RUT o número de operación vacío.
Informe enviado correctamente al correo configurado.

Notas:

Los valores de SFTP, correos y nombres de empresa fueron reemplazados por datos ficticios para uso público.

Este repositorio está pensado como ejemplo de buenas prácticas en validación de archivos en PHP.

Puede adaptarse fácilmente a reglas de negocio personalizadas.

Autor:

Desarrollado por Benja
Enfoque en automatización, validación de datos y desarrollo backend con PHP y SQL.

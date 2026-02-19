<?php
/**
 * @file upload.php
 * @brief API para subida de imágenes en formato Base64.
 *
 * @description
 * Endpoint de API que recibe imágenes codificadas en Base64 vía POST
 * y las guarda como archivos JPEG en el servidor. Utilizado principalmente
 * para captura de fotos desde dispositivos móviles o webcam.
 *
 * Este endpoint es utilizado por los formularios de tickets para subir
 * evidencias fotográficas de los problemas reportados.
 *
 * Flujo de operación:
 * 1. Recibe string Base64 en POST['photo_data']
 * 2. Elimina prefijo data:image/jpeg;base64, si existe
 * 3. Decodifica el Base64 a datos binarios
 * 4. Genera nombre único con timestamp
 * 5. Guarda archivo en carpeta uploads/
 * 6. Retorna mensaje de éxito/error
 *
 * @module API de Archivos
 * @access API (POST request)
 *
 * @dependencies
 * - PHP: file_put_contents, base64_decode, str_replace
 * - Filesystem: Carpeta uploads/ con permisos de escritura
 *
 * @inputs
 * - POST['photo_data']: Imagen codificada en Base64 (requerido)
 *   Formato esperado: data:image/jpeg;base64,/9j/4AAQSkZJRgABA...
 *
 * @outputs
 * - String: "Photo uploaded successfully!" (OK)
 * - String: "No photo data received." (POST sin photo_data)
 * - String: "Invalid request." (No es POST)
 *
 * @file_handling
 * - Formato guardado: JPEG
 * - Nombre archivo: photo_{timestamp}.jpg
 * - Ubicación: uploads/
 * - Ejemplo: uploads/photo_1705334567.jpg
 *
 * @security
 * - ADVERTENCIA: Sin autenticación (agregar recomendado)
 * - ADVERTENCIA: Sin validación de tamaño de archivo
 * - ADVERTENCIA: Sin validación de tipo de imagen real
 * - Solo acepta JPEG (considerar otros formatos)
 * - No sanitiza nombre de archivo (solo usa timestamp)
 *
 * @todo
 * - Agregar autenticación de sesión
 * - Validar tamaño máximo de imagen
 * - Validar que sea imagen válida (magic bytes)
 * - Soportar otros formatos (PNG, GIF)
 * - Retornar JSON en lugar de texto plano
 * - Retornar URL del archivo guardado
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['photo_data'])) {
        // Get the Base64 string from POST data
        $data = $_POST['photo_data'];

        // Remove the "data:image/jpeg;base64," part if present
        $data = str_replace('data:image/jpeg;base64,', '', $data);
        $data = base64_decode($data);

        // Save the image to the server
        $filePath = 'uploads/photo_' . time() . '.jpg';
        file_put_contents($filePath, $data);

        echo "Photo uploaded successfully!";
    } else {
        echo "No photo data received.";
    }
} else {
    echo "Invalid request.";
}
?>
<?php
/**
 * @file abc.php
 * @brief Endpoint simple para subida de archivos.
 *
 * @description
 * API básica para recibir y guardar archivos subidos mediante formularios
 * HTML multipart. Guarda los archivos en la carpeta uploads/ manteniendo
 * el nombre original del archivo.
 *
 * Flujo de operación:
 * 1. Verifica existencia de $_FILES['file']
 * 2. Extrae datos del archivo (nombre, ruta temporal, error)
 * 3. Si no hay error, mueve archivo a uploads/
 * 4. Retorna mensaje de éxito o error
 *
 * @module Módulo de Archivos
 * @access API (POST request con multipart/form-data)
 *
 * @dependencies
 * - PHP: $_FILES, move_uploaded_file, basename
 * - Filesystem: Carpeta uploads/ con permisos de escritura
 *
 * @inputs
 * - FILES['file']: Archivo subido desde formulario HTML
 *
 * @outputs
 * - String: "File uploaded successfully!" (OK)
 * - String: "Failed to upload file." (Error al mover)
 * - String: "Error with the file upload." (Error en subida)
 * - String: "No file uploaded." (Sin archivo)
 *
 * @file_handling
 * - Ruta destino: uploads/{nombre_original}
 * - Usa basename() para prevenir directory traversal
 * - Mantéiene nombre original del archivo
 *
 * @security
 * - basename() previene ataques de path traversal
 * - ADVERTENCIA: Sin validación de tipo de archivo
 * - ADVERTENCIA: Sin límite de tamaño (usar php.ini)
 * - ADVERTENCIA: Sin autenticación
 * - ADVERTENCIA: Nombres duplicados sobrescriben archivos
 *
 * @todo
 * - Agregar validación de tipos permitidos
 * - Generar nombres únicos (timestamp/uuid)
 * - Agregar autenticación
 * - Retornar JSON con ruta del archivo
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_error = $file['error'];
    
    if ($file_error === 0) {
        $upload_dir = 'uploads/';
        $upload_file = $upload_dir . basename($file_name);
        
        if (move_uploaded_file($file_tmp_name, $upload_file)) {
            echo 'File uploaded successfully!';
        } else {
            echo 'Failed to upload file.';
        }
    } else {
        echo 'Error with the file upload.';
    }
} else {
    echo 'No file uploaded.';
}
?>
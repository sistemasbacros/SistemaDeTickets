<?php
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
<?php
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
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageData = $_POST['image'];
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $decodedData = base64_decode($imageData);

    $fileName = 'drawing_' . time() . '.png';
    file_put_contents('uploads/' . $fileName, $decodedData);

    echo json_encode(['status' => 'success', 'file' => $fileName]);
}
?>
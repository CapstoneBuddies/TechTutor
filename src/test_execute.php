<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/Game/php-game-project/src/execute.php"); // Use HTTP
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "code=<?php echo 'Hello, World!'; ?>");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
echo $response;
?>
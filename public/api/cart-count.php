<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

$count = getCartCount();
echo json_encode(['count' => $count]);
?>

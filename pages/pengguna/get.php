<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
header('Content-Type: application/json');
$id = $_GET['id'] ?? 0;
$sql = "SELECT id, username, nama_lengkap, email, role, foto FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc() ?: ['error' => 'Data tidak ditemukan']);
?>




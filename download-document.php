<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit("Unauthorized");
}

$doc_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT file_name, file_path FROM pr_case_documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc || !file_exists($doc['file_path'])) {
    exit("File not found");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['file_name']) . '"');
header('Content-Length: ' . filesize($doc['file_path']));
readfile($doc['file_path']);
exit;
<?php
session_start();
require_once __DIR__ . '/../../db/functions.php';

$database = new Database();
$db = $database->connection();

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? null; // 'accept' | 'reject' | null (show page)

if ($token === '') {
	header('Location: /main.php');
	exit;
}

// Find guest by token
$stmt = $db->prepare('SELECT eg.*, em.title AS event_title FROM evento_guest eg JOIN evento_main em ON eg.id_evento_main = em.id_evento_main WHERE eg.token = :t');
$stmt->execute([':t' => $token]);
$guest = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guest) {
	http_response_code(404);
	echo 'Link inválido.';
	exit;
}

if ($action === 'accept' || $action === 'reject') {
	$status = $action === 'accept' ? 'accepted' : 'rejected';
	$upd = $db->prepare("UPDATE evento_guest SET status = :s, responded_at = NOW() WHERE id_evento_guest = :id");
	$upd->execute([':s' => $status, ':id' => $guest['id_evento_guest']]);
	include __DIR__ . '/views/rsvp_result.php';
	exit;
}

include __DIR__ . '/views/rsvp_view.php';


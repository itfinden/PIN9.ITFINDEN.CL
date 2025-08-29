<?php 
session_start();
// Manejar cambio de idioma ANTES de cualquier output
require_once 'lang/language_handler.php';

if(isset($_SESSION['user'])) {
	header('Location: content.php');
	die();
} else {
	require 'views/main.view.php';
}

?>
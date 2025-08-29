<?php 
session_start();
// Manejar cambio de idioma ANTES de cualquier output
require_once 'lang/language_handler.php';

$title= "Pin9";


if(isset($_SESSION['user'])) {
	header('Location: content.php');
	die();
} else {
	header('Location: main.php');
}

?>
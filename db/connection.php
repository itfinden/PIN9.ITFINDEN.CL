<?php
// Archivo de conexión a la base de datos
// Configuración de la base de datos

$hostname = 'localhost';
$username = 'itfinden_pin9';
$password = 'on5A5oR0zLG69eKS';
$database = 'itfinden_pin9';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

// Incluir funciones de permisos
require_once 'functions.php';
?> 
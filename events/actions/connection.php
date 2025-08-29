<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    die('No tienes permiso para acceder a los eventos.');
}
    class Database_X{
        private $hostname = 'localhost';
        private $username = 'itfinden_pin9';
        private $password = 'on5A5oR0zLG69eKS';
        private $database = 'itfinden_pin9';
        private $connection;

        public function connect(){
            $this->connection = null;
            try
            {
                $this->connection = new PDO('mysql:host=' . $this->hostname . ';dbname=' . $this->database . ';charset=utf8', 
                $this->username, $this->password);
            }
            catch(Exception $e)
            {
                die('Erro : '.$e->getMessage());
            }

            return $this->connection;
        }
    }
?>

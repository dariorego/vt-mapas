<?php
require 'config.php';
require 'Database.php';
$db = new Database();
$db->execute("ALTER TABLE motorista MODIFY carro_id INT NULL");
echo "Fixed DB";

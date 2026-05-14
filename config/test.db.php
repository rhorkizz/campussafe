<?php
$pdo = new PDO('mysql:host=localhost;dbname=campus_incident_system', 'root', '');
if ($pdo) echo "Connection works!";
else echo "Connection failed!";

<?php

// MySQLi connection (for existing code)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQLite connection using PDO (for new SQLite features)
try {
    $pdo_sqlite = new PDO("sqlite:includes/mydb.sqlite,shop_system.sqlite");
    $pdo_sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("SQLite connection failed: " . $e->getMessage());
}



// Database connection for MySQL
$servername = "localhost";  // Your database host, usually 'localhost'
$username = "root";         // Your database username
$password = "";             // Your database password (leave empty for XAMPP default)
$dbname = "shop_system";    // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

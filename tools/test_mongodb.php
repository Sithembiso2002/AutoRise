<?php
require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://admin:901018182+1@127.0.0.1:27017/?authSource=admin");

// Use a new database
$database = $client->shop_system;

// Use a collection (like a table)
$collection = $database->my_collection;

// Insert a sample document
$result = $collection->insertOne(['name' => 'John Doe', 'email' => 'john@example.com']);

echo "Inserted with Object ID: " . $result->getInsertedId();
?>
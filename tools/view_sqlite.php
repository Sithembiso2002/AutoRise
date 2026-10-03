<?php
// Connect to SQLite database
try {
    $db = new PDO("sqlite:database/shop_system.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all table names
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");

    echo "<h2>SQLite Tables and Data</h2>";

    foreach ($tables as $table) {
        $tableName = $table['name'];
        echo "<h3>Table: $tableName</h3>";

        $rows = $db->query("SELECT * FROM $tableName");

        // Fetch column names
        $columns = array_keys($rows->fetch(PDO::FETCH_ASSOC));
        $rows->execute(); // Reset cursor

        if (empty($columns)) {
            echo "No data.<br>";
            continue;
        }

        echo "<table border='1' cellpadding='5'><tr>";
        foreach ($columns as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($columns as $col) {
                echo "<td>" . htmlspecialchars($row[$col]) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
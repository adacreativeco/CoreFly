<?php
// Since we don't have a formal autoloader for a simple script, we'll try to use the established database class if possible, 
// or just open the sqlite directly for a quick check.
try {
    $db = new PDO("sqlite:e:/corfly/storage/corefly.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $users = $db->query("SELECT email, username, tenant_id FROM users LIMIT 10")->fetchAll();
    echo json_encode($users, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

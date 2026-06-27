<?php
$db_user = getenv("DB_USER") ?: "root";
$db_pass = getenv("DB_PASS") ?: "";
$db_name = getenv("DB_NAME") ?: "deadlinerx";

$cloud_sql_socket = getenv("CLOUD_SQL_SOCKET");

if ($cloud_sql_socket) {
    $conn = new mysqli(null, $db_user, $db_pass, $db_name, null, $cloud_sql_socket);
} else {
    $host = getenv("DB_HOST") ?: "localhost";
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
}

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Database Connection failed"
    ]));
}
?>
<?php
require 'auth.php';

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $appId = $input['app_id'];
    $action = $input['action'];
    $userId = $_SESSION['user_id'];

    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

    if ($action == 'add') {
        $check = pg_query_params($dbconn, "SELECT 1 FROM user_favorites WHERE user_id = $1 AND app_id = $2", array($userId, $appId));
        if (pg_num_rows($check) == 0) {
            pg_query_params($dbconn, "INSERT INTO user_favorites (user_id, app_id) VALUES ($1, $2)", array($userId, $appId));
        }
    } else {
        pg_query_params($dbconn, "DELETE FROM user_favorites WHERE user_id = $1 AND app_id = $2", array($userId, $appId));
    }

    echo json_encode(['status' => 'success']);
    pg_close($dbconn);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
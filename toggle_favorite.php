<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'];
    $appId = $input['app_id'];
    $action = $input['action']; // 'add' or 'remove'

    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    
    // หา User ID
    $res = pg_query_params($dbconn, "SELECT user_id FROM personnel WHERE google_email = $1", array($email));
    $row = pg_fetch_assoc($res);
    $userId = $row['user_id'];

    if ($action == 'add') {
        pg_query_params($dbconn, "INSERT INTO user_favorites (user_id, app_id) VALUES ($1, $2)", array($userId, $appId));
    } else {
        pg_query_params($dbconn, "DELETE FROM user_favorites WHERE user_id = $1 AND app_id = $2", array($userId, $appId));
    }

    echo json_encode(['status' => 'success']);
    pg_close($dbconn);

} catch (Exception $e) {
    echo json_encode(['status' => 'error']);
}
?>
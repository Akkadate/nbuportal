<?php
require 'auth.php';

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $avatar = $input['avatar'] ?? '';

    if (empty($email)) throw new Exception("Email is required");

    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$dbconn) throw new Exception("DB Connection Error");

    $query = "SELECT p.user_id, p.firstname_th, p.lastname_th, p.user_role, d.dept_name_th 
              FROM personnel p 
              LEFT JOIN departments d ON p.department_id = d.dept_id 
              WHERE p.google_email = $1 AND p.status = 'active'";

    $result = pg_query_params($dbconn, $query, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $row['firstname_th'] . ' ' . $row['lastname_th'];
        $_SESSION['user_role'] = $row['user_role'];
        $_SESSION['user_dept'] = $row['dept_name_th'] ?? 'General';
        $_SESSION['user_avatar'] = $avatar;

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'fail', 'message' => 'User not found']);
    }
    pg_close($dbconn);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
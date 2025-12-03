<?php
require 'auth.php'; // เรียกใช้ Session

error_reporting(0);
ini_set('display_errors', 0);

$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    // รับ JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ตรวจสอบว่ามีข้อมูล email หรือไม่
    if (!isset($input['email']) || empty($input['email'])) {
        throw new Exception("Email is required");
    }
    
    $email = $input['email'];
    $avatar = $input['avatar'] ?? '';

    // เชื่อมต่อฐานข้อมูล
    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$dbconn) {
        throw new Exception("DB Connection Error: " . pg_last_error());
    }

    // Query เพื่อหา User (รวม department_id เพื่อนำไปเก็บใน Session)
    $query = "SELECT p.user_id, p.firstname_th, p.lastname_th, p.user_role, p.department_id, d.dept_name_th 
              FROM personnel p 
              LEFT JOIN departments d ON p.department_id = d.dept_id 
              WHERE p.google_email = $1 AND p.status = 'active'";

    $result = pg_query_params($dbconn, $query, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        
        // *** สร้าง SESSION ที่นี่ ***
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $row['firstname_th'] . ' ' . $row['lastname_th'];
        $_SESSION['user_role'] = $row['user_role'];
        $_SESSION['user_dept'] = $row['dept_name_th'] ?? 'General';
        $_SESSION['user_dept_id'] = $row['department_id']; // เก็บ department_id ด้วย
        $_SESSION['user_avatar'] = $avatar;

        echo json_encode(['status' => 'success']);
        pg_close($dbconn);
        exit;
    } else {
        echo json_encode(['status' => 'fail', 'message' => 'User not found']);
        pg_close($dbconn);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500); // ส่ง HTTP 500 กรณี Error จริงจัง
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
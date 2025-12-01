<?php
// ปิดการแสดง Error ของ PHP ออกทางหน้าจอ (เพื่อไม่ให้ JSON พัง)
error_reporting(0);
ini_set('display_errors', 0);

// ตั้งค่า Header ให้เป็น JSON เสมอ
header('Content-Type: application/json; charset=utf-8');

// ตั้งค่า Database (ตรวจสอบให้ถูกต้อง)
$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    // 1. รับข้อมูลจาก Frontend
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ถ้าไม่มีข้อมูลส่งมา (กรณีเปิดไฟล์ตรงๆ) ให้จบการทำงาน
    if (!$input) {
        throw new Exception("No input data received");
    }

    $email = $input['email'] ?? '';
    if (empty($email)) {
        throw new Exception("Email is required");
    }

    // 2. เชื่อมต่อ PostgreSQL
    $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    $dbconn = pg_connect($conn_string);

    if (!$dbconn) {
        throw new Exception("Database connection failed: " . pg_last_error());
    }

    // 3. Query ข้อมูล
    $query = "SELECT p.user_id, p.firstname_th, p.lastname_th, p.user_role, d.dept_name_th 
              FROM personnel p 
              LEFT JOIN departments d ON p.department_id = d.dept_id 
              WHERE p.google_email = $1 AND p.status = 'active'";

    $result = pg_query_params($dbconn, $query, array($email));

    if (!$result) {
        throw new Exception("Query failed: " . pg_last_error($dbconn));
    }

    if (pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        echo json_encode([
            'status' => 'success',
            'role' => $row['user_role'],
            'department' => $row['dept_name_th'] ?? 'General',
            'name_th' => $row['firstname_th'] . ' ' . $row['lastname_th']
        ]);
    } else {
        echo json_encode([
            'status' => 'fail', 
            'message' => 'User not found or inactive'
        ]);
    }

    pg_close($dbconn);

} catch (Exception $e) {
    // ส่ง Error กลับเป็น JSON เสมอ
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
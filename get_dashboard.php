<?php
// ปิด Error Report เพื่อไม่ให้ JSON พัง
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Config Database (ตรวจสอบ Password ให้ถูกต้อง)
$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';

    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$dbconn) throw new Exception("Database connection failed");
    
    // 1. หา User ID และ Role จาก Email
    $userQuery = "SELECT user_id, user_role FROM personnel WHERE google_email = $1";
    $userResult = pg_query_params($dbconn, $userQuery, array($email));
    
    if (pg_num_rows($userResult) == 0) throw new Exception("User not found");
    $userData = pg_fetch_assoc($userResult);
    $userId = $userData['user_id'];
    $userRole = $userData['user_role'];

    // 2. ดึง Favorites ID ของ User
    $favQuery = "SELECT app_id FROM user_favorites WHERE user_id = $1";
    $favResult = pg_query_params($dbconn, $favQuery, array($userId));
    $favorites = [];
    while ($row = pg_fetch_assoc($favResult)) {
        $favorites[] = (int)$row['app_id'];
    }

    // 3. ดึง Apps ทั้งหมด + ชื่อหมวดหมู่ (JOIN categories)
    // Logic สิทธิ์: ถ้าเป็น student เห็นแค่ all/student, ถ้าเป็น staff/admin เห็นหมด
    $roleFilter = ($userRole == 'student') ? "('all', 'student')" : "('all', 'staff', 'admin')";
    
    // SQL: Join ตาราง categories เพื่อเอาชื่อหมวดมาแสดง
    $appQuery = "SELECT a.*, c.category_name, c.display_order 
                 FROM applications a 
                 LEFT JOIN categories c ON a.category_id = c.category_id 
                 WHERE a.is_active = TRUE AND a.required_role IN $roleFilter 
                 ORDER BY c.display_order ASC, a.app_id ASC";
                 
    $appResult = pg_query($dbconn, $appQuery);
    if (!$appResult) throw new Exception("Error fetching apps");
    
    $apps = [];
    while ($row = pg_fetch_assoc($appResult)) {
        $row['app_id'] = (int)$row['app_id'];
        $row['category_id'] = (int)$row['category_id'];
        // ถ้าไม่มีหมวดหมู่ ให้ตั้งชื่อว่า "อื่นๆ"
        if (empty($row['category_name'])) $row['category_name'] = 'General / อื่นๆ';
        $apps[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'favorites' => $favorites,
        'apps' => $apps
    ]);

    pg_close($dbconn);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
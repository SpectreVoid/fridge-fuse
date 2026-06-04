<?php
// index.php - Universal Temporary Database API

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight CORS requests automatically
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_file = 'database.sqlite';

// Parse the incoming request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_uri, '/'));

// =========================================================
// CONTROL ENDPOINT: POST /api/setup (Wipe & Regenerate)
// =========================================================
if (isset($path_parts[1]) && $path_parts[1] === 'setup') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed. Use POST to reset the database."]);
        exit();
    }

    try {
        // Wipe the old database instantly by unlinking the file
        if (file_exists($db_file)) {
            unlink($db_file);
        }

        // Establish a connection to spin up a completely clean SQLite file
        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Define initial test tables (Users can add more dynamically via code/shell later)
        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL, email TEXT);");
        $db->exec("CREATE TABLE inventory (id INTEGER PRIMARY KEY AUTOINCREMENT, item_name TEXT NOT NULL, quantity INTEGER);");

        // Seed with baseline mock data
        $db->exec("INSERT INTO users (username, email) VALUES ('sandbox_dev', 'test@example.com');");
        $db->exec("INSERT INTO inventory (item_name, quantity) VALUES ('Core Node Alpha', 14), ('Backup Battery Pack', 3);");

        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Temporary database wiped and regenerated successfully."]);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Setup execution failed: " . $e->getMessage()]);
        exit();
    }
}

// =========================================================
// UNIVERSAL REST API ROUTING ( /api/{table} or /api/{table}/{id} )
// =========================================================
if (empty($path_parts[0]) || $path_parts[0] !== 'api' || empty($path_parts[1])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid format. Use /api/{table_name} or POST /api/setup"]);
    exit();
}

// Block access if the database file hasn't been instantiated yet
if (!file_exists($db_file)) {
    http_response_code(500);
    echo json_encode(["error" => "Database missing. Fire a POST request to /api/setup to initialize."]);
    exit();
}

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal database connection failure."]);
    exit();
}

// Sanitize the table name to prevent SQL Injection
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $path_parts[1]);
$id = isset($path_parts[2]) ? (int)$path_parts[2] : null;

// Dynamically check if the table requested by the client exists in the SQLite file
$table_check = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
$table_check->execute([':table' => $table]);
if (!$table_check->fetch()) {
    http_response_code(404);
    echo json_encode(["error" => "Table '$table' does not exist in the active database shell."]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Fetch a single record by ID
                $stmt = $db->prepare("SELECT * FROM $table WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$data) {
                    http_response_code(404);

<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'Please login to use chat!']);
    exit();
}

$message   = trim($_POST['message'] ?? '');
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$role      = $_SESSION['role'];

if (empty($message)) {
    echo json_encode(['reply' => 'Please type a message!']);
    exit();
}

// Gemini API Key - உன் key இங்க போடு!
$api_key = "YOUR_GEMINI_API_KEY_HERE";

// DB context
$context = "";

if ($role === 'customer') {
    $orders_stmt = mysqli_prepare($conn, "
        SELECT o.id, o.order_status, o.total_amount, o.created_at, s.shop_name
        FROM orders o
        JOIN shops s ON o.shop_id = s.id
        WHERE o.customer_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
    mysqli_stmt_execute($orders_stmt);
    $orders = mysqli_stmt_get_result($orders_stmt);

    $order_info = "";
    while ($order = mysqli_fetch_assoc($orders)) {
        $order_info .= "Order #{$order['id']} from {$order['shop_name']} - Status: {$order['order_status']} - LKR {$order['total_amount']} - Date: {$order['created_at']}\n";
    }
    $context = "Customer name: $user_name. Recent orders:\n$order_info";

} elseif ($role === 'shop_owner') {
    $shop_stmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE owner_id = ?");
    mysqli_stmt_bind_param($shop_stmt, "i", $user_id);
    mysqli_stmt_execute($shop_stmt);
    $shop = mysqli_fetch_assoc(mysqli_stmt_get_result($shop_stmt));

    if ($shop) {
        $pending = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as t FROM orders WHERE shop_id = {$shop['id']} AND order_status = 'pending'"))['t'];
        $total   = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as t FROM orders WHERE shop_id = {$shop['id']}"))['t'];
        $context = "Shop owner: $user_name. Shop: {$shop['shop_name']}. Total orders: $total. Pending: $pending.";
    }
}

// System prompt
$system_prompt = "You are FindyWear AI Assistant - a helpful assistant for a hyperlocal fashion e-commerce platform in Sri Lanka. FindyWear connects customers with nearby dress shops within 5km for 1-day delivery. You help customers track orders, find shops, and answer questions. You help shop owners manage orders and products. Always be friendly, helpful and concise. Reply in the same language the user writes (Tamil, English or Sinhala). Current user context: $context";

// Gemini API call
$url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $system_prompt . "\n\nUser: " . $message]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 300
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['reply' => 'Sorry, AI service unavailable. Please try again!']);
    exit();
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $reply = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['reply' => $reply]);
} else {
    // Debug
    echo json_encode(['reply' => 'AI error: ' . json_encode($result)]);
}
?>
<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    echo json_encode(['error' => 'Latitude required']);
    exit();
}

$lat = floatval($_GET['lat']);
$lng = floatval($_GET['lng']);
$radius = 5; // 5 km radius

// Haversine formula - 5km radius shop filter
$sql = "SELECT s.*, u.name as owner_name, u.phone as owner_phone,
        (6371 * acos(
        cos(radians(?)) * cos(radians(s.latitude)) * 
        cos(radians(s.longitude) - radians(?)) + 
        sin(radians(?)) * sin(radians(s.latitude))
        )) AS distance
        FROM shops s
        JOIN users u ON s.owner_id = u.id
        WHERE s.status = 'active'
        HAVING distance < ?
        ORDER BY distance ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "dddd", $lat, $lng, $lat, $radius);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$shops = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['distance'] = round($row['distance'], 1); // Round distance to 2 decimals
    $shops[] = $row;
}
echo json_encode(['shops' => $shops, 'count' => count($shops)]);

?>
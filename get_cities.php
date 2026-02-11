<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (isset($_GET['district_id']) && !empty($_GET['district_id'])) {
    $district_id = intval($_GET['district_id']);
    
    try {
        // First, get the district name from district_id
        $district_sql = "SELECT District_name FROM district_table WHERE id = ?";
        $district_stmt = $pdo->prepare($district_sql);
        $district_stmt->execute([$district_id]);
        $district = $district_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($district) {
            // Now get cities linked to this district
            // Note: Using City_link field which should contain the district_id
            $sql = "SELECT * FROM city_table WHERE City_link = ? ORDER BY City ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$district_id]);
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($cities);
        } else {
            echo json_encode([]);
        }
    } catch (Exception $e) {
        error_log("Error in get_cities.php: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
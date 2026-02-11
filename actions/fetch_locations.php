<?php
require_once '../config/config.php';
if(isset($_POST['district_name'])) {
    $stmt = $pdo->prepare("SELECT c.City FROM city_table c 
                           JOIN district_table d ON c.City_link = d.id 
                           WHERE d.District_name = ?");
    $stmt->execute([$_POST['district_name']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
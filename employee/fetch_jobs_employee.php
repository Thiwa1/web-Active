<?php
session_start();
require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// Get Seeker ID
$stmt_p = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
$stmt_p->execute([$user_id]);
$seeker_id = $stmt_p->fetchColumn();

// Fetch Jobs
$sql = "SELECT a.id, a.Job_role as Position, e.employer_name as Company, a.Job_category, 
               c.City, d.District_name, a.Opening_date, a.Closing_date, a.img_path, e.logo_path,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_ad_link = a.id AND ja.seeker_link = ?) as applied
        FROM advertising_table a
        LEFT JOIN employer_profile e ON a.link_to_employer_profile = e.id
        LEFT JOIN city_table c ON a.City = c.City
        LEFT JOIN district_table d ON a.District = d.District_name
        WHERE a.Approved = 1 AND a.Closing_date >= CURDATE()
        ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$seeker_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($jobs);

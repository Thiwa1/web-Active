<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Get Profile ID
    $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) die("Profile not found.");
    $profile_id = $profile['id'];

    // 2. Validate Input
    $headline = trim($_POST['headline']);
    $skills = trim($_POST['skills_tags']);
    $exp = (int)$_POST['experience_years'];
    $salary = (float)$_POST['expected_salary'];
    $desc = trim($_POST['description']);
    $duration = (int)$_POST['duration'];

    if (empty($headline) || empty($skills)) {
        die("Headline and Skills are required.");
    }

    // 3. Check for Update vs Insert
    if (!empty($_POST['offer_id'])) {
        // UPDATE
        $offer_id = (int)$_POST['offer_id'];
        
        $sql = "UPDATE talent_offers SET headline=?, skills_tags=?, experience_years=?, expected_salary=?, description=? 
                WHERE id=? AND seeker_link=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$headline, $skills, $exp, $salary, $desc, $offer_id, $profile_id]);
        
        header("Location: ../dashboard.php?page=promote_self&msg=Offer Updated Successfully!");
    } else {
        // INSERT
        $sql = "INSERT INTO talent_offers (seeker_link, headline, skills_tags, experience_years, expected_salary, description, expiry_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profile_id, $headline, $skills, $exp, $salary, $desc, $duration]);

        header("Location: ../dashboard.php?page=promote_self&msg=Offer Published Successfully!");
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

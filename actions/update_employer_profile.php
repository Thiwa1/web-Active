<?php
session_start();
require_once '../config/config.php';
require_once '../config/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Sanitize inputs
    $name = htmlspecialchars($_POST['employer_name'] ?? '');
    $addr1 = htmlspecialchars($_POST['employer_address_1'] ?? '');
    $addr2 = htmlspecialchars($_POST['employer_address_2'] ?? '');
    $addr3 = htmlspecialchars($_POST['employer_address_3'] ?? '');
    
    // Handle optional unique fields (Convert empty strings to NULL to prevent SQL 1062 errors)
    $mobile = !empty($_POST['employer_mobile_no']) ? htmlspecialchars($_POST['employer_mobile_no']) : null;
    $whatsapp = !empty($_POST['employer_whatsapp_no']) ? htmlspecialchars($_POST['employer_whatsapp_no']) : null;
    $landline = !empty($_POST['employer_landline']) ? htmlspecialchars($_POST['employer_landline']) : null; // Mapped to 'employer' column

    $about = htmlspecialchars($_POST['employer_about_company'] ?? '');

    try {
        // Handle Image Uploads
        $logo_path = null;
        $logo_content = null;

        if (!empty($_FILES['employer_logo']['tmp_name'])) {
            $raw_path = uploadImage($_FILES['employer_logo'], '../uploads/employers/');
            $logo_path = str_replace(['../', '../../'], '', $raw_path);
            $logo_content = file_get_contents($_FILES['employer_logo']['tmp_name']);
        }

        $br_path = null;
        if (!empty($_FILES['employer_BR']['tmp_name'])) {
            $raw_br = uploadImage($_FILES['employer_BR'], '../uploads/docs/', ['pdf','jpg','png']);
            $br_path = str_replace(['../', '../../'], '', $raw_br);
            // $br_content = file_get_contents(...) - BR usually file only now
        }

        // Check if profile exists
        $check = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
        $check->execute([$user_id]);
        $exists = $check->fetch();

        if ($exists) {
            // Update
            $sql = "UPDATE employer_profile SET 
                    employer_name=?, employer_address_1=?, employer_address_2=?, employer_address_3=?, 
                    employer_mobile_no=?, employer_whatsapp_no=?, employer_about_company=?, employer=? ";
            $params = [$name, $addr1, $addr2, $addr3, $mobile, $whatsapp, $about, $landline];
            
            if ($logo_path) {
                $sql .= ", logo_path=?, employer_logo=?";
                $params[] = $logo_path;
                $params[] = $logo_content; // BLOB update
            }
            if ($br_path) {
                $sql .= ", br_path=?";
                $params[] = $br_path;
            }
            
            $sql .= " WHERE link_to_user=?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            // Bind explicitly for BLOB if needed, but PDO executes handle basic strings fine.
            // For large BLOBs, bindParam is better, but here we assume reasonable size.
            // Or use bindParam loop. Let's stick to execute() unless issues arise.
            $stmt->execute($params);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO employer_profile (link_to_user, employer_name, employer_address_1, employer_address_2, employer_address_3, employer_mobile_no, employer_whatsapp_no, employer_about_company, employer, logo_path, br_path, employer_logo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$user_id, $name, $addr1, $addr2, $addr3, $mobile, $whatsapp, $about, $landline, $logo_path, $br_path, $logo_content]);
        }

        header("Location: ../employer/dashboard.php?success=Profile Updated");
    } catch (Exception $e) {
        header("Location: ../employer/profile.php?error=" . urlencode($e->getMessage()));
    }
}
<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = htmlspecialchars($_POST['full_name']);
    $initials = htmlspecialchars($_POST['initials']);

    try {
        // Uploads
        $img_path = null;
        if (!empty($_FILES['profile_img']['tmp_name'])) {
            $raw = uploadImage($_FILES['profile_img'], '../../uploads/seekers/');
            $img_path = str_replace(['../', '../../'], '', $raw);
        }

        $cv_path = null;
        if (!empty($_FILES['cv_file']['tmp_name'])) {
            $raw = uploadImage($_FILES['cv_file'], '../../uploads/seekers/', ['pdf','doc','docx']);
            $cv_path = str_replace(['../', '../../'], '', $raw);
        }

        $cl_path = null;
        if (!empty($_FILES['cl_file']['tmp_name'])) {
            $raw = uploadImage($_FILES['cl_file'], '../../uploads/seekers/', ['pdf','doc','docx']);
            $cl_path = str_replace(['../', '../../'], '', $raw);
        }

        $check = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
        $check->execute([$user_id]);
        
        if ($check->fetch()) {
            $sql = "UPDATE employee_profile_seeker SET employee_full_name=?, employee_name_with_initial=?";
            $params = [$full_name, $initials];
            
            if ($img_path) { $sql .= ", img_path=?"; $params[] = $img_path; }
            if ($cv_path) { $sql .= ", cv_path=?"; $params[] = $cv_path; }
            if ($cl_path) { $sql .= ", cl_path=?"; $params[] = $cl_path; }
            
            $sql .= " WHERE link_to_user=?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare("INSERT INTO employee_profile_seeker (link_to_user, employee_full_name, employee_name_with_initial, img_path, cv_path, cl_path) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $full_name, $initials, $img_path, $cv_path, $cl_path]);
        }

        header("Location: ../profile.php?msg=saved");
    } catch (Exception $e) {
        header("Location: ../profile.php?err=" . urlencode($e->getMessage()));
    }
}

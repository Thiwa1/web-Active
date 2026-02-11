<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $job_id  = $_POST['job_id'];
    
    // 1. Get employer ID to ensure they own this job
    $stmt = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();
    
    if (!$emp) {
        die("Unauthorized access.");
    }
    
    $emp_id      = $emp['id'];
    $opening     = $_POST['Opening_date'];
    $closing     = $_POST['Closing_date'];
    $industry    = $_POST['Industry'];
    $category    = $_POST['Job_category'];
    $job_type    = $_POST['job_type'] ?? 'Full Time'; // New field
    $role        = $_POST['Job_role'];
    $city        = $_POST['City'];
    $district    = $_POST['District'];
    $description = $_POST['job_description'];
    
    // Method toggles
    $apply_system = isset($_POST['Apply_by_system']) ? 1 : 0;
    $email_addr   = $_POST['Apply_by_email_address'];
    $apply_email  = !empty($email_addr) ? 1 : 0;
    $wa_no        = $_POST['apply_WhatsApp_No'];
    $apply_wa     = !empty($wa_no) ? 1 : 0;

    try {
        // 2. Handle Image Update logic
        // We only update the 'Img' column if a new file was actually uploaded
        if (!empty($_FILES['Img']['tmp_name'])) {
            $img = file_get_contents($_FILES['Img']['tmp_name']);
            $sql = "UPDATE advertising_table SET 
                    Opening_date = ?, Closing_date = ?, Industry = ?, Job_category = ?, job_type = ?,
                    Job_role = ?, City = ?, job_description = ?, 
                    District = ?, Apply_by_email = ?, Apply_by_system = ?, 
                    apply_WhatsApp = ?, Apply_by_email_address = ?, 
                    apply_WhatsApp_No = ?, Img = ?, Approved = 0 
                    WHERE id = ? AND link_to_employer_profile = ?";
            
            $stmt = $pdo->prepare($sql);
            $params = [
                $opening, $closing, $industry, $category, $job_type, $role, $city,
                $description, $district, $apply_email, $apply_system, 
                $apply_wa, $email_addr, $wa_no, $img, $job_id, $emp_id
            ];
        } else {
            // Update without changing the existing image
            $sql = "UPDATE advertising_table SET 
                    Opening_date = ?, Closing_date = ?, Industry = ?, Job_category = ?, job_type = ?,
                    Job_role = ?, City = ?, job_description = ?, 
                    District = ?, Apply_by_email = ?, Apply_by_system = ?, 
                    apply_WhatsApp = ?, Apply_by_email_address = ?, 
                    apply_WhatsApp_No = ?, Approved = 0 
                    WHERE id = ? AND link_to_employer_profile = ?";
            
            $stmt = $pdo->prepare($sql);
            $params = [
                $opening, $closing, $industry, $category, $job_type, $role, $city,
                $description, $district, $apply_email, $apply_system, 
                $apply_wa, $email_addr, $wa_no, $job_id, $emp_id
            ];
        }
        
        $stmt->execute($params);

        header("Location: ../employer/manage_jobs.php?msg=Vacancy updated and sent for re-approval");
        exit();

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

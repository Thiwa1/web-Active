<?php
session_start();
require_once '../config/config.php';
require_once '../config/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // 1. Get employer profile details
    $stmt = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();
    
    if (!$emp) {
        die("Error: Employer profile not found.");
    }

    $link_to_emp = $emp['id'];
    
    // 2. Prepare Vacancy Data (The First Ad)
    $opening     = $_POST['Opening_date'];
    $closing     = $_POST['Closing_date'];
    $industry    = $_POST['Industry'];
    $category    = $_POST['Job_category'];
    $job_type    = $_POST['job_type'] ?? 'Full Time'; // Default fallback
    $role        = $_POST['Job_role'];
    $city        = $_POST['City'];
    $district    = $_POST['District'];
    $description = $_POST['job_description'];
    
    // Application method toggles
    $apply_system = isset($_POST['Apply_by_system']) ? 1 : 0;
    $email_addr   = $_POST['Apply_by_email_address'];
    $apply_email  = !empty($email_addr) ? 1 : 0;
    $wa_no        = $_POST['apply_WhatsApp_No'];
    $apply_wa     = !empty($wa_no) ? 1 : 0;

    // Handle Job Banner Image
    $img_path = null;
    if (!empty($_FILES['job_banner']['tmp_name'])) {
        try {
            $raw_path = uploadImage($_FILES['job_banner'], '../uploads/jobs/');
            $img_path = str_replace(['../', '../../'], '', $raw_path);
        } catch (Exception $e) { die("Image Error: " . $e->getMessage()); }
    }

    // 3. Prepare Payment & Unit Data
    $total_received = (double)$_POST['calculated_total'];
    $unit_count     = (int)$_POST['unit_count']; 
    $payment_slip_path = null;
    
    // Check for Promo Mode
    $is_promo = isset($_POST['is_promo']) && $_POST['is_promo'] == '1';

    if ($total_received > 0 && !empty($_FILES['payment_slip']['tmp_name'])) {
        try {
            $raw_slip = uploadImage($_FILES['payment_slip'], '../uploads/docs/', ['jpg','jpeg','png','pdf']);
            $payment_slip_path = str_replace(['../', '../../'], '', $raw_slip);
        } catch (Exception $e) { die("Slip Error: " . $e->getMessage()); }
    }

    try {
        // Start Transaction
        $pdo->beginTransaction();

        // --- STEP A: Insert the Payment Record FIRST ---
        // This slip covers ALL units purchased in this transaction
        $payment_approval = ($total_received == 0 || $is_promo) ? 1 : 0;
        
        $sqlPay = "INSERT INTO payment_table 
                   (employer_link, Totaled_received, slip_path, payment_date, Approval) 
                   VALUES (?, ?, ?, CURDATE(), ?)";
        
        $stmtPay = $pdo->prepare($sqlPay);
        $stmtPay->execute([$link_to_emp, $total_received, $payment_slip_path, $payment_approval]);
        $new_payment_id = $pdo->lastInsertId();

        // --- STEP B: Insert the First Ad (The one with details) ---
        $sqlAd = "INSERT INTO advertising_table 
                  (link_to_employer_profile, Opening_date, Closing_date, Industry, Job_category, job_type, Job_role, img_path, City, job_description, District, Apply_by_email, Apply_by_system, apply_WhatsApp, Apply_by_email_address, apply_WhatsApp_No, Approved)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        
        $stmtAd = $pdo->prepare($sqlAd);
        $stmtAd->execute([
            $link_to_emp, $opening, $closing, $industry, $category, $job_type, $role,
            $img_path, $city, $description, $district, $apply_email, $apply_system, 
            $apply_wa, $email_addr, $wa_no
        ]);
        $first_ad_id = $pdo->lastInsertId();

        // Link First Ad to Payment
        $is_paid = ($total_received == 0 || $is_promo) ? 1 : 0;
        $sqlLink = "INSERT INTO paid_advertising (slip_link, add_link, paid) VALUES (?, ?, ?)";
        $stmtLink = $pdo->prepare($sqlLink);
        $stmtLink->execute([$new_payment_id, $first_ad_id, $is_paid]);

        // --- STEP C: Create Placeholder Slots for Extra Units ---
        if ($unit_count > 1) {
            // Prepare a generic "Draft" insert for the remaining units
            $sqlDraft = "INSERT INTO advertising_table 
                         (link_to_employer_profile, Job_role, job_description, Approved) 
                         VALUES (?, 'Purchased Ad Slot (Pending Details)', 'Please edit this advertisement to fill in your job details.', 0)";
            $stmtDraft = $pdo->prepare($sqlDraft);

            for ($i = 1; $i < $unit_count; $i++) {
                $stmtDraft->execute([$link_to_emp]);
                $extra_ad_id = $pdo->lastInsertId();

                // Link these extra slots to the SAME payment slip
                $stmtLink->execute([$new_payment_id, $extra_ad_id, $is_paid]);
            }
        }

        // Commit all changes
        $pdo->commit();

        $redirect_msg = ($unit_count > 1) 
            ? "Success! $unit_count ads submitted. 1 is ready, " . ($unit_count-1) . " credits added to your dashboard."
            : "Vacancy and Payment submitted for review.";

        header("Location: ../employer/dashboard.php?page=manage_jobs&msg=" . urlencode($redirect_msg));
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
}

<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/NotifySMS.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action']; 
    $reason = $_POST['reason'] ?? '';
    
    $sms = new NotifySMS($pdo);

    try {
        $pdo->beginTransaction();

        if ($action === 'approve') {
            // 1. Update Payment Table
            $stmt1 = $pdo->prepare("UPDATE payment_table SET Approval = 1, Reject_comment = NULL, Approval_date = CURDATE() WHERE id = ?");
            $stmt1->execute([$payment_id]);

            // 2. Mark Ads as Paid
            $stmt2 = $pdo->prepare("UPDATE paid_advertising SET paid = 1 WHERE slip_link = ?");
            $stmt2->execute([$payment_id]);

            // 3. Get Ad details for matching
            $sqlGetAds = "SELECT id, Job_role, City, District, Job_category, Opening_date 
                          FROM advertising_table 
                          WHERE id IN (SELECT add_link FROM paid_advertising WHERE slip_link = ?)";
            $stmtAds = $pdo->prepare($sqlGetAds);
            $stmtAds->execute([$payment_id]);
            $linkedAds = $stmtAds->fetchAll(PDO::FETCH_ASSOC);

            foreach ($linkedAds as $job) {
                // Activate Ad
                $pdo->prepare("UPDATE advertising_table SET Approved = 1 WHERE id = ?")->execute([$job['id']]);

                // 4. FIXED SMS LOGIC: Get Mobile/WhatsApp from user_table
                // We JOIN employer_profile_seeker -> user_table to get the actual contact numbers
                $sqlSubscribers = "SELECT ut.id as user_id, ut.mobile_number, ut.WhatsApp_number 
                                   FROM employee_alerted_setting eas
                                   JOIN employee_profile_seeker eps ON eas.link_to_employee_profile = eps.id
                                   JOIN user_table ut ON eps.link_to_user = ut.id
                                   WHERE eas.active = 1 
                                   AND (eas.district = ? OR eas.city = ? OR eas.job_category = ?)
                                   AND ut.user_active = 1";
                
                $stmtSub = $pdo->prepare($sqlSubscribers);
                $stmtSub->execute([
                    $job['District'], 
                    $job['City'], 
                    $job['Job_category']
                ]);
                $subscribers = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

                foreach ($subscribers as $sub) {
                    // Send to mobile number retrieved from user_table
                    $sentStatus = $sms->sendJobAlert($sub['mobile_number'], $job['Job_role'], $job['City']);
                    
                    if ($sentStatus) {
                        $logStmt = $pdo->prepare("INSERT INTO sms_logs (job_id, user_id, phone_number, status) VALUES (?, ?, ?, 'Sent')");
                        $logStmt->execute([$job['id'], $sub['user_id'], $sub['mobile_number']]);
                    }
                }
            }
            $msg = "Payment approved. Ads are LIVE and SMS alerts have been dispatched.";

        } else {
            // REJECT LOGIC
            $pdo->prepare("UPDATE payment_table SET Approval = 2, Reject_comment = ? WHERE id = ?")->execute([$reason, $payment_id]);
            $pdo->prepare("UPDATE advertising_table SET Approved = 0 WHERE id IN (SELECT add_link FROM paid_advertising WHERE slip_link = ?)")->execute([$payment_id]);
            $msg = "Payment rejected.";
        }

        $pdo->commit();
        header("Location: ../review_payments.php?status=success&msg=" . urlencode($msg));
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        die("Database Error: " . $e->getMessage());
    }
}
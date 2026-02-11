<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/mail_helper.php';
require_once '../../classes/NotifySMS.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 50;
if ($limit > 500) $limit = 500; // Safety cap

$sent_count = 0;

try {
    // 1. Identify Target Employees
    // Select Active settings where alert hasn't been sent recently
    // Joining with Profile and User to get contact info
    $sql = "
        SELECT s.id as setting_id, s.district, s.city, s.job_category, 
               p.link_to_user, u.mobile_number, u.user_email, u.full_name
        FROM employee_alerted_setting s
        JOIN employee_profile_seeker p ON s.link_to_employee_profile = p.id
        JOIN user_table u ON p.link_to_user = u.id
        WHERE s.active = 1 
        AND (s.last_alert_sent IS NULL OR s.last_alert_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))
        LIMIT $limit
    ";
    
    $targets = $pdo->query($sql)->fetchAll();

    if (empty($targets)) {
        header("Location: ../sms_panel.php?msg=No pending alerts found.");
        exit();
    }

    $smsLogSql = "INSERT INTO sms_logs (job_id, user_id, phone_number, status, sent_at) VALUES (?, ?, ?, ?, NOW())";
    $smsStmt = $pdo->prepare($smsLogSql);

    $smsService = new NotifySMS($pdo);

    $updateSql = "UPDATE employee_alerted_setting SET last_alert_sent = NOW(), Total_count = Total_count + 1 WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);

    foreach ($targets as $t) {
        // 2. Find a MATCHING Job for this user
        // Priority: Match City > District > Category
        // Must be Approved (1) and Active (Closing date future)
        $matchSql = "
            SELECT id, Job_role FROM advertising_table 
            WHERE Approved = 1 
            AND Closing_date >= CURDATE()
            AND (
                (City = ? AND Job_category = ?) OR 
                (District = ? AND Job_category = ?) OR
                (Job_category = ?)
            )
            ORDER BY id DESC LIMIT 1
        ";
        
        $matchStmt = $pdo->prepare($matchSql);
        $matchStmt->execute([
            $t['city'], $t['job_category'],
            $t['district'], $t['job_category'],
            $t['job_category']
        ]);
        
        $job = $matchStmt->fetch();

        if ($job) {
            // 3. Send SMS using NotifySMS Class
            $smsStatus = 'Skipped';

            if ($smsService->isConfigured()) {
                if ($smsService->sendJobAlert($t['mobile_number'], $job['Job_role'], $t['city'])) {
                    $smsStatus = 'Sent';
                } else {
                    $smsStatus = 'Failed';
                }
            }
            
            // 4. Send Email (Fallback/Parallel)
            if (!empty($t['user_email'])) {
                $subject = "New Job Alert: " . $job['Job_role'];
                $msg = "<p>Hi " . htmlspecialchars($t['full_name']) . ",</p>
                        <p>We found a new job matching your preferences:</p>
                        <h3>" . htmlspecialchars($job['Job_role']) . "</h3>
                        <p><strong>Category:</strong> " . htmlspecialchars($t['job_category']) . "</p>
                        <p>Apply now on TipTop Vacancies!</p>";
                sendEmail($t['user_email'], $subject, $msg);
            }

            // 5. Log Success (SMS Log used as generic alert log)
            $smsStmt->execute([$job['id'], $t['link_to_user'], $t['mobile_number'], $smsStatus]);
            
            // 6. Update Setting timestamp
            $updateStmt->execute([$t['setting_id']]);
            
            $sent_count++;
        }
    }

    header("Location: ../sms_panel.php?msg=Campaign Complete. Sent $sent_count messages.");

} catch (PDOException $e) {
    // If column missing, give hint
    if (strpos($e->getMessage(), 'last_alert_sent') !== false) {
        die("Database Update Required: Please run the SQL command to add 'last_alert_sent' column.");
    }
    die("System Error: " . $e->getMessage());
}

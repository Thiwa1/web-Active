<?php
require_once 'config/config.php';

try {
    // 1. Create a dummy employer profile if it doesn't exist
    $pdo->exec("INSERT INTO user_table (user_email, user_password, full_name, Birthday, male_female, user_type, mobile_number, WhatsApp_number, user_active)
                VALUES ('test_employer@example.com', 'password', 'Test Employer', '1990-01-01', 'Male', 'employer', '0771234567', '0771234567', 1)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $userId = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO employer_profile (link_to_user, employer_name, employer_mobile_no)
                VALUES ($userId, 'Test Company Ltd', '0771234567')
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $empId = $pdo->lastInsertId();

    // 2. Insert a test job with 'Online' type
    $sql = "INSERT INTO advertising_table
            (link_to_employer_profile, Job_role, job_type, Job_category, Industry, City, District, Closing_date, Approved)
            VALUES (?, 'Test Online Job', 'Online', 'IT', 'Software', 'Colombo', 'Colombo', CURDATE() + INTERVAL 30 DAY, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$empId]);
    $jobId = $pdo->lastInsertId();

    echo "Inserted Test Job ID: $jobId with type 'Online'.\n";

    // 3. Verify it was saved correctly
    $stmt = $pdo->prepare("SELECT job_type FROM advertising_table WHERE id = ?");
    $stmt->execute([$jobId]);
    $savedType = $stmt->fetchColumn();

    if ($savedType === 'Online') {
        echo "SUCCESS: Job type 'Online' saved correctly.\n";
    } else {
        echo "FAILURE: Job type saved as '$savedType'.\n";
    }

    // 4. Check Stats Calculation Logic
    $statsQuery = "SELECT job_type, COUNT(*) as count FROM advertising_table WHERE Approved = 1 GROUP BY job_type";
    $stats = $pdo->query($statsQuery)->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "Stats Dump:\n";
    print_r($stats);

    if (isset($stats['Online']) && $stats['Online'] > 0) {
         echo "SUCCESS: Stats query correctly counts 'Online' jobs.\n";
    } else {
         echo "FAILURE: Stats query missing 'Online' count.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

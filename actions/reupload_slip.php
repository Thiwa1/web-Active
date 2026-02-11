<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['new_slip']['tmp_name'])) {
    $payment_id = $_POST['payment_id'];
    $new_slip = file_get_contents($_FILES['new_slip']['tmp_name']);

    try {
        // Reset approval to 0 (Pending) and clear the rejection reason
        $stmt = $pdo->prepare("UPDATE payment_table 
                               SET Payment_slip = ?, Approval = 0, rejection_reason = NULL, payment_date = CURDATE() 
                               WHERE id = ?");
        $stmt->execute([$new_slip, $payment_id]);

        header("Location: ../employer/manage_jobs.php?msg=Slip re-uploaded successfully. Waiting for Admin review.");
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
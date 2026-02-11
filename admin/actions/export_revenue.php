<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    exit("Unauthorized");
}

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Revenue_Report_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// 1. Write the Header Row
fputcsv($output, ['Payment ID', 'Employer Name', 'Date', 'Amount (LKR)', 'Status']);

// 2. Fetch all approved payments
$query = "SELECT p.id, e.employer_name, p.payment_date, p.Totaled_received 
          FROM payment_table p
          JOIN employer_profile e ON p.employer_link = e.id
          WHERE p.Approval = 1
          ORDER BY p.payment_date DESC";

$stmt = $pdo->query($query);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['employer_name'],
        date('Y-m-d', strtotime($row['payment_date'])),
        $row['Totaled_received'],
        'Approved'
    ]);
}

fclose($output);
exit();
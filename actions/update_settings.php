<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    exit("Unauthorized");
}

// 1. HANDLE DELETIONS
if (isset($_GET['delete_type']) && isset($_GET['id'])) {
    $type = $_GET['delete_type'];
    $id = $_GET['id'];
    
    $table = match($type) {
        'district' => 'district_table',
        'city'     => 'city_table',
        'industry' => 'Industry_Setting',
        'category' => 'job_category_table',
        default    => null
    };

    if ($table) {
        try {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ../admin/settings.php?status=deleted");
        } catch (Exception $e) {
            die("Delete Error: (Check if this item is being used in a job ad) " . $e->getMessage());
        }
    }
    exit();
}

// 2. HANDLE ADDS AND UPDATES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'];
    $redirect = "../admin/settings.php?status=success"; // Default redirect

    try {
        switch ($action) {
            // NEW: Handle Pricing Update
            case 'update_price':
                $selling_price = $_POST['selling_price'];
                $unit_of_add = $_POST['Unit_of_add'];
                
                $exists = $pdo->query("SELECT id FROM Price_setting LIMIT 1")->fetch();
                if ($exists) {
                    $sql = "UPDATE Price_setting SET selling_price = ?, Unit_of_add = ? WHERE id = ?";
                    $pdo->prepare($sql)->execute([$selling_price, $unit_of_add, $exists['id']]);
                } else {
                    $sql = "INSERT INTO Price_setting (selling_price, Unit_of_add) VALUES (?, ?)";
                    $pdo->prepare($sql)->execute([$selling_price, $unit_of_add]);
                }
                
                // Redirect back to dashboard since that's where the price modal is
                $redirect = "../admin/dashboard.php?status=price_updated";
                break;

            case 'add_district':
                $pdo->prepare("INSERT INTO district_table (District_name) VALUES (?)")->execute([$_POST['district_name']]);
                break;

            case 'add_city':
                $pdo->prepare("INSERT INTO city_table (City, City_link) VALUES (?, ?)")->execute([$_POST['city_name'], $_POST['district_id']]);
                break;

            case 'add_industry':
                $pdo->prepare("INSERT INTO Industry_Setting (Industry_name) VALUES (?)")->execute([$_POST['industry_name']]);
                break;

            case 'add_category':
                $pdo->prepare("INSERT INTO job_category_table (Description) VALUES (?)")->execute([$_POST['cat_name']]);
                break;

            case 'edit_entry':
                $type = $_POST['type'];
                $id = $_POST['id'];
                $val = $_POST['new_value'];

                $sql = match($type) {
                    'district' => "UPDATE district_table SET District_name = ? WHERE id = ?",
                    'city'     => "UPDATE city_table SET City = ? WHERE id = ?",
                    'industry' => "UPDATE Industry_Setting SET Industry_name = ? WHERE id = ?",
                    'category' => "UPDATE job_category_table SET Description = ? WHERE id = ?",
                };
                $pdo->prepare($sql)->execute([$val, $id]);
                break;

            case 'update_company':
                $pdo->prepare("UPDATE Compan_details SET company_name = ?, TP_No = ?, addres1 = ? WHERE id = 1")
                    ->execute([$_POST['company_name'], $_POST['TP_No'], $_POST['addres1']]);
                break;
        }
        
        header("Location: " . $redirect);
        exit();

    } catch (Exception $e) {
        die("Update Error: " . $e->getMessage());
    }
}
<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    exit("Unauthorized");
}

// 1. HANDLE DELETIONS (Refined logic for GET param handling)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    
    $table = match($type) {
        'district' => 'district_table',
        'city'     => 'city_table',
        'industry' => 'Industry_Setting',
        'category' => 'job_category_table',
        'site_setting' => 'site_settings', // Added support for deleting site settings
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
            case 'update_price':
                $selling_price = $_POST['selling_price'];
                $unit_of_add = $_POST['Unit_of_add'];
                $id = $_POST['price_id'];
                
                if ($id) {
                    $sql = "UPDATE Price_setting SET selling_price = ?, Unit_of_add = ? WHERE id = ?";
                    $pdo->prepare($sql)->execute([$selling_price, $unit_of_add, $id]);
                } else {
                    $sql = "INSERT INTO Price_setting (selling_price, Unit_of_add) VALUES (?, ?)";
                    $pdo->prepare($sql)->execute([$selling_price, $unit_of_add]);
                }
                $redirect = "../admin/dashboard.php?status=price_updated";
                break;

            case 'add_entry':
                $type = $_POST['type'];
                $val = $_POST['value'];

                if ($type === 'district') {
                    $pdo->prepare("INSERT INTO district_table (District_name) VALUES (?)")->execute([$val]);
                } elseif ($type === 'city') {
                    $dId = $_POST['district_id'];
                    $pdo->prepare("INSERT INTO city_table (City, City_link) VALUES (?, ?)")->execute([$val, $dId]);
                } elseif ($type === 'industry') {
                    $pdo->prepare("INSERT INTO Industry_Setting (Industry_name) VALUES (?)")->execute([$val]);
                } elseif ($type === 'category') {
                    $pdo->prepare("INSERT INTO job_category_table (Description) VALUES (?)")->execute([$val]);
                } elseif ($type === 'site_setting') {
                    // Check if key exists
                    $check = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
                    $check->execute([$val]);
                    if (!$check->fetch()) {
                        $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, '')")->execute([$val]);
                    }
                }
                break;

            case 'edit_entry':
                $type = $_POST['type'];
                $id = $_POST['id'];
                $val = $_POST['new_value'];

                if ($type === 'site_setting') {
                    $sql = "UPDATE site_settings SET setting_value = ? WHERE id = ?";
                } else {
                    $sql = match($type) {
                        'district' => "UPDATE district_table SET District_name = ? WHERE id = ?",
                        'city'     => "UPDATE city_table SET City = ? WHERE id = ?",
                        'industry' => "UPDATE Industry_Setting SET Industry_name = ? WHERE id = ?",
                        'category' => "UPDATE job_category_table SET Description = ? WHERE id = ?",
                        default => null
                    };
                }

                if ($sql) $pdo->prepare($sql)->execute([$val, $id]);
                break;

            case 'update_company':
                // Handle text fields
                $pdo->prepare("UPDATE Compan_details SET company_name = ?, TP_No = ?, addres1 = ?, addres2 = ?, addres3 = ?, Compan_detailscol = ? WHERE id = 1")
                    ->execute([
                        $_POST['company_name'], $_POST['TP_No'],
                        $_POST['addres1'], $_POST['addres2'], $_POST['addres3'],
                        $_POST['Compan_detailscol']
                    ]);

                // Handle Logo Upload
                if (!empty($_FILES['logo']['tmp_name'])) {
                    require_once '../config/upload_helper.php';
                    $raw_path = uploadImage($_FILES['logo'], '../uploads/system/');
                    $path = str_replace(['../', './'], '', $raw_path);
                    $blob = file_get_contents($_FILES['logo']['tmp_name']);

                    $pdo->prepare("UPDATE Compan_details SET logo = ?, logo_path = ? WHERE id = 1")
                        ->execute([$blob, $path]);
                }
                break;

            case 'update_site_settings':
                if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE id = ?");
                    foreach ($_POST['settings'] as $id => $val) {
                        // For checkboxes (switches), if unchecked, it won't be sent unless handled by hidden input trick.
                        // My UI uses hidden input trick (value=0 then checkbox value=1).
                        $stmt->execute([$val, $id]);
                    }
                }
                break;
        }
        
        header("Location: " . $redirect);
        exit();

    } catch (Exception $e) {
        die("Update Error: " . $e->getMessage());
    }
}

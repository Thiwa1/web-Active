<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/upload_helper.php';

// Security check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Unauthorized Access");
}

// Check for empty POST (File upload limit exceeded)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    die("Error: No data received. The file you uploaded might be larger than the server allows (post_max_size/upload_max_filesize).");
}

/** * HANDLE POST REQUESTS (Add / Update)
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action_type'] ?? '';

    try {
        // 1. Update Portal Branding
        if ($action == 'update_company') {
            $name = $_POST['company_name'];
            $tp = $_POST['TP_No'];
            $a1 = $_POST['addres1'];
            $a2 = $_POST['addres2'];
            $a3 = $_POST['addres3'];
            $extra = $_POST['Compan_detailscol'];
            $logo_path = null;

            if (!empty($_FILES['logo']['tmp_name'])) {
                try {
                    $logo_path = uploadImage($_FILES['logo'], '../../uploads/system/');
                } catch (Exception $e) { die("Image Error: " . $e->getMessage()); }
            }

            // Retry logic for schema updates
            $maxRetries = 1;
            $attempt = 0;
            $success = false;

            while ($attempt <= $maxRetries && !$success) {
                try {
                    $attempt++;
                    
                    // Check if row exists
                    $exists = $pdo->query("SELECT id FROM Compan_details LIMIT 1")->fetch();

                    if ($exists) {
                        // UPDATE
                        $sql = "UPDATE Compan_details SET company_name=?, TP_No=?, addres1=?, addres2=?, addres3=?, Compan_detailscol=?";
                        $params = [$name, $tp, $a1, $a2, $a3, $extra];
                        
                        if ($logo_path) {
                            $sql .= ", logo_path=?";
                            $params[] = $logo_path;
                        }
                        
                        $sql .= " WHERE id=?";
                        $params[] = $exists['id'];
                        
                        $pdo->prepare($sql)->execute($params);
                    } else {
                        // INSERT
                        $sql = "INSERT INTO Compan_details (company_name, TP_No, addres1, addres2, addres3, Compan_detailscol, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $params = [$name, $tp, $a1, $a2, $a3, $extra, $logo_path];
                        $pdo->prepare($sql)->execute($params);
                    }
                    
                    $success = true;

                } catch (PDOException $e) {
                    // Check for Data Too Long (Truncation) error
                    if (($e->getCode() == '22001' || strpos($e->getMessage(), 'too long') !== false) && $attempt <= $maxRetries) {
                        // FIX: Alter table to widen columns
                        $pdo->exec("ALTER TABLE Compan_details MODIFY company_name VARCHAR(255)");
                        $pdo->exec("ALTER TABLE Compan_details MODIFY TP_No VARCHAR(100)");
                        $pdo->exec("ALTER TABLE Compan_details MODIFY addres1 VARCHAR(255)");
                        $pdo->exec("ALTER TABLE Compan_details MODIFY addres2 VARCHAR(255)");
                        $pdo->exec("ALTER TABLE Compan_details MODIFY addres3 VARCHAR(255)");
                        $pdo->exec("ALTER TABLE Compan_details MODIFY Compan_detailscol TEXT");
                        
                        // Loop will retry the insert/update
                        continue;
                    } else {
                        throw $e; // Re-throw other errors
                    }
                }
            }

            header("Location: ../settings.php?status=updated#pane-general");
        }

        // 5. Update Pricing Model
        elseif ($action == 'update_price') {
            $price = $_POST['selling_price'];
            $units = $_POST['Unit_of_add'];
            $id    = $_POST['price_id'];

            // Check if row exists
            $exists = $pdo->query("SELECT id FROM Price_setting LIMIT 1")->fetch();

            if ($exists) {
                $sql = "UPDATE Price_setting SET selling_price = ?, Unit_of_add = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$price, $units, $exists['id']]);
            } else {
                $sql = "INSERT INTO Price_setting (selling_price, Unit_of_add) VALUES (?, ?)";
                $pdo->prepare($sql)->execute([$price, $units]);
            }

            header("Location: ../dashboard.php?status=price_updated");
            exit();
        }

        // 4. Update SMS/API Settings (Bulk Update)
        elseif ($action == 'update_site_settings') {
            if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                $sql = "UPDATE site_settings SET setting_value = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                foreach ($_POST['settings'] as $id => $val) {
                    $stmt->execute([$val, $id]);
                }
            }
            header("Location: ../settings.php?status=updated#pane-sms");
            exit();
        }

        // 2. Edit Existing Entry (District, Industry, etc)
        elseif ($action == 'edit_entry') {
            $table = getTableName($_POST['type']);
            $col   = getColumnName($_POST['type']);
            
            $sql = "UPDATE $table SET $col = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$_POST['new_value'], $_POST['id']]);

            // Redirect with tab focus
            $hash = ($table == 'site_settings') ? '#pane-sms' : 
                   (($table == 'district_table' || $table == 'city_table') ? '#pane-geo' : '#pane-taxonomy');
            header("Location: ../settings.php?status=success" . $hash);
        }

        // 3. Add New Entry
        elseif ($action == 'add_entry') {
            $table = getTableName($_POST['type']);
            $col   = getColumnName($_POST['type']);
            
            if ($_POST['type'] == 'city' && !empty($_POST['district_id'])) {
                 // Use City and City_link based on codebase convention
                 $sql = "INSERT INTO city_table (City, City_link) VALUES (?, ?)";
                 $pdo->prepare($sql)->execute([$_POST['value'], $_POST['district_id']]);
            } else {
                 $sql = "INSERT INTO $table ($col) VALUES (?)";
                 $pdo->prepare($sql)->execute([$_POST['value']]);
            }

            // Redirect with tab focus
            $hash = ($table == 'site_settings') ? '#pane-sms' : 
                   (($table == 'district_table' || $table == 'city_table') ? '#pane-geo' : '#pane-taxonomy');
            header("Location: ../settings.php?status=added" . $hash);
        }
        
        else {
            // No matching action
             header("Location: ../settings.php?status=error&msg=UnknownAction");
        }

    } catch (Exception $e) {
        die("System Error: " . $e->getMessage());
    }
}

/** * HANDLE GET REQUESTS (Delete)
 */
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $table = getTableName($_GET['type']);
    $id    = (int)$_GET['id'];

    try {
        $sql = "DELETE FROM $table WHERE id = ?";
        $pdo->prepare($sql)->execute([$id]);
        
        $hash = ($table == 'site_settings') ? '#pane-sms' : 
               (($table == 'district_table' || $table == 'city_table') ? '#pane-geo' : '#pane-taxonomy');
        header("Location: ../settings.php?status=deleted" . $hash);
    } catch (Exception $e) {
        die("Deletion Error: Check if this item is being used by active jobs.");
    }
}

/**
 * HELPER: Map dynamic types to your specific DB tables
 */
function getTableName($type) {
    return [
        'district' => 'district_table',
        'city'     => 'city_table',
        'industry' => 'Industry_Setting',
        'category' => 'job_category_table',
        'site_setting' => 'site_settings'
    ][$type] ?? exit("Invalid Table Mapping");
}

function getColumnName($type) {
    return [
        'district' => 'District_name',
        'city'     => 'City',
        'industry' => 'Industry_name',
        'category' => 'Description',
        'site_setting' => 'setting_key' // For adding new keys
    ][$type] ?? exit("Invalid Column Mapping");
}

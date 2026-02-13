<?php
require_once 'config/config.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;
$category = isset($_GET['category']) ? $_GET['category'] : '';
$cities = isset($_GET['cities']) ? (array)$_GET['cities'] : [];

try {
    $sql = "SELECT a.id, a.Job_role as Position, e.employer_name as Company, a.Job_category, 
                   c.City, d.District_name, a.Opening_date, a.Closing_date, a.img_path, a.Img,
                   e.logo_path, e.employer_logo
            FROM advertising_table a
            LEFT JOIN employer_profile e ON a.link_to_employer_profile = e.id
            LEFT JOIN city_table c ON a.City = c.City
            LEFT JOIN district_table d ON a.District = d.District_name
            WHERE a.Approved = 1";
    
    $params = [];
    if (!empty($keyword)) {
        $sql .= " AND (a.Job_role LIKE ? OR e.employer_name LIKE ? OR a.Job_category LIKE ?)";
        $searchTerm = "%$keyword%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    if ($district_id > 0) {
        $sql .= " AND d.id = ?";
        $params[] = $district_id;
    }
    if (!empty($category)) {
        $sql .= " AND a.Job_category = ?";
        $params[] = $category;
    }
    if (!empty($cities)) {
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($cities), '?'));
        $sql .= " AND c.City IN ($placeholders)";
        $params = array_merge($params, $cities);
    }
    
    // Sort by most recent
    $sql .= " ORDER BY a.Opening_date DESC, a.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $desktop_html = '';
    $mobile_html = '';

    if (empty($jobs)) {
        $desktop_html = '<tr><td colspan="8" class="text-center py-5">
                <div class="text-muted"><i class="fas fa-search fa-2x mb-3"></i><br>No Jobs Found</div>
              </td></tr>';
        $mobile_html = '<div class="mobile-no-jobs">
                <i class="fas fa-search"></i>
                <h4>No Jobs Found</h4>
                <p>Try adjusting your search criteria</p>
              </div>';
    } else {
        $counter = 1;
        foreach ($jobs as $job) {
            $company = htmlspecialchars($job['Company'] ?? 'N/A');
            $location = htmlspecialchars($job['City']) . ", " . htmlspecialchars($job['District_name']);
            $initial = strtoupper(substr($company, 0, 1));
            
            // --- LOGO LOGIC ---
            // Fix: Strip ../ to ensure path is relative to root (uploads/...)
            $cleanLogoPath = '';
            if (!empty($job['logo_path'])) {
                $cleanLogoPath = str_replace(['../', './'], '', $job['logo_path']);
            }

            // Use __DIR__ to check file existence reliably from root
            $logoAbsPath = __DIR__ . '/' . $cleanLogoPath;

            if (!empty($cleanLogoPath) && file_exists($logoAbsPath)) {
                $imgTag = '<img src="' . htmlspecialchars($cleanLogoPath) . '" style="width:32px;height:32px;object-fit:cover;border-radius:6px;">';
                $mobileImgTag = '<img src="' . htmlspecialchars($cleanLogoPath) . '" class="mobile-company-icon" style="padding:0; object-fit:cover;">';

                $logoHtml = $imgTag;
                $mobileLogoHtml = $mobileImgTag;
            } elseif (!empty($job['employer_logo'])) {
                $base64Logo = base64_encode($job['employer_logo']);
                $imgTag = '<img src="data:image/jpeg;base64,' . $base64Logo . '" style="width:32px;height:32px;object-fit:cover;border-radius:6px;">';
                $mobileImgTag = '<img src="data:image/jpeg;base64,' . $base64Logo . '" class="mobile-company-icon" style="padding:0; object-fit:cover;">';

                $logoHtml = $imgTag;
                $mobileLogoHtml = $mobileImgTag;
            } else {
                 $logoHtml = '<div class="company-icon">' . $initial . '</div>';
                 $mobileLogoHtml = '<div class="mobile-company-icon">' . $initial . '</div>';
            }

            // --- BANNER LOGIC (Mobile only mainly, but good to have) ---
            $banner = '';
            // Fix: Strip ../ for banner too
            $cleanImgPath = '';
            if (!empty($job['img_path'])) {
                $cleanImgPath = str_replace(['../', './'], '', $job['img_path']);
            }
            $imgAbsPath = __DIR__ . '/' . $cleanImgPath;

            if(!empty($cleanImgPath) && file_exists($imgAbsPath)) {
                $banner = '<div style="height: 140px; overflow: hidden; border-radius: 12px; margin-bottom: 15px;">
                            <img src="' . htmlspecialchars($cleanImgPath) . '" class="w-100 h-100" style="object-fit: cover;">
                           </div>';
            } elseif(!empty($job['Img'])) {
                 $banner = '<div style="height: 140px; overflow: hidden; border-radius: 12px; margin-bottom: 15px;">
                            <img src="data:image/jpeg;base64,' . base64_encode($job['Img']) . '" class="w-100 h-100" style="object-fit: cover;">
                           </div>';
            }

            // --- DATE BADGES ---
            $today = date('Y-m-d');
            $closing = $job['Closing_date'];
            $isOpen = ($closing >= $today);
            // $statusBadge is not used in current template but logic is here

            // --- DESKTOP ROW ---
            $desktop_html .= '<tr>
                <td class="cell-number">' . $counter . '</td>
                <td class="cell-position"><strong>' . htmlspecialchars($job['Position']) . '</strong></td>
                <td class="cell-company">
                    <div class="company-info">
                        ' . $logoHtml . '
                        <span>' . $company . '</span>
                    </div>
                </td>
                <td class="cell-category"><span class="badge bg-light text-dark border">' . htmlspecialchars($job['Job_category']) . '</span></td>
                <td class="cell-location">' . $location . '</td>
                <td class="cell-date">' . $job['Opening_date'] . '</td>
                <td class="cell-date text-danger">' . $job['Closing_date'] . '</td>
                <td class="text-center">
                    <button class="btn-view-excel view-job" data-id="' . $job['id'] . '">View</button>
                </td>
            </tr>';

            // --- MOBILE CARD ---
            $mobile_html .= '<div class="mobile-card">
                    ' . $banner . '

                    <div class="mobile-card-header">
                        <div class="mobile-job-title">' . htmlspecialchars($job['Position']) . '</div>
                        <div class="mobile-job-badge">NEW</div>
                    </div>

                    <div class="mobile-company-info">
                        ' . $mobileLogoHtml . '
                        <div class="mobile-company-details">
                            <div class="mobile-company-name">' . $company . '</div>
                            <div class="mobile-job-category">' . htmlspecialchars($job['Job_category']) . '</div>
                        </div>
                    </div>

                    <div class="mobile-job-meta">
                        <div class="mobile-meta-item">
                            <i class="fas fa-map-marker-alt"></i> ' . $location . '
                        </div>
                         <div class="mobile-meta-item">
                            <i class="fas fa-briefcase"></i> Full Time
                        </div>
                    </div>

                    <div class="mobile-dates-container">
                        <div class="mobile-date-card open">
                            <div class="mobile-date-label">Opening</div>
                            <div class="mobile-date-value">' . date('M d', strtotime($job['Opening_date'])) . '</div>
                        </div>
                        <div class="mobile-date-card close">
                            <div class="mobile-date-label">Closing</div>
                            <div class="mobile-date-value">' . date('M d', strtotime($job['Closing_date'])) . '</div>
                        </div>
                    </div>

                    <div class="mobile-card-footer">
                        <div class="mobile-location text-muted">
                            <small>Posted ' . date('M d', strtotime($job['Opening_date'])) . '</small>
                        </div>
                        <button class="btn-view-mobile view-job" data-id="' . $job['id'] . '">
                            View Details <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                  </div>';

            $counter++;
        }
    }

    echo $desktop_html;
    echo "###SPLIT###";
    echo $mobile_html;

} catch (Exception $e) {
    // Return error formatted for both splits
    echo '<tr><td colspan="8" class="text-danger">Error: ' . $e->getMessage() . '</td></tr>';
    echo "###SPLIT###";
    echo '<div class="alert alert-danger">Error loading jobs</div>';
}

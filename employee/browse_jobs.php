<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Jobs | JobQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8fafc; }
        .job-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid #e2e8f0; transition: 0.2s; }
        .job-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container py-5">
        <h3 class="fw-bold mb-4">Latest Opportunities</h3>
        <div id="job-list">
            <!-- Loaded via AJAX -->
            <div class="text-center"><div class="spinner-border text-primary"></div></div>
        </div>
    </div>

    <script>
    async function loadJobs() {
        try {
            const res = await fetch('fetch_jobs_employee.php');
            const jobs = await res.json();
            const container = document.getElementById('job-list');
            
            if(jobs.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No active jobs found.</div>';
                return;
            }

            container.innerHTML = jobs.map(job => `
                <div class="job-card d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="${job.logo_path ? '../uploads/employers/'+job.logo_path.split('/').pop() : 'https://via.placeholder.com/50'}" class="rounded me-3" width="50" height="50">
                        <div>
                            <h5 class="mb-1 fw-bold">${job.Position}</h5>
                            <div class="text-muted small">${job.Company} • ${job.City}</div>
                        </div>
                    </div>
                    <div>
                        ${job.applied > 0 
                            ? '<button class="btn btn-secondary btn-sm" disabled><i class="fas fa-check"></i> Applied</button>' 
                            : `<button onclick="apply(${job.id})" class="btn btn-primary btn-sm fw-bold">Apply Now</button>`
                        }
                    </div>
                </div>
            `).join('');
        } catch(e) {
            console.error(e);
        }
    }

    async function apply(jobId) {
        const result = await Swal.fire({
            title: 'Apply for this job?',
            text: "Your profile will be sent to the employer.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Apply!'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch('../actions/process_profile_apply_ajax.php', {
                    method: 'POST',
                    body: JSON.stringify({job_id: jobId})
                });
                const data = await res.json();
                
                if(data.success) {
                    Swal.fire('Applied!', 'Application sent successfully.', 'success');
                    loadJobs(); // Refresh
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }
    }

    loadJobs();
    </script>
</body>
</html>

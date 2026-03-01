// City Dropdown Logic
const cityBtn = document.getElementById('cityBtn');
const cityMenu = document.getElementById('cityMenu');
const citySearch = document.getElementById('citySearch');
if (cityBtn) {
    cityBtn.onclick = (e) => {
        e.stopPropagation();
        cityMenu.classList.toggle('show');
        cityBtn.classList.toggle('active');
    };
    document.onclick = (e) => {
        if(!cityMenu.contains(e.target) && e.target !== cityBtn) {
            cityMenu.classList.remove('show');
            cityBtn.classList.remove('active');
        }
    };
}
if (citySearch) {
    citySearch.onkeyup = function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.city-item').forEach(i => {
            i.style.display = i.innerText.toLowerCase().includes(val) ? "block" : "none";
        });
    };
}
// Load Cities based on District
if (document.getElementById('districtSelect')) {
    document.getElementById('districtSelect').onchange = async function() {
        const dId = this.value;
        const content = document.getElementById('cityContent');

        if(!dId) {
            content.innerHTML = '<span class="text-muted"><i class="fas fa-info-circle me-2"></i>Select a district first</span>';
            return;
        }

        content.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading cities...</span>';

        try {
            const res = await fetch(`get_cities.php?district_id=${dId}`);
            const cities = await res.json();
            if(cities.length === 0) {
                content.innerHTML = '<span class="text-muted"><i class="fas fa-exclamation-circle me-2"></i>No cities found</span>';
                return;
            }
            content.innerHTML = cities.map(c => `
                <div class="city-item">
                    <input type="checkbox" name="cities[]" value="${c.City}" class="city-check me-2" id="c_${c.id}">
                    <label for="c_${c.id}">${c.City}</label>
                </div>
            `).join('');
            // Add change handlers
            document.querySelectorAll('.city-check').forEach(box => {
                box.onchange = () => {
                    const count = document.querySelectorAll('.city-check:checked').length;
                    document.getElementById('cityLabel').innerText = count > 0 ? `${count} City Selected` : "Select cities...";
                    fetchJobs();
                };
            });
        } catch(error) {
            content.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading cities</span>';
            console.error('Error loading cities:', error);
        }
        fetchJobs();
    };
}
// AJAX Job Fetching
async function fetchJobs() {
    const loader = document.getElementById('loader');
    const desktopResults = document.getElementById('desktopResults');
    const mobileResults = document.getElementById('mobileResults');

    if (loader) loader.classList.remove('d-none');

    try {
        const searchForm = document.getElementById('searchForm');
        if (!searchForm) return;
        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData).toString();

        const res = await fetch('fetch_jobs.php?' + params);
        const rawText = await res.text();

        // Split the response using our custom marker
        const parts = rawText.split('###SPLIT###');

        if (parts.length === 2) {
            if (desktopResults) desktopResults.innerHTML = parts[0];
            if (mobileResults) mobileResults.innerHTML = parts[1];

            // Update the job count (count <tr> rows)
            const count = desktopResults ? desktopResults.querySelectorAll('tr').length : 0;
            const actualCount = desktopResults && desktopResults.innerText.includes("No Jobs Found") ? 0 : count;
            const countEl = document.getElementById('jobCount');
            if (countEl) countEl.textContent = actualCount;
        }

    } catch(error) {
        console.error('Fetch error:', error);
        if (desktopResults) desktopResults.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading data</td></tr>';
        if (mobileResults) mobileResults.innerHTML = '<div class="mobile-no-jobs"><i class="fas fa-exclamation-circle"></i><h4>Error Loading Jobs</h4><p>Please try again later</p></div>';
    } finally {
        if (loader) loader.classList.add('d-none');
    }
}
// Category Filter
function setupCategoryLinks() {
    const links = document.querySelectorAll('.cat-link');
    links.forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();

            // Update active state
            const val = this.dataset.val;
            links.forEach(a => {
                if(a.dataset.val === val) a.classList.add('active');
                else a.classList.remove('active');
            });
            const hiddenCat = document.getElementById('hiddenCat');
            if (hiddenCat) hiddenCat.value = val;
            fetchJobs();
            // Close offcanvas if open (mobile)
            const offcanvasEl = document.getElementById('filterOffcanvas');
            if (offcanvasEl) {
                // Assuming bootstrap is loaded globally
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (bsOffcanvas) bsOffcanvas.hide();
            }
        };
    });
}
setupCategoryLinks();
// Search Form Submit
if (document.getElementById('searchForm')) {
    document.getElementById('searchForm').onsubmit = function(e) {
        e.preventDefault();
        fetchJobs();
    };
}
// Load jobs on page load
window.addEventListener('load', fetchJobs);
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.view-job');
    if (btn) {
        e.preventDefault();
        const jobId = btn.getAttribute('data-id');
        if (jobId) {
            window.location.href = 'job_details.php?id=' + jobId;
        }
    }
});

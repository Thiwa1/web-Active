/* Pro Plus Career — main.js */
(function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────
    function basePath() {
        // Detect if we are inside a sub-directory
        const path = window.location.pathname;
        if (/\/(admin|employer|employee)\//.test(path)) return '../';
        return './';
    }

    function formatDate(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function logoHtml(job, cssClass) {
        if (job.logo_path) {
            return `<img src="${basePath()}${job.logo_path.replace(/^(\.\.\/|\.\/)/,'')}" class="${cssClass}" onerror="this.style.display='none'">`;
        }
        const letter = (job.Company || 'J').charAt(0).toUpperCase();
        return `<div class="job-card-initial">${letter}</div>`;
    }

    // ── City Dropdown ─────────────────────────────────────────
    const cityBtn  = document.getElementById('cityBtn');
    const cityMenu = document.getElementById('cityMenu');
    const citySearch = document.getElementById('citySearch');
    const cityContent = document.getElementById('cityContent');
    const cityLabel = document.getElementById('cityLabel');
    const districtSelect = document.getElementById('districtSelect');
    let selectedCities = [];

    if (cityBtn && cityMenu) {
        cityBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            cityMenu.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!cityMenu.contains(e.target) && e.target !== cityBtn) {
                cityMenu.classList.remove('show');
            }
        });
    }

    if (districtSelect) {
        districtSelect.addEventListener('change', function () {
            const districtId = this.value;
            selectedCities = [];
            updateCityLabel();

            if (!districtId) {
                cityContent.innerHTML = '<span class="text-muted small">Select a district first</span>';
                return;
            }

            cityContent.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

            fetch(`${basePath()}get_cities.php?district_id=${districtId}`)
                .then(r => r.json())
                .then(cities => {
                    if (!cities.length) {
                        cityContent.innerHTML = '<span class="text-muted small">No cities found</span>';
                        return;
                    }
                    cityContent.innerHTML = cities.map(c => `
                        <label class="city-checkbox">
                            <input type="checkbox" value="${c.City}" class="city-cb">
                            <span>${c.City}</span>
                        </label>`).join('');

                    cityContent.querySelectorAll('.city-cb').forEach(cb => {
                        cb.addEventListener('change', function () {
                            if (this.checked) {
                                selectedCities.push(this.value);
                            } else {
                                selectedCities = selectedCities.filter(v => v !== this.value);
                            }
                            updateCityLabel();
                            loadJobs();
                        });
                    });
                })
                .catch(() => { cityContent.innerHTML = '<span class="text-muted small">Error loading cities</span>'; });
        });
    }

    if (citySearch) {
        citySearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            cityContent.querySelectorAll('.city-checkbox').forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    function updateCityLabel() {
        if (!cityLabel) return;
        cityLabel.textContent = selectedCities.length
            ? selectedCities.slice(0, 2).join(', ') + (selectedCities.length > 2 ? ` +${selectedCities.length - 2}` : '')
            : 'Select cities...';
        cityLabel.className = selectedCities.length ? 'text-dark' : 'text-muted';
    }

    // ── Job Loading ───────────────────────────────────────────
    const searchForm    = document.getElementById('searchForm');
    const desktopResults = document.getElementById('desktopResults');
    const mobileResults  = document.getElementById('mobileResults');
    const loader         = document.getElementById('loader');
    const jobCount       = document.getElementById('jobCount');
    const hiddenCat      = document.getElementById('hiddenCat');

    function buildUrl() {
        const params = new URLSearchParams();
        const kw = document.getElementById('keyword');
        if (kw && kw.value.trim()) params.set('q', kw.value.trim());
        if (districtSelect && districtSelect.value) params.set('district_id', districtSelect.value);
        if (hiddenCat && hiddenCat.value) params.set('category', hiddenCat.value);
        selectedCities.forEach(c => params.append('cities[]', c));
        return `${basePath()}fetch_jobs.php?${params.toString()}`;
    }

    function loadJobs() {
        if (!desktopResults && !mobileResults) return;
        if (loader) loader.classList.remove('d-none');

        fetch(buildUrl())
            .then(r => r.text())
            .then(raw => {
                if (loader) loader.classList.add('d-none');
                const parts = raw.split('###SPLIT###');
                const desktopHtml = parts[0] || '';
                const mobileHtml  = parts[1] || '';

                if (desktopResults) desktopResults.innerHTML = desktopHtml;
                if (mobileResults)  mobileResults.innerHTML  = mobileHtml;

                // Update count badge
                const rows = desktopResults ? desktopResults.querySelectorAll('tr').length : 0;
                if (jobCount) jobCount.textContent = rows;
            })
            .catch(() => {
                if (loader) loader.classList.add('d-none');
                const msg = '<tr><td colspan="8" class="text-center text-muted py-5">Failed to load jobs. Please try again.</td></tr>';
                if (desktopResults) desktopResults.innerHTML = msg;
            });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            loadJobs();
        });
    }

    // ── View Job Button ───────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.view-job');
        if (!btn) return;
        const id = btn.dataset.id;
        if (id) window.location.href = `${basePath()}job_view.php?id=${id}`;
    });

    // ── Category Filter ───────────────────────────────────────
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.cat-link');
        if (!link) return;

        document.querySelectorAll('.cat-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        if (hiddenCat) hiddenCat.value = link.dataset.val || '';
        loadJobs();

        // close mobile offcanvas if open
        const offcanvas = document.getElementById('filterOffcanvas');
        if (offcanvas && offcanvas.classList.contains('show')) {
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
            if (bsOffcanvas) bsOffcanvas.hide();
        }
    });

    // ── Initial Load ──────────────────────────────────────────
    if (desktopResults || mobileResults) {
        loadJobs();
    }

})();

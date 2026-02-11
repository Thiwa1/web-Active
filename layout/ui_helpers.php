<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Global Loader Styles -->
<style>
    #global-loader {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: none;
        align-items: center; justify-content: center;
        backdrop-filter: blur(8px);
    }
</style>

<!-- Loader HTML -->
<div id="global-loader">
    <div class="text-center">
        <div class="tiptop-loader mb-3">
            <span>T</span><span>i</span><span>p</span>&nbsp;<span>T</span><span>o</span><span>p</span>
        </div>
        <h6 class="fw-bold text-primary tracking-wide small text-uppercase">Processing Request...</h6>
    </div>
</div>

<!-- Global Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Loader Logic
        const loader = document.getElementById('global-loader');
        
        // Show loader on form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.checkValidity()) {
                    // 1. Show Global Overlay
                    loader.style.display = 'flex';

                    // 2. Change Submit Button Text to Tip Top Animation
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn) {
                        // Store original width to prevent collapse
                        const width = btn.offsetWidth;
                        btn.style.width = width + 'px';
                        btn.setAttribute('disabled', 'true');

                        btn.innerHTML = `
                            <div class="tiptop-loader">
                                <span>T</span><span>i</span><span>p</span>&nbsp;<span>T</span><span>o</span><span>p</span>
                            </div>
                        `;
                    }
                }
            });
        });

        // Hide loader when page is fully loaded (failsafe)
        window.addEventListener('load', () => {
            loader.style.display = 'none';
        });

        // 2. Notification Logic (URL Params)
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success') || urlParams.has('msg')) {
            const msg = urlParams.get('success') || urlParams.get('msg');
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: msg,
                confirmButtonColor: '#4f46e5',
                timer: 3000,
                timerProgressBar: true
            });
        }

        if (urlParams.has('error') || urlParams.has('err')) {
            const err = urlParams.get('error') || urlParams.get('err');
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: err,
                confirmButtonColor: '#ef4444'
            });
        }
    });

    // Helper for manual triggering
    function showLoading() {
        document.getElementById('global-loader').style.display = 'flex';
    }
    
    function hideLoading() {
        document.getElementById('global-loader').style.display = 'none';
    }
</script>

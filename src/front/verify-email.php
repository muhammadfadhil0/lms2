<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi reCAPTCHA - Point</title>
    
    <?php require '../../assets/head.php'; ?>
    <?php 
    // Tentukan environment dan site key yang sesuai
    $isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1');
    $siteKey = $isLocalhost ? '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' : '6LfWLcgrAAAAAEJA82JgT_kkvxv6BPzcKbmXR6zP';
    ?>
    
    <!-- Load reCAPTCHA v3 API -->
    <script>
        window.recaptchaSiteKey = '<?php echo $siteKey; ?>';
        window.isLocalhost = <?php echo $isLocalhost ? 'true' : 'false'; ?>;
        console.log('reCAPTCHA Site Key:', window.recaptchaSiteKey);
        console.log('Environment:', window.isLocalhost ? 'localhost' : 'production');
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>" async defer></script>
</head>

<body>
    <?php
    // Ambil email dari parameter URL atau session
    $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
    $username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
    $namaLengkap = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
    $role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : 'siswa';
    
    if (empty($email)) {
        header('Location: ../../login.php');
        exit();
    }
    ?>
    
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img src="../../assets/img/logo.png" alt="Point" class="mx-auto h-20 w-auto" />
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Verifikasi Pendaftaran</h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Silakan selesaikan verifikasi reCAPTCHA untuk mengaktifkan akun Anda
            </p>
            <p class="mt-1 text-center text-sm font-medium text-orange">
                <?php echo $email; ?>
            </p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <?php if ($isLocalhost): ?>
            <div class="mb-4 p-3 text-xs rounded bg-yellow-50 border border-yellow-300 text-yellow-800">
                MODE PENGEMBANGAN: reCAPTCHA mungkin gagal di localhost. Sistem akan melakukan bypass jika token kosong. Jangan aktifkan ini di production.
            </div>
            <?php endif; ?>
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'recaptcha_failed') {
                    echo 'Verifikasi reCAPTCHA gagal. Silakan coba lagi!';
                } elseif ($_GET['error'] == 'system_error') {
                    echo 'Terjadi kesalahan sistem. Silakan coba lagi.';
                } else {
                    echo 'Terjadi kesalahan. Silakan coba lagi.';
                }
                echo '<button type="button" class="absolute top-2 right-2 text-red-700 hover:text-red-900" onclick="document.getElementById(\'error-alert\').style.display=\'none\'">';
                echo '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">';
                echo '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
                echo '</svg>';
                echo '</button>';
                echo '</div>';
            }
            ?>
            
            <form id="verification-form" action="../logic/verify-recaptcha-process.php" method="POST" class="space-y-6">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>" />
                <input type="hidden" name="namaLengkap" value="<?php echo htmlspecialchars($namaLengkap); ?>" />
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>" />
                <input type="hidden" name="recaptcha_response" id="recaptcha_response" />
                
                <div class="text-center">
                    <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                        <div class="flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Hampir Selesai!</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Klik tombol di bawah untuk menyelesaikan pendaftaran Anda
                        </p>
                        
                        <button type="button" id="verify-btn" 
                                class="w-full bg-orange text-white py-3 px-4 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="btn-text">Verifikasi & Aktifkan Akun</span>
                            <span id="btn-loading" class="hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memverifikasi...
                            </span>
                        </button>
                    </div>
                    
                    <p class="mt-4 text-xs text-gray-500">
                        Dilindungi oleh reCAPTCHA dan tunduk pada 
                        <a href="https://policies.google.com/privacy" target="_blank" class="text-orange hover:underline">Kebijakan Privasi</a> dan 
                        <a href="https://policies.google.com/terms" target="_blank" class="text-orange hover:underline">Persyaratan Layanan</a> Google.
                    </p>
                </div>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                <a href="../../login.php" class="font-semibold text-gray-600 hover:text-gray-800">
                    ← Kembali ke halaman login
                </a>
            </p>
        </div>
    </div>

    <script>
        (function() {
            const MAX_INIT_ATTEMPTS = 20; // ~10s (interval 500ms)
            const INIT_INTERVAL = 500;
            let initAttempts = 0;
            let buttonBound = false;

            function logDebug(msg, data) {
                console.log('[reCAPTCHA]', msg, data || '');
            }

            function enableButton(btn, btnText, btnLoading) {
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
            }

            function disableButton(btn, btnText, btnLoading) {
                btn.disabled = true;
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
            }

            function bindButton() {
                if (buttonBound) return;
                const btn = document.getElementById('verify-btn');
                if (!btn) return;
                buttonBound = true;
                btn.addEventListener('click', executeRecaptcha);
                logDebug('Button bound');
            }

            function executeRecaptcha() {
                if (typeof grecaptcha === 'undefined') {
                    alert('reCAPTCHA belum siap. Tunggu sebentar dan coba lagi.');
                    logDebug('Execute aborted: grecaptcha undefined');
                    return;
                }
                const btn = document.getElementById('verify-btn');
                const btnText = document.getElementById('btn-text');
                const btnLoading = document.getElementById('btn-loading');
                disableButton(btn, btnText, btnLoading);
                logDebug('Executing with action=register');
                grecaptcha.execute(window.recaptchaSiteKey, { action: 'register' })
                    .then(function(token) {
                        if (!token || typeof token !== 'string') {
                            throw new Error('Token kosong atau tidak valid');
                        }
                        logDebug('Token received (first 12 chars)', token.slice(0,12) + '...');
                        document.getElementById('recaptcha_response').value = token;
                        document.getElementById('verification-form').submit();
                    })
                    .catch(function(error) {
                        // Jika di localhost, abaikan error reCAPTCHA dan lanjutkan submit
                        if (window.isLocalhost) {
                            logDebug('reCAPTCHA failed on localhost, bypassing client-side check.', error);
                            document.getElementById('recaptcha_response').value = ''; // Kirim token kosong
                            document.getElementById('verification-form').submit();
                            return;
                        }

                        logDebug('Execution error', error);
                        enableButton(btn, btnText, btnLoading);
                        alert('Verifikasi gagal: ' + (error && error.message ? error.message : 'Error tidak diketahui') + '. Coba lagi.');
                    });
            }

            function tryInit() {
                initAttempts++;
                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                    logDebug('grecaptcha tersedia. Binding...');
                    grecaptcha.ready(function() {
                        bindButton();
                    });
                    return;
                }
                if (initAttempts < MAX_INIT_ATTEMPTS) {
                    if (initAttempts % 5 === 0) logDebug('Menunggu grecaptcha... attempt', initAttempts);
                    setTimeout(tryInit, INIT_INTERVAL);
                } else {
                    logDebug('Gagal load reCAPTCHA setelah attempts', initAttempts);
                    const btn = document.getElementById('verify-btn');
                    if (btn) btn.title = 'reCAPTCHA gagal dimuat. Refresh halaman.';
                }
            }

            // Start attempts
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', tryInit);
            } else {
                tryInit();
            }
            window.addEventListener('load', function() { setTimeout(tryInit, 1000); });
        })();
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Username Baru - Point</title>
    <?php require '../../assets/head.php'; ?>
</head>

<body>
    <?php
    session_start();
    
    // Cek apakah user sudah melalui verifikasi OTP
    if (!isset($_SESSION['forgot_username_email']) || !isset($_SESSION['otp_verified'])) {
        header('Location: forgot-username-step1.php');
        exit();
    }
    
    $email = $_SESSION['forgot_username_email'];
    
    // Function untuk sensor email
    function sensorEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) != 2) return $email;
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 3) {
            $sensoredUsername = str_repeat('*', strlen($username));
        } else {
            $sensoredUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 3) . substr($username, -1);
        }
        
        $domainParts = explode('.', $domain);
        if (count($domainParts) >= 2) {
            $mainDomain = $domainParts[0];
            $extension = end($domainParts);
            
            if (strlen($mainDomain) <= 2) {
                $sensoredDomain = str_repeat('*', strlen($mainDomain)) . '.' . $extension;
            } else {
                $sensoredDomain = substr($mainDomain, 0, 1) . str_repeat('*', strlen($mainDomain) - 1) . '.' . $extension;
            }
        } else {
            $sensoredDomain = $domain;
        }
        
        return $sensoredUsername . '@' . $sensoredDomain;
    }
    
    $sensoredEmail = sensorEmail($email);
    ?>

    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <img src="../../assets/img/logo.png" alt="Point" class="mx-auto h-20 w-auto text-center" />
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Buat Username Baru</h2>
            <p class="mt-1 text-center text-sm text-gray-600">Buatlah username baru untuk akun <strong><?php echo $sensoredEmail; ?></strong></p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'username_exists') {
                    echo 'Username sudah digunakan. Silakan pilih username lain.';
                } elseif ($_GET['error'] == 'invalid_username') {
                    echo 'Username tidak valid. Gunakan hanya huruf, angka, dan underscore. Minimal 3 karakter.';
                } elseif ($_GET['error'] == 'empty_username') {
                    echo 'Username tidak boleh kosong.';
                } elseif ($_GET['error'] == 'update_failed') {
                    echo 'Gagal memperbarui username. Silakan coba lagi.';
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

            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Verifikasi Berhasil</h3>
                        <p class="text-xs text-green-700 mt-1">Email Anda telah diverifikasi. Sekarang buat username baru untuk melanjutkan.</p>
                    </div>
                </div>
            </div>

            <form action="../logic/forgot-username-process.php" method="POST" class="space-y-6" id="usernameForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div>
                    <label for="new_username" class="block text-sm/6 font-medium text-gray-900">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
                            </svg>
                            Username Baru
                        </div>
                    </label>
                    <div class="mt-2">
                        <input id="new_username" type="text" name="new_username" required autocomplete="username" placeholder="username_baru" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                    </div>
                    <div class="mt-1">
                        <div id="username-feedback" class="text-xs"></div>
                        <div class="text-xs text-gray-500 mt-1">
                            <p>Ketentuan username:</p>
                            <ul class="list-disc list-inside ml-2 mt-1 space-y-0.5">
                                <li>Minimal 3 karakter, maksimal 20 karakter</li>
                                <li>Hanya boleh huruf, angka, dan underscore (_)</li>
                                <li>Tidak boleh dimulai dengan angka</li>
                                <li>Tidak boleh menggunakan spasi</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                                <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-900">Catatan Penting</h4>
                            <p class="text-xs text-blue-700 mt-1">Username yang Anda buat akan menggantikan username lama. Pastikan username mudah diingat karena akan digunakan untuk login.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" name="update_username" id="submitBtn" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M4 7h16l-1 10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2l-1 -10z" />
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            <path d="M12 12l0 3" />
                        </svg>
                        Simpan Username Baru
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="../../login.php" class="text-sm text-gray-600 hover:text-orange">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                        <path d="M9 14l-4 -4l4 -4" />
                        <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                    </svg>
                    Kembali ke halaman login
                </a>
            </div>
        </div>
    </div>

    <script>
        const usernameInput = document.getElementById('new_username');
        const feedback = document.getElementById('username-feedback');
        const submitBtn = document.getElementById('submitBtn');
        
        usernameInput.addEventListener('input', function() {
            const username = this.value.trim();
            validateUsername(username);
        });
        
        function validateUsername(username) {
            feedback.innerHTML = '';
            
            if (username.length === 0) {
                submitBtn.disabled = true;
                return;
            }
            
            const errors = [];
            const validations = {
                length: username.length >= 3 && username.length <= 20,
                format: /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(username),
                noStartNumber: !/^[0-9]/.test(username),
                noSpaces: !/\s/.test(username)
            };
            
            if (!validations.length) {
                errors.push('Username harus 3-20 karakter');
            }
            
            if (!validations.format) {
                errors.push('Hanya huruf, angka, dan underscore');
            }
            
            if (!validations.noStartNumber) {
                errors.push('Tidak boleh dimulai dengan angka');
            }
            
            if (!validations.noSpaces) {
                errors.push('Tidak boleh menggunakan spasi');
            }
            
            if (errors.length > 0) {
                feedback.innerHTML = '<span class="text-red-600">✗ ' + errors.join(', ') + '</span>';
                submitBtn.disabled = true;
            } else {
                feedback.innerHTML = '<span class="text-green-600">✓ Username valid</span>';
                submitBtn.disabled = false;
                
                // Check username availability
                checkUsernameAvailability(username);
            }
        }
        
        function checkUsernameAvailability(username) {
            if (username.length >= 3) {
                fetch('../logic/check-username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        feedback.innerHTML = '<span class="text-green-600">✓ Username tersedia</span>';
                        submitBtn.disabled = false;
                    } else {
                        feedback.innerHTML = '<span class="text-red-600">✗ Username sudah digunakan</span>';
                        submitBtn.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
        
        // Initial validation
        if (usernameInput.value) {
            validateUsername(usernameInput.value);
        }
    </script>
</body>

</html>
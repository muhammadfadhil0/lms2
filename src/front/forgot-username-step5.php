<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Username Baru - Point</title>
    <?php require '../../assets/head.php'; ?>
</head>

<body>
    <?php
    session_start();
    
    // Cek apakah user sudah mengganti username
    if (!isset($_SESSION['forgot_username_email']) || !isset($_SESSION['new_username'])) {
        header('Location: forgot-username-step1.php');
        exit();
    }
    
    $email = $_SESSION['forgot_username_email'];
    $newUsername = $_SESSION['new_username'];
    
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
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Username Berhasil Diperbarui</h2>
            <p class="mt-1 text-center text-sm text-gray-600">Verifikasi username baru Anda untuk melanjutkan</p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'wrong_username') {
                    echo 'Username yang dimasukkan tidak sesuai. Silakan periksa kembali.';
                } elseif ($_GET['error'] == 'empty_username') {
                    echo 'Username tidak boleh kosong.';
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

            <!-- Success Update Display -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-medium text-green-900">Berhasil Diperbarui!</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p><strong>Email:</strong> <?php echo $sensoredEmail; ?></p>
                            <p><strong>Username Baru:</strong> <span class="font-mono bg-green-100 px-2 py-1 rounded"><?php echo htmlspecialchars($newUsername); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Form -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M9 12l2 2l4 -4" />
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-900">Verifikasi Terakhir</h4>
                        <p class="text-xs text-blue-700 mt-1">Untuk keamanan, silakan ketikkan username baru Anda sekali lagi untuk konfirmasi.</p>
                    </div>
                </div>
            </div>

            <form action="../logic/forgot-username-process.php" method="POST" class="space-y-6">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="expected_username" value="<?php echo htmlspecialchars($newUsername); ?>">
                
                <div>
                    <label for="verify_username" class="block text-sm/6 font-medium text-gray-900">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
                            </svg>
                            Ketikkan username baru Anda
                        </div>
                    </label>
                    <div class="mt-2">
                        <input id="verify_username" type="text" name="verify_username" required autocomplete="off" placeholder="<?php echo htmlspecialchars($newUsername); ?>" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Masukkan: <strong><?php echo htmlspecialchars($newUsername); ?></strong></p>
                </div>

                <div>
                    <button type="submit" name="verify_final_username" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                        </svg>
                        Verifikasi & Selesai
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300" />
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white px-2 text-gray-500">atau</span>
                    </div>
                </div>

                <div class="mt-6 text-center space-y-2">
                    <a href="forgot-username-step4.php" class="block text-sm text-orange hover:text-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                        </svg>
                        Ubah username lagi
                    </a>
                    <a href="../../login.php" class="block text-sm text-gray-600 hover:text-orange">
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
    </div>

    <script>
        // Auto focus on input
        document.getElementById('verify_username').focus();
        
        // Real-time validation
        const input = document.getElementById('verify_username');
        const expectedUsername = '<?php echo htmlspecialchars($newUsername); ?>';
        
        input.addEventListener('input', function() {
            if (this.value === expectedUsername) {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
            } else {
                this.style.borderColor = '#d1d5db';
                this.style.backgroundColor = '#ffffff';
            }
        });
    </script>
</body>

</html>
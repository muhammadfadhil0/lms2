<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Email - Point</title>
    <?php require '../../assets/head.php'; ?>
</head>

<body>
    <?php
    session_start();
    
    // Cek apakah email sudah terverifikasi di step 1
    if (!isset($_SESSION['forgot_username_email'])) {
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
        
        // Sensor username (tampilkan 2 karakter pertama dan terakhir)
        if (strlen($username) <= 3) {
            $sensoredUsername = str_repeat('*', strlen($username));
        } else {
            $sensoredUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 3) . substr($username, -1);
        }
        
        // Sensor domain (tampilkan karakter pertama dan domain extension)
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
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Konfirmasi Email</h2>
            <p class="mt-1 text-center text-sm text-gray-600">Pastikan email ini benar sebelum mengirim kode OTP</p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'send_failed') {
                    echo 'Gagal mengirim kode OTP. Silakan coba lagi.';
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

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                            <path d="m3 7l9 6l9 -6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-blue-900">Email Ditemukan</h3>
                        <p class="text-sm text-blue-700 mt-1">Kami akan mengirim kode OTP ke:</p>
                        <p class="text-lg font-bold text-blue-900 mt-2 font-mono"><?php echo $sensoredEmail; ?></p>
                    </div>
                </div>
            </div>

            <form action="../logic/forgot-username-process.php" method="POST" class="space-y-6">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M12 9v4" />
                                <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                <path d="M12 16h.01" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Pastikan email benar</h4>
                            <p class="text-xs text-gray-600 mt-1">Kode OTP akan dikirim ke email ini. Jika email salah, gunakan tombol "Ubah Email" di bawah.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <button type="submit" name="send_otp" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M12 18h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v7.5" />
                            <path d="m3 6l9 6l9 -6" />
                            <path d="M15 18h6" />
                            <path d="M18 15l3 3l-3 3" />
                        </svg>
                        Kirim Kode OTP
                    </button>
                    
                    <a href="forgot-username-step1.php" class="flex w-full justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm/6 font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                        </svg>
                        Ubah Email
                    </a>
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
</body>

</html>
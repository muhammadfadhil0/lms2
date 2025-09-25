<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masukkan Kode OTP - Point</title>
    <?php require '../../assets/head.php'; ?>
    <style>
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            margin: 0 4px;
        }
        .otp-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .otp-input.filled {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
    </style>
</head>

<body>
    <?php
    session_start();
    
    // Cek apakah user sudah melalui step sebelumnya
    if (!isset($_SESSION['forgot_username_email']) || !isset($_SESSION['otp_sent'])) {
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
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Masukkan Kode OTP</h2>
            <p class="mt-1 text-center text-sm text-gray-600">Kode 6 digit telah dikirim ke <strong><?php echo $sensoredEmail; ?></strong></p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'invalid_otp') {
                    echo 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan periksa kembali atau minta kode baru.';
                } elseif ($_GET['error'] == 'empty_otp') {
                    echo 'Silakan masukkan kode OTP yang lengkap.';
                } elseif ($_GET['error'] == 'expired_otp') {
                    echo 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.';
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
            
            if (isset($_GET['success'])) {
                echo '<div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg relative" id="success-alert">';
                if ($_GET['success'] == 'otp_sent') {
                    echo 'Kode OTP baru telah dikirim ke email Anda.';
                }
                echo '<button type="button" class="absolute top-2 right-2 text-green-700 hover:text-green-900" onclick="document.getElementById(\'success-alert\').style.display=\'none\'">';
                echo '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">';
                echo '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
                echo '</svg>';
                echo '</button>';
                echo '</div>';
            }
            ?>

            <form action="../logic/forgot-username-process.php" method="POST" class="space-y-6" id="otpForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div>
                    <label class="block text-sm/6 font-medium text-gray-900 text-center mb-4">
                        <div class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M9 12l2 2l4 -4" />
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                            </svg>
                            Kode Verifikasi 6 Digit
                        </div>
                    </label>
                    
                    <div class="flex justify-center items-center space-x-2">
                        <input type="text" maxlength="1" class="otp-input" id="otp1" name="otp1" autocomplete="off">
                        <input type="text" maxlength="1" class="otp-input" id="otp2" name="otp2" autocomplete="off">
                        <input type="text" maxlength="1" class="otp-input" id="otp3" name="otp3" autocomplete="off">
                        <input type="text" maxlength="1" class="otp-input" id="otp4" name="otp4" autocomplete="off">
                        <input type="text" maxlength="1" class="otp-input" id="otp5" name="otp5" autocomplete="off">
                        <input type="text" maxlength="1" class="otp-input" id="otp6" name="otp6" autocomplete="off">
                    </div>
                    
                    <input type="hidden" name="otp_code" id="otp_code">
                </div>

                <div class="text-center">
                    <div id="timer" class="text-sm text-gray-600 mb-4"></div>
                    <button type="button" id="resendBtn" class="text-sm text-orange hover:text-indigo-500 disabled:text-gray-400 disabled:cursor-not-allowed" disabled>
                        Kirim ulang kode OTP
                    </button>
                </div>

                <div>
                    <button type="submit" name="verify_otp" id="submitBtn" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        Verifikasi Kode
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="forgot-username-step1.php" class="text-sm text-gray-600 hover:text-orange">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                        <path d="M9 14l-4 -4l4 -4" />
                        <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                    </svg>
                    Mulai dari awal
                </a>
            </div>
        </div>
    </div>

    <script>
        // OTP Input functionality
        const otpInputs = document.querySelectorAll('.otp-input');
        const submitBtn = document.getElementById('submitBtn');
        const otpCodeHidden = document.getElementById('otp_code');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 1) {
                    this.classList.add('filled');
                    // Move to next input
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }
                
                updateOTPCode();
                checkFormCompletion();
            });
            
            input.addEventListener('keydown', function(e) {
                // Handle backspace
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                    otpInputs[index - 1].value = '';
                    otpInputs[index - 1].classList.remove('filled');
                    updateOTPCode();
                    checkFormCompletion();
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                
                if (pastedData.length === 6) {
                    otpInputs.forEach((inp, idx) => {
                        if (idx < pastedData.length) {
                            inp.value = pastedData[idx];
                            inp.classList.add('filled');
                        }
                    });
                    updateOTPCode();
                    checkFormCompletion();
                }
            });
        });
        
        function updateOTPCode() {
            let code = '';
            otpInputs.forEach(input => {
                code += input.value;
            });
            otpCodeHidden.value = code;
        }
        
        function checkFormCompletion() {
            const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
            submitBtn.disabled = !allFilled;
        }
        
        // Countdown timer for resend
        let timeLeft = 60;
        const timerElement = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');
        
        function updateTimer() {
            if (timeLeft > 0) {
                timerElement.textContent = `Kirim ulang kode dalam ${timeLeft} detik`;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                timerElement.textContent = '';
                resendBtn.disabled = false;
                resendBtn.textContent = 'Kirim ulang kode OTP';
            }
        }
        
        updateTimer();
        
        // Resend OTP
        resendBtn.addEventListener('click', function() {
            window.location.href = '../logic/forgot-username-process.php?action=resend_otp';
        });
        
        // Auto focus first input
        otpInputs[0].focus();
    </script>
</body>

</html>
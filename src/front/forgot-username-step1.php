<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Username - Masukkan Email - Point</title>
    <?php require '../../assets/head.php'; ?>
</head>

<body>
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <img src="../../assets/img/logo.png" alt="Point" class="mx-auto h-20 w-auto text-center" />
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Lupa Username</h2>
            <p class="mt-1 text-center text-sm text-gray-600">Masukkan email yang Anda gunakan saat mendaftar</p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'email_not_found') {
                    echo 'Email tidak ditemukan dalam sistem. Pastikan email yang dimasukkan benar.';
                } elseif ($_GET['error'] == 'invalid_email') {
                    echo 'Format email tidak valid. Silakan masukkan email yang benar.';
                } elseif ($_GET['error'] == 'empty_email') {
                    echo 'Email tidak boleh kosong.';
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

            <form action="../logic/forgot-username-process.php" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm/6 font-medium text-gray-900">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                <path d="m3 7l9 6l9 -6" />
                            </svg>
                            Email Anda
                        </div>
                    </label>
                    <div class="mt-2">
                        <input id="email" type="email" name="email" required autocomplete="email" placeholder="contoh@email.com" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Masukkan alamat email yang sama seperti saat Anda mendaftar</p>
                </div>

                <div>
                    <button type="submit" name="verify_email" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-0.5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="M9 12l2 2l4 -4" />
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                        </svg>
                        Verifikasi Email
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
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<?php require 'src/component/modal-help-login.php'; ?>
<?php require 'src/component/modal-lupa-password.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Point</title>
    <?php require 'assets/head.php'; ?>
</head>

<!-- partial require -->


<body>
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full  sm:max-w-sm">
            <img src="assets/img/logo.png" alt="Your Company" class="mx-auto h-20 w-auto text-center" />
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-black">Halo, Selamat Datang <span class="hidden md:inline">di Point</span></h2>
            <p class="mt-1 text-center text-sm text-gray-600">Cari sumber belajarmu di sini, login untuk melanjutkan</p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <?php
            if (isset($_GET['success'])) {
                echo '<div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg relative" id="success-alert">';
                if ($_GET['success'] == 'registration') {
                    echo 'Akun berhasil dibuat! Silakan login dengan username dan password Anda.';
                }
                echo '<button type="button" class="absolute top-2 right-2 text-green-700 hover:text-green-900" onclick="document.getElementById(\'success-alert\').style.display=\'none\'">';
                echo '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">';
                echo '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
                echo '</svg>';
                echo '</button>';
                echo '</div>';
            }

            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg relative" id="error-alert">';
                if ($_GET['error'] == 'invalid') {
                    echo 'Username atau password salah!';
                } elseif ($_GET['error'] == 'empty') {
                    echo 'Harap isi semua field!';
                } elseif ($_GET['error'] == 'user_not_found') {
                    echo 'Username atau email tidak ditemukan!';
                } elseif ($_GET['error'] == 'wrong_password') {
                    echo 'Password yang Anda masukkan salah!';
                } elseif ($_GET['error'] == 'account_inactive') {
                    echo 'Akun Anda belum aktif atau diblokir. Hubungi administrator.';
                } elseif ($_GET['error'] == 'login_failed') {
                    echo 'Login gagal. Silakan coba lagi.';
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

            <form action="src/logic/login.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm/6 font-medium text-gray-900">Usernamemu</label>
                    <div class="mt-2">
                        <input id="username" type="text" name="username" required autocomplete="username" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium text-gray-900">Kata Sandi</label>

                    </div>
                    <div class="mt-2">
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                    </div>
                </div>

                <div>
                    <button type="submit" name="login" class="flex w-full justify-center rounded-md bg-orange px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Masuk</button>
                </div>
            </form>

            <p class="mt-10 text-center text-sm/6 text-gray-500">
                Terjadi masalah?
                <!-- nanti ini akan masuk ke partial/modal-login-support -->
                <button class="font-semibold text-orange hover:text-indigo-500" command="show-modal" commandfor="help-login">Silahkan hubungi IT Support</button>
            </p>

            <p class="mt-2 text-center text-sm/6 text-gray-500">Belum punya akun? <a href="src/front/register.php" class="font-semibold text-orange hover:text-indigo-500">Daftar sekarang</a></p>
        </div>
    </div>


</body>

</html>
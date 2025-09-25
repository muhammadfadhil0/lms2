<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/tabler-icons.min.css">
    <?php require 'assets/head.php'; ?>
    <title>Edupoint</title>
</head>

<body>
    <!-- Include this script tag or install `@tailwindplus/elements` via npm: -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->
    <div class="bg-white">
        <header class="absolute inset-x-0 top-0 z-50">
            <nav aria-label="Global" class="flex items-center justify-between p-6 lg:px-8">
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 p-1.5">
                        <span class="sr-only">Your Company</span>
                        <img src="assets/img/logo.png" alt="" class="h-8 w-auto" />
                    </a>
                </div>
                <div class="flex lg:hidden">
                    <button type="button" command="show-modal" commandfor="mobile-menu"
                        class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
                        <span class="sr-only">Open main menu</span>
                        <i class="ti ti-menu-2 text-xl"></i>
                    </button>
                </div>
                <div class="hidden lg:flex lg:gap-x-12">
                    <a href="#" class="text-sm/6 font-semibold text-gray-900">Bantuan</a>
                    <a href="#" class="text-sm/6 font-semibold text-gray-900">Tentang Kami</a>
                </div>
                <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                    <a href="login.php" class="text-sm/6 font-semibold text-gray-900">Masuk <span
                            aria-hidden="true">&rarr;</span></a>
                </div>
            </nav>
            <el-dialog>
                <dialog id="mobile-menu" class="backdrop:bg-transparent lg:hidden">
                    <div tabindex="0" class="fixed inset-0 focus:outline-none">
                        <el-dialog-panel
                            class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                            <div class="flex items-center justify-between">
                                <a href="#" class="-m-1.5 p-1.5">
                                    <span class="sr-only">Your Company</span>
                                    <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                                        alt="" class="h-8 w-auto" />
                                </a>
                                <button type="button" command="close" commandfor="mobile-menu"
                                    class="-m-2.5 rounded-md p-2.5 text-gray-700">
                                    <span class="sr-only">Close menu</span>
                                    <i class="ti ti-x text-xl"></i>
                                </button>
                            </div>
                            <div class="mt-6 flow-root">
                                <div class="-my-6 divide-y divide-gray-900/10">
                                    <div class="space-y-2 py-6">
                                        <a href="#"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Product</a>
                                        <a href="#"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Features</a>
                                        <a href="#"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Marketplace</a>
                                        <a href="#"
                                            class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Company</a>
                                    </div>
                                    <div class="py-6">
                                        <a href="#"
                                            class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Log
                                            in</a>
                                    </div>
                                </div>
                            </div>
                        </el-dialog-panel>
                    </div>
                </dialog>
            </el-dialog>
        </header>

        <div class="relative isolate px-6 pt-14 lg:px-8">
            <div aria-hidden="true"
                class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
                <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
                    class="relative left-[calc(50%-11rem)] aspect-1155/678 w-144.5 -translate-x-1/2 rotate-30 bg-linear-to-tr from-[#ff9500] to-[#ff6b35] opacity-30 sm:left-[calc(50%-30rem)] sm:w-288.75">
                </div>
            </div>
            <div class="mx-auto max-w-2xl py-30 sm:py-48 lg:py-25">
                <div class="hidden sm:mb-8 sm:flex sm:justify-center">
                    <div
                        class="relative rounded-full bg-orange-50 px-3 py-1 text-sm/6 text-gray-400 border border-white hover:ring-white/20">
                        Kami baru saja meluncurkan produk kami. <a href="#" class="font-semibold text-orange-500"><span
                                aria-hidden="true" class="absolute inset-0"></span>Lihat lebih lanjut <span
                                aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>
                <div class="text-center">
                    <h1 class="text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl">
                        Sederhanakan
                        Pembelajaran Daring Anda
                    </h1>
                    <p class="mt-8 text-lg font-medium text-pretty text-gray-600 sm:text-xl/8">Dapatkan dan rasakan
                        pengalaman belajar dan mengajar yang mudah dengan antarmuka mudah dan bantuan AI kami</p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="#"
                            class="rounded-md bg-orange px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Daftar
                            Sekarang</a>
                        <a href="#" class="text-sm/6 font-semibold text-gray-900">Pelajari <span
                                aria-hidden="true">→</span></a>
                    </div>
                </div>
            </div>
            <div aria-hidden="true"
                class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]">
                <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
                    class="relative left-[calc(50%+3rem)] aspect-1155/678 w-144.5 -translate-x-1/2 bg-linear-to-tr from-[#ff9500] to-[#ff6b35] opacity-30 sm:left-[calc(50%+36rem)] sm:w-288.75">
                </div>
            </div>
        </div>
    </div>

    <!-- product screenshot -->
    <div class="overflow-hidden bg-gray-50 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">

                <!-- Images section - appears first on mobile, second on desktop -->
                <div class="relative md:self-end lg:order-2">
                    <!-- Default Image -->
                    <img id="feature-image-default" width="2432" height="1442" src="assets/img/features/kelas-guru.png"
                        alt="Product screenshot"
                        class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-white/10 sm:w-228 md:-ml-4 lg:-ml-0 transition-opacity duration-300" />

                    <!-- Social Media Feature Image -->
                    <img id="feature-image-social-media" width="2432" height="1442"
                        src="assets/img/features/kelas-guru.png" alt="Kelas seperti grup sosial media"
                        class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-white/10 sm:w-228 md:-ml-4 lg:-ml-0 transition-opacity duration-300 absolute inset-0 opacity-0" />

                    <!-- Exam Management Feature Image -->
                    <img id="feature-image-exam-management" width="2432" height="1442"
                        src="assets/img/features/ai-buat-ujian.png" alt="Mengatur ujian tidak semenakutkan itu"
                        class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-white/10 sm:w-228 md:-ml-4 lg:-ml-0 transition-opacity duration-300 absolute inset-0 opacity-0" />

                    <!-- Pingo AI Feature Image -->
                    <img id="feature-image-pingo-ai" width="2432" height="1442" src="assets/img/features/pingo.png"
                        alt="Pingo AI siap membantu Anda"
                        class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-white/10 sm:w-228 md:-ml-4 lg:-ml-0 transition-opacity duration-300 absolute inset-0 opacity-0" />
                </div>

                <!-- Text content section - appears second on mobile, first on desktop -->
                <div class="lg:pt-4 lg:pr-8 lg:order-1">
                    <div class="lg:max-w-lg">
                        <h2 class="text-base/7 font-semibold text-orange-500">Kami hadir untuk</h2>
                        <p class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl">
                            Mempermudah Pembelajaran Anda</p>
                        <dl class="mt-10 max-w-xl space-y-1 text-base/7 text-gray-600 lg:max-w-none">
                            <div class="relative pl-9 cursor-pointer hover:bg-orange-50 p-3 rounded-lg transition-colors"
                                onclick="showImage('social-media')">
                                <dt class="inline font-semibold text-gray-900">
                                    <i class="ti ti-users absolute top-4 left-4 text-lg text-orange-600"></i>
                                    Kelas seperti sosial media
                                </dt>
                                <br>
                                <dd class="inline">Sukai, beri tugas, dan bagikan materi dengan mudah.</dd>
                            </div>
                            <div class="relative pl-9 cursor-pointer hover:bg-orange-50 p-3 rounded-lg transition-colors"
                                onclick="showImage('exam-management')">
                                <dt class="inline font-semibold text-gray-900">
                                    <i class="ti ti-clipboard-check absolute top-4 left-4 text-lg text-orange-600"></i>
                                    Ujian jadi mudah
                                </dt>
                                <br>
                                <dd class="inline">Jadwal fleksibel dan Pingo AI bantu buat soal tanpa ribet.</dd>
                            </div>
                            <div class="relative pl-9 cursor-pointer hover:bg-orange-50 p-3 rounded-lg transition-colors"
                                onclick="showImage('pingo-ai')">
                                <dt class="inline font-semibold text-gray-900">
                                    <i class="ti ti-sparkles absolute top-4 left-4 text-lg text-orange-600"></i>
                                    Pingo AI siap bantu
                                </dt>
                                <br>
                                <dd class="inline">Asisten personal untuk kelola pembelajaran lebih efisien.</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- keuntungan card grid -->
    <div class="bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-base/7 font-semibold text-orange-600">Keuntungan</h2>
                <p class="mt-2 text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">
                    Mengapa Memilih Edupoint?
                </p>
                <p class="mt-6 text-lg/8 text-gray-600">
                    Dapatkan pengalaman pembelajaran yang lebih baik dengan fitur-fitur unggulan kami
                </p>
            </div>
            
            <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <!-- Column 1: Mudah Digunakan -->
                <div class="text-center group">
                    <div class="relative overflow-hidden rounded-2xl bg-gray-50 p-8 group-hover:bg-orange-50 transition-colors duration-300">
                        <img src="assets/img/features/kelas-guru.png" alt="Interface yang mudah digunakan" 
                             class="mx-auto h-48 w-full object-cover rounded-lg shadow-lg group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">
                            Interface Intuitif
                        </h3>
                        <p class="mt-3 text-base text-gray-600 leading-6">
                            Desain yang mudah dipahami dan digunakan oleh siapa saja, dari pemula hingga ahli. Tidak perlu pelatihan khusus.
                        </p>
                    </div>
                </div>

                <!-- Column 2: AI Assistant -->
                <div class="text-center group">
                    <div class="relative overflow-hidden rounded-2xl bg-gray-50 p-8 group-hover:bg-orange-50 transition-colors duration-300">
                        <img src="assets/img/features/pingo.png" alt="Pingo AI Assistant" 
                             class="mx-auto h-48 w-full object-cover rounded-lg shadow-lg group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">
                            AI yang Cerdas
                        </h3>
                        <p class="mt-3 text-base text-gray-600 leading-6">
                            Pingo AI membantu membuat soal ujian, menganalisis hasil, dan memberikan insight pembelajaran yang berharga.
                        </p>
                    </div>
                </div>

                <!-- Column 3: Exam Management -->
                <div class="text-center group">
                    <div class="relative overflow-hidden rounded-2xl bg-gray-50 p-8 group-hover:bg-orange-50 transition-colors duration-300">
                        <img src="assets/img/features/ai-buat-ujian.png" alt="Manajemen ujian yang mudah" 
                             class="mx-auto h-48 w-full object-cover rounded-lg shadow-lg group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="mt-6">
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">
                            Ujian Fleksibel
                        </h3>
                        <p class="mt-3 text-base text-gray-600 leading-6">
                            Buat dan kelola ujian dengan mudah. Jadwal fleksibel, penilaian otomatis, dan laporan detail tersedia.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- harga -->

    <div class="relative isolate bg-gray-50 px-6 py-24 sm:py-32 lg:px-8">
        <div aria-hidden="true" class="absolute inset-x-0 -top-3 -z-10 transform-gpu overflow-hidden px-36 blur-3xl">
            <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
                class="mx-auto aspect-1155/678 w-288.75 bg-linear-to-tr from-[#ff9500] to-[#ff6b35] opacity-20"></div>
        </div>
        <div class="mx-auto max-w-4xl text-center">
            <h2 class="text-base/7 font-semibold text-orange-600">Harga</h2>
            <p class="mt-2 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-6xl">Pilih Paket
                Terbaik Untuk Anda</p>
        </div>
        <p class="mx-auto mt-6 max-w-2xl text-center text-lg font-medium text-pretty text-gray-600 sm:text-xl/8">Pilih
            paket terbaik Anda untuk meningkatkan pengalaman belajar dan mengajar Anda.</p>
        <div
            class="mx-auto mt-16 grid max-w-lg grid-cols-1 items-center gap-y-6 sm:mt-20 sm:gap-y-0 lg:max-w-4xl lg:grid-cols-2">
            <div
                class="rounded-3xl rounded-t-3xl bg-gray-900/2.5 p-8 ring-1 ring-gray-900/10 sm:mx-8 sm:rounded-b-none sm:p-10 lg:mx-0 lg:rounded-tr-none lg:rounded-bl-3xl">
                <h3 id="tier-hobby" class="text-base/7 font-semibold text-orange-600">Uji Coba</h3>
                <p class="mt-4 flex items-baseline gap-x-2">
                    <span class="text-5xl font-semibold tracking-tight text-gray-900">Gratis</span>
                    <span class="text-base text-gray-600">/bulan</span>
                </p>
                <p class="mt-6 text-base/7 text-gray-700">Paket untuk Anda yang ingin mencoba produk layanan kami.</p>
                <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-700 sm:mt-10">
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        5 Kelas aktif
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        5 Ujian Aktif
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Uji coba terbatas Pingo AI Exam Maker
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        <path
                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                            clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                        Uji coba terbatas Pingo AI Exam Analytics
                    </li>
                </ul>
                <a href="#" aria-describedby="tier-hobby"
                    class="mt-8 block rounded-md bg-gray-900/10 px-3.5 py-2.5 text-center text-sm font-semibold text-gray-900 inset-ring inset-ring-gray-900/5 hover:bg-gray-900/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900/75 sm:mt-10">
                    Daftar Sekarang</a>
            </div>
            <div class="relative rounded-3xl bg-gray-200 p-8 ring-1 ring-gray-900/10 sm:p-10">
                <h3 id="tier-enterprise" class="text-base/7 font-semibold text-orange-600">Pro</h3>
                <p class="mt-4 flex items-baseline gap-x-2">
                    <span class="text-4xl md:text-5xl font-semibold tracking-tight text-gray-900">Rp150.000</span>
                    <span class="text-base text-gray-600">/bulan</span>
                </p>
                <p class="mt-6 text-base/7 text-gray-700">Paket sempurna untuk mendukung proses belajar mengajar Anda.
                </p>
                <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-700 sm:mt-10">
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Buat kelas tanpa batas
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Buat ujian tanpa batas
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Akses lebih banyak Pingo AI Exam Maker
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Akses lebih banyak Pingo AI Exam Analytics
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        Akses Pingo AI Chat Assistant
                    </li>
                    <li class="flex gap-x-3">
                        <i class="ti ti-check h-6 w-5 flex-none text-orange-600"></i>
                        <path
                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                            clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                        Dukungan prioritas
                    </li>
                </ul>
                <a href="#" aria-describedby="tier-enterprise"
                    class="mt-8 block rounded-md bg-orange-500 px-3.5 py-2.5 text-center text-sm font-semibold text-white hover:bg-orange-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 sm:mt-10">Dapatkan
                    Pro</a>
            </div>
        </div>
    </div>


    <!-- akhir CTA -->
    <div class="bg-white">
        <div class="mx-auto max-w-7xl py-24 sm:px-6 sm:py-32 lg:px-8">
            <div
                class="relative isolate overflow-hidden bg-gray-50 px-6 pt-16 after:pointer-events-none after:absolute after:inset-0 after:inset-ring after:inset-ring-gray-900/10 sm:rounded-3xl sm:px-16 after:sm:rounded-3xl md:pt-24 lg:flex lg:gap-x-20 lg:px-24 lg:pt-0">
                <svg viewBox="0 0 1024 1024" aria-hidden="true"
                    class="absolute top-1/2 left-1/2 -z-10 size-256 -translate-y-1/2 mask-[radial-gradient(closest-side,white,transparent)] sm:left-full sm:-ml-80 lg:left-1/2 lg:ml-0 lg:-translate-x-1/2 lg:translate-y-0">
                    <circle r="512" cx="512" cy="512" fill="url(#759c1415-0410-454c-8f7c-9a820de03641)"
                        fill-opacity="0.7" />
                    <defs>
                        <radialGradient id="759c1415-0410-454c-8f7c-9a820de03641">
                            <stop stop-color="#ff9500" />
                            <stop offset="1" stop-color="#ff6b35" />
                        </radialGradient>
                    </defs>
                </svg>
                <div class="mx-auto max-w-md text-center lg:mx-0 lg:flex-auto lg:py-32 lg:text-left">
                    <h2 class="text-3xl font-semibold tracking-tight text-balance text-gray-900 sm:text-4xl">Kami
                        percaya bahwa teknologi harus menjadi solusi, bukan masalah baru.</h2>
                    <div class="mt-10 flex items-center justify-center gap-x-6 lg:justify-start">
                        <a href="#"
                            class="rounded-md bg-orange-500 px-3.5 py-2.5 text-sm font-semibold text-gray-900 inset-ring inset-ring-gray-900/5 hover:bg-orange-400 text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900">
                            Mulai sekarang</a>
                        <a href="#" class="text-sm/6 font-semibold text-gray-900 hover:text-gray-700">
                            Pelajari lebih lanjut
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
                <div class="relative mt-16 h-80 lg:mt-8">
                    <img width="1824" height="1080" src="assets/img/features/kelas-guru.png" alt="App screenshot"
                        class="absolute top-0 left-0 w-228 max-w-none rounded-md bg-gray-900/5 ring-1 ring-gray-900/10" />
                </div>
            </div>
        </div>
    </div>


    <script>
        // Feature Image Switcher
        let currentFeature = 'default';
        let autoRotateInterval;
        let currentIndex = 0;
        let isUserClicked = false;

        const features = ['social-media', 'exam-management', 'pingo-ai'];

        function showImage(featureId, isAutoRotate = false) {
            // Jika user klik, stop auto rotate sementara
            if (!isAutoRotate) {
                clearInterval(autoRotateInterval);
                isUserClicked = true;

                // Update index berdasarkan feature yang diklik
                currentIndex = features.indexOf(featureId);
                if (currentIndex === -1) currentIndex = 0;
            }

            // Hide all images
            document.getElementById('feature-image-default').style.opacity = '0';
            document.getElementById('feature-image-social-media').style.opacity = '0';
            document.getElementById('feature-image-exam-management').style.opacity = '0';
            document.getElementById('feature-image-pingo-ai').style.opacity = '0';

            // Show selected image
            document.getElementById('feature-image-' + featureId).style.opacity = '1';
            currentFeature = featureId;

            // Remove active state from all feature items
            document.querySelectorAll('.feature-item').forEach(item => {
                item.classList.remove('bg-orange-100', 'border-orange-300');
                item.classList.add('hover:bg-orange-50');
            });

            // Add active state to current feature
            const activeItem = document.querySelector(`[onclick="showImage('${featureId}')"]`);
            if (activeItem) {
                activeItem.classList.add('bg-orange-100', 'border-orange-300');
                activeItem.classList.remove('hover:bg-orange-50');
            }

            // Jika user klik, restart auto rotate setelah 5 detik
            if (!isAutoRotate && isUserClicked) {
                setTimeout(() => {
                    isUserClicked = false;
                    startAutoRotate();
                }, 5000);
            }
        }

        function startAutoRotate() {
            // Clear interval yang ada untuk menghindari duplikasi
            clearInterval(autoRotateInterval);

            autoRotateInterval = setInterval(() => {
                // Jika user sedang tidak berinteraksi, lanjutkan auto rotate
                if (!isUserClicked) {
                    showImage(features[currentIndex], true);
                    currentIndex = (currentIndex + 1) % features.length;
                }
            }, 3000); // Konsisten 3 detik
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Add feature-item class to all clickable items
            document.querySelectorAll('[onclick^="showImage"]').forEach(item => {
                item.classList.add('feature-item', 'border', 'border-transparent');
            });

            // Mulai dengan feature pertama setelah 1 detik
            setTimeout(() => {
                currentIndex = 0; // Mulai dari index 0 (social-media)
                showImage(features[currentIndex], true);
                currentIndex = 1; // Set ke next untuk putaran berikutnya

                // Mulai auto rotate setelah 3 detik
                setTimeout(() => {
                    startAutoRotate();
                }, 3000);
            }, 1000);
        });
    </script>

</body>

</html>
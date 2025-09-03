<!-- Sidebar Arsip Ujian -->
<div id="archiveSidebar" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out z-[999] border-l border-gray-200">
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="ti ti-archive mr-2 text-orange"></i>
                Arsip Ujian
            </h2>
            <button id="closeArchiveBtn" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="ti ti-x text-gray-500"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <div id="archiveLoader" class="hidden flex items-center justify-center py-8">
                <div class="flex items-center space-x-2 text-gray-500">
                    <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm">Memuat arsip...</span>
                </div>
            </div>

            <div id="archiveContent" class="space-y-3">
                <!-- Archived exams will be loaded here -->
            </div>

            <div id="archiveEmpty" class="hidden text-center py-8 text-gray-500">
                <i class="ti ti-archive text-4xl mb-3 text-gray-300"></i>
                <p class="text-sm">Belum ada ujian yang diarsipkan</p>
            </div>
        </div>
    </div>
</div>

<!-- Backdrop for archive sidebar -->
<div id="archiveBackdrop" class="hidden fixed inset-0 bg-gray-500/75 z-[998] transition-opacity"></div>

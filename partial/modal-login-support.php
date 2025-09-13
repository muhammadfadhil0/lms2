<!-- Modal Backdrop -->
<div id="login-support-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <!-- Background overlay -->
  <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
  
  <!-- Modal container -->
  <div class="flex min-h-full items-center justify-center p-4">
    <!-- Modal content -->
    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
      <!-- Modal header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Bantuan IT Support</h3>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Modal body -->
      <div class="p-6">
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-orange bg-opacity-10 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="flex-1">
            <p class="text-sm text-gray-600 mb-4">Jika Anda mengalami masalah login, silakan hubungi IT Support melalui:</p>
            <div class="space-y-2">
              <div class="flex items-center space-x-2">
                <span class="text-sm">📧</span>
                <span class="text-sm text-gray-900">support@point.ac.id</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm">📱</span>
                <span class="text-sm text-gray-900">+62 123-456-789</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm">⏰</span>
                <span class="text-sm text-gray-900">Jam Kerja: 08:00 - 17:00 WIB</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal footer -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
        <button onclick="closeModal()" class="px-4 py-2 bg-orange text-white rounded-md hover:bg-orange/80 transition-colors">
          Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    function openModalHelpLogin() {
        document.getElementById('login-support-modal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('login-support-modal').classList.add('hidden');
    }
</script>
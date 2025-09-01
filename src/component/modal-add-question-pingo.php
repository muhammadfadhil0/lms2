<dialog id="add-soal-ai" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent z-[9999]" style="display: none;" aria-hidden="true">
  <div class="modal-backdrop fixed inset-0 bg-black/20 transition-opacity duration-300 opacity-0"></div>

  <div tabindex="0" class="modal-container flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0 relative z-10">
    <div class="modal-panel relative transform overflow-hidden rounded-xl bg-white shadow-2xl border border-gray-200 transition-all duration-300 ease-out opacity-0 scale-95 translate-y-4 sm:my-8 sm:w-full sm:max-w-2xl sm:translate-y-0">

      <!-- Header -->
      <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
        <div class="flex items-center">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/20">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 text-white">
              <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
          <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
            <h3 id="dialog-title" class="text-lg font-semibold text-white">Bantuan PingoAI</h3>
            <div class="">
              <p class="text-base text-white">Pingo siap membantu Anda membuat soal dengan cepat dan mudah.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="px-6 py-6">
        <form class="space-y-6">

          <!-- Row 1: Jumlah Soal & Tipe Soal -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                  </svg>
                  Jumlah Soal
                </span>
              </label>
              <input type="number" min="1" max="20" value="5" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Tipe Soal
                </span>
              </label>
              <select id="tipe-soal" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                <option value="multiple_choice">Pilihan Ganda</option>
                <option value="essay">Essay</option>
              </select>
              <div id="auto-score-notice" class="text-left hidden mt-1 text-xs text-amber-700">
                <i class="ti ti-info-circle mr-1"></i>
                Penilaian otomatis aktif
              </div>
            </div>
          </div>

          <!-- Row 2: Pilihan Jawaban & Kesulitan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div id="pilihan-jawaban">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Pilihan Jawaban
                </span>
              </label>
              <select class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                <option value="4">A - D (4 pilihan)</option>
                <option value="5">A - E (5 pilihan)</option>
                <option value="6">A - F (6 pilihan)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  Tingkat Kesulitan
                </span>
              </label>
              <select id="tingkat-kesulitan" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                <option value="mudah">🟢 Mudah</option>
                <option value="sedang" selected>🟡 Sedang</option>
                <option value="sulit">🔴 Sulit</option>
              </select>
            </div>
          </div>

          <!-- Prompt AI Section -->
          <div class="">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-semibold text-gray-700">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                  Preview Prompt AI
                </span>
              </label>
              <button type="button" id="toggle-prompt-details" class="text-xs font-medium text-orange-600 hover:text-orange-700 transition-colors flex items-center">
                <span id="toggle-text">Lihat Selengkapnya</span>
                <svg id="toggle-icon" class="w-3 h-3 ml-1 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
            
            <div class="bg-white border border-orange-200 rounded-lg p-4">
              <div id="ai-prompt-container">
                <!-- Data Ujian - Always visible -->
                <div class="mb-3">
                  <h4 class="text-sm font-medium text-gray-700 mb-2">Data Ujian:</h4>
                  <div id="exam-data" class="text-xs text-gray-600 bg-gray-50 p-3 rounded border font-mono">
                    Loading exam data...
                  </div>
                </div>
                
                <!-- Collapsible content -->
                <div id="prompt-details" class="hidden transition-all duration-300 ease-in-out">
                  <div class="mb-3">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Prompt yang akan dikirim:</h4>
                    <textarea readonly rows="6" id="ai-prompt" class="w-full text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded p-3 resize-none focus:outline-none font-mono" placeholder="Prompt akan muncul otomatis berdasarkan pengaturan di atas...">Loading prompt...</textarea>
                  </div>
                  <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Expected JSON Response:</h4>
                    <textarea readonly rows="4" id="expected-response" class="w-full text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded p-3 resize-none focus:outline-none font-mono">Loading expected format...</textarea>
                  </div>
                </div>
              </div>
            </div>
            
            <p class="text-xs text-orange-600 mt-2 flex items-center">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              Prompt ini akan dikirim ke AI untuk menghasilkan soal sesuai kriteria Anda
            </p>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
        <button type="button" command="close" commandfor="dialog" class="inline-flex justify-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors">
          Batal
        </button>
        <button type="button" id="generate-questions-btn" class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg hover:from-orange-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 shadow-sm transition-all">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Generate Soal
        </button>
      </div>

    </div>
  </div>
</dialog>
// Manager for simple list modals: schedule, material, classmates
(function(){
    if (window.ListModalsManager) return; // avoid duplicate

    class ListModalsManager {
        constructor(kelasId){
            this.kelasId = kelasId;
            this.cache = { schedules:null, materials:null, classmates:null };
            this.bindCloseButtons();
        }

        bindCloseButtons(){
            const mapping = [
                { id:'schedule-list-modal', attr:'data-close-schedule-modal' },
                { id:'material-list-modal', attr:'data-close-material-modal' },
                { id:'classmates-list-modal', attr:'data-close-classmates-modal' }
            ];
            mapping.forEach(m => {
                const dlg = document.getElementById(m.id);
                if (!dlg) return;
                dlg.addEventListener('click', e => { if (e.target === dlg) this.close(dlg); });
                dlg.querySelectorAll(`[${m.attr}]`).forEach(btn => btn.addEventListener('click', ()=>this.close(dlg)));
            });
        }

        close(dlg){
            if (dlg.open) dlg.close();
            setTimeout(()=>dlg.classList.add('hidden'),100);
        }

        async open(type){
            const id = `${type}-list-modal`;
            const dlg = document.getElementById(id);
            if (!dlg) return;
            dlg.classList.remove('hidden');
            setTimeout(()=>{ if(!dlg.open && dlg.showModal) dlg.showModal(); },10);

            if (type === 'schedule') this.loadSchedules();
            else if (type === 'material') this.loadMaterials();
            else if (type === 'classmates') this.loadClassmates();
        }

        async loadSchedules(force=false){
            if (this.cache.schedules && !force) return this.renderSchedules(this.cache.schedules);
            this.setLoading('schedule');
            const res = await fetch(`../logic/get-kelas-files.php?kelas_id=${this.kelasId}&file_type=schedule`);
            const data = await res.json();
            const files = Array.isArray(data)?data:(data.files||[]);
            this.cache.schedules = files;
            this.renderSchedules(files);
        }

        async loadMaterials(force=false){
            if (this.cache.materials && !force) return this.renderMaterials(this.cache.materials);
            this.setLoading('material');
            const res = await fetch(`../logic/get-kelas-files.php?kelas_id=${this.kelasId}&file_type=material`);
            const data = await res.json();
            const files = Array.isArray(data)?data:(data.files||[]);
            this.cache.materials = files;
            this.renderMaterials(files);
        }

        async loadClassmates(force=false){
            if (this.cache.classmates && !force) return this.renderClassmates(this.cache.classmates);
            this.setLoading('classmates');
            const res = await fetch(`../logic/get-classmates.php?kelas_id=${this.kelasId}`);
            const data = await res.json();
            if(!data.success){
                this.setEmpty('classmates', data.message || 'Gagal memuat');
                return;
            }
            this.cache.classmates = data;
            this.renderClassmates(data);
        }

        setLoading(type){
            const map = {
                schedule:['schedule-loading','schedule-items','no-schedules'],
                material:['material-loading','material-items','no-materials'],
                classmates:['classmates-loading','classmates-items','no-classmates']
            };
            const ids = map[type];
            if(!ids) return;
            const [loading, items, empty] = ids.map(id=>document.getElementById(id));
            if (loading) loading.classList.remove('hidden');
            if (items) items.classList.add('hidden');
            if (empty) empty.classList.add('hidden');
        }

        setEmpty(type, msg){
            const mapEmpty = { schedule:'no-schedules', material:'no-materials', classmates:'no-classmates'};
            const id = mapEmpty[type];
            const el = document.getElementById(id);
            if (el){
                if (msg) {
                    const p = el.querySelector('p');
                    if (p) p.innerText = msg;
                }
                el.classList.remove('hidden');
            }
        }

        renderSchedules(files){
            const container = document.getElementById('schedule-items');
            const loading = document.getElementById('schedule-loading');
            const empty = document.getElementById('no-schedules');
            if (loading) loading.classList.add('hidden');
            if (!files.length){ empty?.classList.remove('hidden'); return; }
            if (container){
                container.innerHTML = files.map(f=>this.fileRow(f,'blue')).join('');
                container.classList.remove('hidden');
            }
        }

        renderMaterials(files){
            const container = document.getElementById('material-items');
            const loading = document.getElementById('material-loading');
            const empty = document.getElementById('no-materials');
            if (loading) loading.classList.add('hidden');
            if (!files.length){ empty?.classList.remove('hidden'); return; }
            if (container){
                container.innerHTML = files.map(f=>this.fileRow(f,'green')).join('');
                container.classList.remove('hidden');
            }
        }

        renderClassmates(data){
            const container = document.getElementById('classmates-items');
            const loading = document.getElementById('classmates-loading');
            const empty = document.getElementById('no-classmates');
            if (loading) loading.classList.add('hidden');
            const students = data.students || [];
            if (!students.length){ empty?.classList.remove('hidden'); }
            if (!container) return;

            const guruCard = `
                <div class="p-3 border border-orange-200 rounded-lg bg-orange-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-semibold">G</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">${data.guru.namaLengkap}</p>
                            <p class="text-xs text-gray-500 truncate">${data.guru.email}</p>
                            <span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded-full bg-orange-600 text-white">Guru</span>
                        </div>
                    </div>
                </div>`;

            const studentCards = students.map((s,i)=>`
                <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-xs font-medium">${(s.namaLengkap||'?').substring(0,2).toUpperCase()}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">${s.namaLengkap}</p>
                            <p class="text-xs text-gray-500 truncate">${s.email}</p>
                            <span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-700">Siswa</span>
                        </div>
                    </div>
                </div>`).join('');

            container.innerHTML = `<div class="space-y-2">${guruCard}${studentCards}</div>`;
            container.classList.remove('hidden');
        }

        fileRow(f,color){
            const ext = (f.file_extension||'').toLowerCase();
            const size = this.formatSize(f.file_size);
            const date = new Date(f.created_at).toLocaleDateString('id-ID');
            const icon = this.iconFor(ext,color);
            return `<div class="flex items-center p-3 bg-${color}-50 rounded-lg hover:bg-${color}-100 transition">
                <div class="w-10 h-10 bg-${color}-100 rounded-lg flex items-center justify-center mr-3">${icon}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${f.title}</p>
                    <p class="text-xs text-gray-500 truncate">${f.file_name} • ${size} • ${date}</p>
                </div>
                <div class="flex items-center space-x-2 ml-3">
                    <button onclick="downloadFile(${f.id})" class="text-${color}-600 hover:text-${color}-800" title="Download"><i class="ti ti-download text-sm"></i></button>
                </div>
            </div>`;
        }

        formatSize(bytes){
            if (!bytes) return '0 B';
            const k=1024; const units=['B','KB','MB','GB'];
            const i = Math.floor(Math.log(bytes)/Math.log(k));
            return (bytes/Math.pow(k,i)).toFixed(1)+' '+units[i];
        }

        iconFor(ext,color){
            const map={pdf:'ti-file-type-pdf',doc:'ti-file-type-doc',docx:'ti-file-type-docx',ppt:'ti-presentation',pptx:'ti-presentation',jpg:'ti-photo',jpeg:'ti-photo',png:'ti-photo'};
            const ic = map[ext]||'ti-file';
            return `<i class="ti ${ic} text-${color}-600"></i>`;
        }
    }

    window.ListModalsManager = ListModalsManager;
})();

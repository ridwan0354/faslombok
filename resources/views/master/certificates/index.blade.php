@extends('layouts.master')

@section('title', 'Cetak Sertifikat & Piagam Penghargaan - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{
    template: @json($template),
    activeField: 'name',
    sampleData: {
        name: 'Muhammad Rayyan Al-Farisi',
        award: 'JUARA 1',
        competition: 'Adzan',
        group: 'Ampenan',
        number: '001/FAS-LBK/XII/2026'
    },
    isDragging: false,
    dragField: null,
    showUploadModal: false,
    isSaving: false,
    startDrag(field, event) {
        event.preventDefault();
        this.isDragging = true;
        this.dragField = field;
        this.activeField = field;
    },
    onMouseMove(event) {
        if (!this.isDragging || !this.dragField) return;
        const canvas = this.$refs.certCanvas.getBoundingClientRect();
        let clientX = event.clientX;
        let clientY = event.clientY;
        if (event.touches && event.touches[0]) {
            clientX = event.touches[0].clientX;
            clientY = event.touches[0].clientY;
        }
        let x = ((clientX - canvas.left) / canvas.width) * 100;
        let y = ((clientY - canvas.top) / canvas.height) * 100;
        x = Math.max(5, Math.min(95, Math.round(x * 10) / 10));
        y = Math.max(5, Math.min(95, Math.round(y * 10) / 10));
        this.template.fields[this.dragField].x = x;
        this.template.fields[this.dragField].y = y;
    },
    stopDrag() {
        this.isDragging = false;
        this.dragField = null;
    },
    setPreviewRecipient(r) {
        this.sampleData.name = r.name;
        this.sampleData.award = r.award;
        this.sampleData.competition = r.competition;
        this.sampleData.group = r.group;
        this.sampleData.number = r.certificate_number;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}"
@mousemove="onMouseMove($event)"
@mouseup="stopDrag()"
@touchmove="onMouseMove($event)"
@touchend="stopDrag()">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-purple-950 tracking-tight flex items-center gap-2">
                <span>Cetak Piagam / Sertifikat</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">Format Kertas A4</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kustomisasi posisi teks penerima, juara, dan lomba langsung di atas gambar template sertifikat, lalu cetak satuan atau massal.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start">
            <button @click="showUploadModal = true" 
                    type="button"
                    class="px-3.5 py-2 rounded-xl bg-white border border-purple-200 text-purple-900 font-bold text-xs hover:bg-purple-50 transition-colors shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Ganti Gambar Background</span>
            </button>
            <a :href="'{{ route('master.certificates.print') }}?competition_id={{ $selectedCompId }}&type={{ $recipientType }}&search={{ urlencode($search) }}'" 
               target="_blank"
               class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-800 to-pink-700 text-white font-bold text-xs hover:from-purple-900 hover:to-pink-800 transition-colors shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Semua Sesuai Filter ({{ count($recipients) }} Piagam)</span>
            </a>
        </div>
    </div>

    <!-- Main Workspace Grid: Canvas Preview (Left/Top) + Positioning Controls (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Visual A4 Canvas Preview -->
        <div class="lg:col-span-8 space-y-3">
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-xs sm:text-sm font-extrabold text-slate-800">Visual Editor Posisi Teks (Drag & Drop Langsung)</h2>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">A4 Landscape • 297mm × 210mm</span>
                </div>

                <!-- Interactive A4 Canvas -->
                <div class="relative w-full rounded-2xl overflow-hidden border-2 border-slate-300 shadow-inner bg-slate-900 select-none cursor-crosshair"
                     style="aspect-ratio: 297 / 210;"
                     x-ref="certCanvas">

                    <!-- Background Image (Sample or User Uploaded) -->
                    <img :src="template.background_image" 
                         alt="Certificate Background" 
                         class="w-full h-full object-cover pointer-events-none absolute inset-0">

                    <!-- Dynamic Draggable Text Overlays -->

                    <!-- 1. Nomor Sertifikat -->
                    <template x-if="template.fields.number && template.fields.number.enabled">
                        <div class="absolute group transform -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing p-1 rounded transition-all"
                             :class="activeField === 'number' ? 'ring-2 ring-purple-600 bg-purple-500/20 shadow-md' : 'hover:ring-1 hover:ring-purple-400'"
                             :style="`left: ${template.fields.number.x}%; top: ${template.fields.number.y}%;`"
                             @mousedown="startDrag('number', $event)"
                             @touchstart="startDrag('number', $event)">
                            <div :style="`font-size: ${template.fields.number.font_size * 0.7}px; font-family: ${template.fields.number.font_family}; font-weight: ${template.fields.number.font_weight}; color: ${template.fields.number.color}; text-align: ${template.fields.number.align};`"
                                 class="whitespace-nowrap px-1.5 py-0.5 font-mono">
                                <span x-text="template.fields.number.prefix"></span><span x-text="sampleData.number"></span>
                            </div>
                            <span class="absolute -top-5 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-purple-900 text-[10px] text-white rounded font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                Geser No Sertifikat
                            </span>
                        </div>
                    </template>

                    <!-- 2. Nama Penerima -->
                    <template x-if="template.fields.name && template.fields.name.enabled">
                        <div class="absolute group transform -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing p-1.5 rounded transition-all"
                             :class="activeField === 'name' ? 'ring-2 ring-purple-600 bg-purple-500/20 shadow-lg' : 'hover:ring-1 hover:ring-purple-400'"
                             :style="`left: ${template.fields.name.x}%; top: ${template.fields.name.y}%;`"
                             @mousedown="startDrag('name', $event)"
                             @touchstart="startDrag('name', $event)">
                            <div :style="`font-size: ${template.fields.name.font_size * 0.7}px; font-family: ${template.fields.name.font_family}; font-weight: ${template.fields.name.font_weight}; color: ${template.fields.name.color}; text-align: ${template.fields.name.align};`"
                                 class="whitespace-nowrap px-3 py-1 drop-shadow-sm">
                                <span x-text="template.fields.name.prefix"></span><span x-text="sampleData.name"></span>
                            </div>
                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-purple-900 text-[10px] text-white rounded font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                Geser Nama Peserta
                            </span>
                        </div>
                    </template>

                    <!-- 3. Predikat / Juara -->
                    <template x-if="template.fields.award && template.fields.award.enabled">
                        <div class="absolute group transform -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing p-1 rounded transition-all"
                             :class="activeField === 'award' ? 'ring-2 ring-purple-600 bg-purple-500/20 shadow-md' : 'hover:ring-1 hover:ring-purple-400'"
                             :style="`left: ${template.fields.award.x}%; top: ${template.fields.award.y}%;`"
                             @mousedown="startDrag('award', $event)"
                             @touchstart="startDrag('award', $event)">
                            <div :style="`font-size: ${template.fields.award.font_size * 0.7}px; font-family: ${template.fields.award.font_family}; font-weight: ${template.fields.award.font_weight}; color: ${template.fields.award.color}; text-align: ${template.fields.award.align};`"
                                 class="whitespace-nowrap px-2 py-0.5 tracking-wider">
                                <span x-text="template.fields.award.prefix"></span><span x-text="sampleData.award"></span>
                            </div>
                            <span class="absolute -top-5 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-purple-900 text-[10px] text-white rounded font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                Geser Juara / Predikat
                            </span>
                        </div>
                    </template>

                    <!-- 4. Cabang Lomba -->
                    <template x-if="template.fields.competition && template.fields.competition.enabled">
                        <div class="absolute group transform -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing p-1 rounded transition-all"
                             :class="activeField === 'competition' ? 'ring-2 ring-purple-600 bg-purple-500/20 shadow-md' : 'hover:ring-1 hover:ring-purple-400'"
                             :style="`left: ${template.fields.competition.x}%; top: ${template.fields.competition.y}%;`"
                             @mousedown="startDrag('competition', $event)"
                             @touchstart="startDrag('competition', $event)">
                            <div :style="`font-size: ${template.fields.competition.font_size * 0.7}px; font-family: ${template.fields.competition.font_family}; font-weight: ${template.fields.competition.font_weight}; color: ${template.fields.competition.color}; text-align: ${template.fields.competition.align};`"
                                 class="whitespace-nowrap px-2 py-0.5">
                                <span x-text="template.fields.competition.prefix"></span><span x-text="sampleData.competition"></span>
                            </div>
                            <span class="absolute -top-5 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-purple-900 text-[10px] text-white rounded font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                Geser Cabang Lomba
                            </span>
                        </div>
                    </template>

                    <!-- 5. Kelompok / TPQ -->
                    <template x-if="template.fields.group && template.fields.group.enabled">
                        <div class="absolute group transform -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing p-1 rounded transition-all"
                             :class="activeField === 'group' ? 'ring-2 ring-purple-600 bg-purple-500/20 shadow-md' : 'hover:ring-1 hover:ring-purple-400'"
                             :style="`left: ${template.fields.group.x}%; top: ${template.fields.group.y}%;`"
                             @mousedown="startDrag('group', $event)"
                             @touchstart="startDrag('group', $event)">
                            <div :style="`font-size: ${template.fields.group.font_size * 0.7}px; font-family: ${template.fields.group.font_family}; font-weight: ${template.fields.group.font_weight}; color: ${template.fields.group.color}; text-align: ${template.fields.group.align};`"
                                 class="whitespace-nowrap px-2 py-0.5">
                                <span x-text="template.fields.group.prefix"></span><span x-text="sampleData.group"></span>
                            </div>
                            <span class="absolute -top-5 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-purple-900 text-[10px] text-white rounded font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                Geser Kelompok
                            </span>
                        </div>
                    </template>

                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Tips: Klik dan tahan teks di atas untuk menggeser posisinya secara bebas.</span>
                    </span>
                    <form action="{{ route('master.certificates.template.reset') }}" method="POST" onsubmit="return confirm('Kembalikan template ke desain contoh bawaan?')">
                        @csrf
                        <button type="submit" class="text-rose-600 hover:text-rose-700 font-bold hover:underline">
                            Reset ke Contoh Bawaan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Field Selector & Fine-Tune Controls -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-black text-slate-900">Pengaturan Area Teks</h3>
                    <p class="text-xs text-slate-500">Pilih komponen teks untuk menyesuaikan gaya & letak koordinat.</p>
                </div>

                <!-- Field Tabs -->
                <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" @click="activeField = 'name'" 
                            :class="activeField === 'name' ? 'bg-purple-800 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-2.5 py-2 rounded-xl text-xs transition-colors flex items-center justify-between">
                        <span>Nama Peserta</span>
                        <span class="w-2 h-2 rounded-full" :class="template.fields.name.enabled ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                    </button>
                    <button type="button" @click="activeField = 'award'" 
                            :class="activeField === 'award' ? 'bg-purple-800 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-2.5 py-2 rounded-xl text-xs transition-colors flex items-center justify-between">
                        <span>Predikat / Juara</span>
                        <span class="w-2 h-2 rounded-full" :class="template.fields.award.enabled ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                    </button>
                    <button type="button" @click="activeField = 'competition'" 
                            :class="activeField === 'competition' ? 'bg-purple-800 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-2.5 py-2 rounded-xl text-xs transition-colors flex items-center justify-between">
                        <span>Cabang Lomba</span>
                        <span class="w-2 h-2 rounded-full" :class="template.fields.competition.enabled ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                    </button>
                    <button type="button" @click="activeField = 'group'" 
                            :class="activeField === 'group' ? 'bg-purple-800 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-2.5 py-2 rounded-xl text-xs transition-colors flex items-center justify-between">
                        <span>Kelompok / TPQ</span>
                        <span class="w-2 h-2 rounded-full" :class="template.fields.group.enabled ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                    </button>
                    <button type="button" @click="activeField = 'number'" 
                            :class="activeField === 'number' ? 'bg-purple-800 text-white font-bold col-span-2' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 col-span-2'"
                            class="px-2.5 py-2 rounded-xl text-xs transition-colors flex items-center justify-between">
                        <span>Nomor Sertifikat</span>
                        <span class="w-2 h-2 rounded-full" :class="template.fields.number.enabled ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                    </button>
                </div>

                <!-- Active Field Controls Panel -->
                <template x-if="template.fields[activeField]">
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700" x-text="template.fields[activeField].label"></span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="template.fields[activeField].enabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                                <span class="ml-2 text-xs font-medium text-slate-600">Aktif</span>
                            </label>
                        </div>

                        <!-- Prefix Input -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Teks Tambahan / Awalan (Prefix)</label>
                            <input type="text" x-model="template.fields[activeField].prefix" 
                                   class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                        </div>

                        <!-- Coordinate Sliders -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                                    <span>Posisi X</span>
                                    <span class="font-mono font-bold" x-text="template.fields[activeField].x + '%'"></span>
                                </div>
                                <input type="range" min="5" max="95" step="0.5" x-model.number="template.fields[activeField].x" 
                                       class="w-full accent-purple-600">
                            </div>
                            <div>
                                <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                                    <span>Posisi Y</span>
                                    <span class="font-mono font-bold" x-text="template.fields[activeField].y + '%'"></span>
                                </div>
                                <input type="range" min="5" max="95" step="0.5" x-model.number="template.fields[activeField].y" 
                                       class="w-full accent-purple-600">
                            </div>
                        </div>

                        <!-- Font Size & Color -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                                    <span>Ukuran Font</span>
                                    <span class="font-mono font-bold" x-text="template.fields[activeField].font_size + 'px'"></span>
                                </div>
                                <input type="range" min="10" max="64" step="1" x-model.number="template.fields[activeField].font_size" 
                                       class="w-full accent-purple-600">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Warna Font</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" x-model="template.fields[activeField].color" 
                                           class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200 p-0.5">
                                    <input type="text" x-model="template.fields[activeField].color" 
                                           class="w-full px-2 py-1 text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-lg">
                                </div>
                            </div>
                        </div>

                        <!-- Font Style & Family -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Jenis Huruf</label>
                                <select x-model="template.fields[activeField].font_family" 
                                        class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                                    <option value="'Times New Roman', serif">Times New Roman (Serif)</option>
                                    <option value="'Georgia', serif">Georgia (Klasik)</option>
                                    <option value="'Arial', sans-serif">Arial (Modern)</option>
                                    <option value="'Courier New', monospace">Courier (Monospace)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ketebalan</label>
                                <select x-model="template.fields[activeField].font_weight" 
                                        class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                                    <option value="bold">Tebal (Bold)</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Save Template Button Form -->
                <form action="{{ route('master.certificates.template.save') }}" method="POST" class="pt-2">
                    @csrf
                    <input type="hidden" name="template[background_image]" :value="template.background_image">
                    <input type="hidden" name="template[paper_size]" value="a4">
                    <input type="hidden" name="template[orientation]" value="landscape">
                    
                    <!-- Hidden inputs for fields -->
                    <template x-for="(f, key) in template.fields" :key="key">
                        <div>
                            <input type="hidden" :name="`template[fields][${key}][label]`" :value="f.label">
                            <input type="hidden" :name="`template[fields][${key}][x]`" :value="f.x">
                            <input type="hidden" :name="`template[fields][${key}][y]`" :value="f.y">
                            <input type="hidden" :name="`template[fields][${key}][font_size]`" :value="f.font_size">
                            <input type="hidden" :name="`template[fields][${key}][font_family]`" :value="f.font_family">
                            <input type="hidden" :name="`template[fields][${key}][font_weight]`" :value="f.font_weight">
                            <input type="hidden" :name="`template[fields][${key}][color]`" :value="f.color">
                            <input type="hidden" :name="`template[fields][${key}][align]`" :value="f.align">
                            <input type="hidden" :name="`template[fields][${key}][prefix]`" :value="f.prefix || ''">
                            <input type="hidden" :name="`template[fields][${key}][enabled]`" :value="f.enabled ? 1 : 0">
                        </div>
                    </template>

                    <button type="submit" 
                            class="w-full py-2.5 bg-purple-900 hover:bg-purple-950 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center justify-center space-x-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan Pengaturan Template & Area</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Filter & Daftar Penerima Piagam -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-purple-950">Daftar Penerima Piagam & Sertifikat</h3>
                <p class="text-xs text-slate-500">Pilih lomba atau kategori untuk mencetak piagam secara massal atau satuan.</p>
            </div>
            
            <!-- Filters Form -->
            <form action="{{ route('master.certificates') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <select name="competition_id" onchange="this.form.submit()"
                        class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                    <option value="">-- Semua Cabang Lomba --</option>
                    @foreach($competitions as $c)
                    <option value="{{ $c->id }}" {{ $selectedCompId == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>

                <select name="type" onchange="this.form.submit()"
                        class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                    <option value="all" {{ $recipientType === 'all' ? 'selected' : '' }}>Semua Penerima</option>
                    <option value="winners" {{ $recipientType === 'winners' ? 'selected' : '' }}>Hanya Juara (1, 2, 3, Harapan)</option>
                    <option value="participants" {{ $recipientType === 'participants' ? 'selected' : '' }}>Hanya Partisipasi</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / kelompok..." 
                           class="pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </form>
        </div>

        <!-- Recipients Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">No. Piagam</th>
                        <th class="py-3 px-4">Nama Penerima</th>
                        <th class="py-3 px-4">Kelompok / TPQ</th>
                        <th class="py-3 px-4">Cabang Lomba</th>
                        <th class="py-3 px-4 text-center">Predikat / Juara</th>
                        <th class="py-3 px-4 text-right">Aksi Cetak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recipients as $idx => $r)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-500 text-xs">
                            {{ $r['certificate_number'] }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-900 block">{{ $r['name'] }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $r['code'] }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-700">
                            {{ $r['group'] }}
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-800">
                            {{ $r['competition'] }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($r['is_winner'])
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    {{ $r['award'] }}
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    Peserta
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                            <button type="button" 
                                    @click="setPreviewRecipient(@json($r))"
                                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-900 font-bold text-xs transition-colors">
                                Preview di Canvas ↑
                            </button>
                            <a href="{{ route('master.certificates.print', ['entry_id' => $r['entry_id'], 'participant_id' => $r['participant_id']]) }}" 
                               target="_blank"
                               class="px-3 py-1.5 rounded-lg bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs shadow-xs transition-colors inline-block">
                                Cetak Satuan ↗
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                            Tidak ada data peserta atau penerima piagam yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Upload Background Image -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showUploadModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Upload Gambar Background Sertifikat</h3>
                <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.certificates.upload.background') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-purple-500 transition-colors">
                    <svg class="w-10 h-10 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <label class="block text-xs font-bold text-purple-900 cursor-pointer hover:underline mb-1">
                        <span>Pilih Gambar dari Komputer</span>
                        <input type="file" name="background" required accept="image/jpeg,image/png,image/jpg" class="sr-only">
                    </label>
                    <p class="text-[11px] text-slate-400">Format JPG atau PNG (Disarankan rasio A4 Landscape: 297mm × 210mm, min. 1754×1240 px, maks 10MB)</p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-800 hover:bg-purple-900 text-white font-bold text-xs rounded-xl shadow-xs">
                        Upload & Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

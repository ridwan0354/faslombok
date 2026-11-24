@extends('layouts.master')

@section('title', 'Kelola Juri & Hak Akses - FAS Lombok 2026')

@section('content')
<script>
function judgesManager() {
    return {
        showAddJudgeModal: false,
        showEditJudgeModal: false,
        showAssignModal: false,
        selectedJudgeId: '',
        selectedJudgeName: '',
        searchQuery: '',
        filterCompId: '',
        copiedJudgeId: null,
        async copyAccessMessage(judgeId, message) {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(message);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = message;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                this.copiedJudgeId = judgeId;
                setTimeout(() => {
                    if (this.copiedJudgeId === judgeId) {
                        this.copiedJudgeId = null;
                    }
                }, 2500);
            } catch (e) {
                window.prompt('Salin pesan akses juri berikut secara manual:', message);
            }
        },
        editJudge: {
            id: '',
            name: '',
            phone: '',
            origPhone: '',
            password: '',
            syncPasswordWithPhone: false,
            status: 'active'
        },
        openEditJudge(j) {
            this.editJudge = {
                id: j.id,
                name: j.name,
                phone: j.phone,
                origPhone: j.phone,
                password: '',
                syncPasswordWithPhone: false,
                status: j.status || 'active'
            };
            this.showEditJudgeModal = true;
        },
        openAssignModal(id, name) {
            this.selectedJudgeId = id;
            this.selectedJudgeName = name;
            this.showAssignModal = true;
        },
        matchesFilter(name, phone, compIds) {
            const q = this.searchQuery.trim().toLowerCase();
            const matchText = !q || name.toLowerCase().includes(q) || phone.toLowerCase().includes(q);
            const matchComp = !this.filterCompId || compIds.includes(parseInt(this.filterCompId));
            return matchText && matchComp;
        }
    };
}
</script>

<div class="space-y-6" x-data="judgesManager()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-purple-950 tracking-tight">Dewan Juri & Hak Akses ({{ $judges->count() }} Juri)</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola akun login juri (<span class="font-semibold text-purple-900">Username & Password bawaan = Nomor HP masing-masing</span>), edit/hapus juri, dan atur penugasan per cabang lomba.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button @click="showAddJudgeModal = true"
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-800 to-pink-700 text-white font-bold text-xs hover:from-purple-900 hover:to-pink-800 transition-colors shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Tambah Akun Juri</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="sm:col-span-2 relative">
            <input type="text" x-model="searchQuery" placeholder="Cari nama penguji / dewan juri atau nomor HP..."
                   class="w-full pl-9 pr-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 focus:outline-none">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div>
            <select x-model="filterCompId" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                <option value="">-- Semua Cabang Lomba ({{ $competitions->count() }}) --</option>
                @foreach($competitions as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Judges List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($judges as $j)
        @php
            $compIds = $j->judgeAssignments->pluck('competition_id')->values()->all();
            $compNames = $j->judgeAssignments
                ->map(fn($ja) => $ja->competition?->name)
                ->filter()
                ->implode(', ');
            $magicUrl = $j->judgeMagicLoginUrl();
            $loginUrl = route('login');
            $accessMessage = "Assalamu'alaikum Wr. Wb.\n"
                . "Berikut akses Portal Penilaian Dewan Juri *Festival Anak Sholeh & Bazar Remaja Lombok 2026* (20 Desember 2026):\n\n"
                . "Nama Juri : {$j->name}\n"
                . "Cabang Lomba : " . ($compNames ?: 'Belum ditugaskan') . "\n\n"
                . "*Link Masuk Otomatis (Klik langsung masuk tanpa login):*\n"
                . "{$magicUrl}\n\n"
                . "*Akses Login Manual:*\n"
                . "Link Login : {$loginUrl}\n"
                . "Username : {$j->phone}\n"
                . "Password : {$j->phone} (sesuai No. HP / password yang diatur panitia)\n\n"
                . "Terima kasih & selamat bertugas.";
            $judgePayload = [
                'id' => $j->id,
                'name' => $j->name,
                'phone' => $j->phone,
                'status' => $j->status,
            ];
        @endphp
        <div x-show="matchesFilter(@js($j->name), @js($j->phone), @js($compIds))"
             class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">{{ $j->name }}</h3>
                        <p class="text-xs text-purple-800 font-mono font-bold mt-0.5">{{ $j->phone }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $j->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $j->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">
                        Cabang Lomba Ditugaskan ({{ $j->judgeAssignments->count() }}):
                    </span>
                    <div class="space-y-1.5 max-h-36 overflow-y-auto">
                        @forelse($j->judgeAssignments as $ja)
                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-slate-800">{{ $ja->competition ? $ja->competition->name : '-' }}</span>
                                <div class="flex space-x-1 mt-0.5">
                                    <span class="text-[9px] font-semibold {{ $ja->can_score ? 'text-emerald-700' : 'text-slate-500' }}">
                                        {{ $ja->can_score ? '• Menilai' : '• View' }}
                                    </span>
                                    @if($ja->can_view_peer_scores)
                                        <span class="text-[9px] font-semibold text-indigo-700">• Peer View</span>
                                    @endif
                                </div>
                            </div>
                            <form action="{{ route('master.judges.assign.revoke', $ja->id) }}" method="POST" onsubmit="return confirm('Cabut tugas juri ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] text-rose-600 font-bold hover:text-rose-800">
                                    Cabut
                                </button>
                            </form>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400">Belum ada tugas perlombaan.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                <button type="button"
                        @click="copyAccessMessage({{ $j->id }}, @js($accessMessage))"
                        :class="copiedJudgeId === {{ $j->id }} ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200'"
                        class="w-full py-2 font-bold text-xs rounded-xl border transition-colors flex items-center justify-center space-x-1.5">
                    <svg x-show="copiedJudgeId !== {{ $j->id }}" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg x-show="copiedJudgeId === {{ $j->id }}" x-cloak class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="copiedJudgeId === {{ $j->id }} ? 'Link & Pesan Akses Tersalin!' : 'Salin Link Akses'"></span>
                </button>
                <button type="button" @click="openAssignModal('{{ $j->id }}', @js($j->name))"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                    + Tugaskan ke Lomba
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openEditJudge(@js($judgePayload))"
                            class="flex-1 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-800 font-bold text-xs rounded-xl border border-purple-200 transition-colors">
                        Edit Juri
                    </button>
                    <form action="{{ route('master.judges.destroy', $j->id) }}" method="POST" data-confirm="Yakin ingin menghapus akun juri {{ $j->name }}?" onsubmit="return confirm(this.dataset.confirm)" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Tambah Akun Juri -->
    <div x-show="showAddJudgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showAddJudgeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Juri Baru</h3>
                <button @click="showAddJudgeModal = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.judges.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Juri</label>
                    <input type="text" name="name" required placeholder="Contoh: Ibu Wida (Ampenan)" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor HP (Username & Password Default)</label>
                    <input type="tel" name="phone" required placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password Kustom (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan agar password otomatis sama dengan Nomor HP" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                    <p class="text-[11px] text-slate-400 mt-1">Jika dikosongkan, juri langsung bisa login dengan Username & Password = Nomor HP.</p>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddJudgeModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-800 hover:bg-purple-900 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Akun Juri</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Akun Juri -->
    <div x-show="showEditJudgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showEditJudgeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Data Akun Juri</h3>
                <button @click="showEditJudgeModal = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/master/juri/' + editJudge.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Juri</label>
                    <input type="text" name="name" x-model="editJudge.name" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor HP / ID Login</label>
                    <input type="tel" name="phone" x-model="editJudge.phone"
                           @input="if (editJudge.phone !== editJudge.origPhone) editJudge.syncPasswordWithPhone = true"
                           @change="if (editJudge.phone !== editJudge.origPhone) editJudge.syncPasswordWithPhone = true"
                           required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-mono">
                </div>

                <div class="p-3 bg-purple-50/70 border border-purple-200 rounded-2xl space-y-2">
                    <label class="flex items-center space-x-2 text-xs font-bold text-purple-950 cursor-pointer">
                        <input type="checkbox" name="sync_password_phone" value="1" x-model="editJudge.syncPasswordWithPhone" class="rounded text-purple-700 focus:ring-purple-600">
                        <span>Samakan password dengan Nomor HP di atas</span>
                    </label>
                    <p class="text-[11px] text-purple-700">Saat Nomor HP diganti, opsi ini otomatis tercentang agar password ikut berubah ke Nomor HP baru.</p>
                </div>

                <div x-show="!editJudge.syncPasswordWithPhone">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password Baru Kustom (Opsional)</label>
                    <input type="password" name="password" x-model="editJudge.password" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status Akun</label>
                    <select name="status" x-model="editJudge.status" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                        <option value="active">Aktif (Dapat Login)</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showEditJudgeModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-800 hover:bg-purple-900 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tugaskan Juri -->
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showAssignModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Tugaskan Juri ke Cabang Lomba</h3>
                    <p class="text-xs text-purple-700 font-bold" x-text="selectedJudgeName"></p>
                </div>
                <button @click="showAssignModal = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.judges.assign') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="user_id" :value="selectedJudgeId">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Cabang Lomba</label>
                    <select name="competition_id" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                        <option value="">-- Pilih Lomba --</option>
                        @foreach($competitions as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->format === 'team' ? 'Beregu' : 'Individu' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 text-xs text-slate-700 font-medium bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_score" value="1" checked class="rounded text-purple-600">
                        <span>Hak Menilai (Can Score)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_view_peer_scores" value="1" class="rounded text-purple-600">
                        <span>Boleh Melihat Nilai Juri Lain (Peer Visibility)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="required_for_completion" value="1" checked class="rounded text-purple-600">
                        <span>Juri Wajib (Required for final completion)</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-800 hover:bg-purple-900 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

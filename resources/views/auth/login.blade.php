@extends('layouts.app')

@section('title', 'Login Panitia & Juri - Festival Anak Sholeh Lombok 2026')

@section('body')
<div class="min-h-screen bg-gradient-to-br from-emerald-950 via-emerald-900 to-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 px-4" 
     x-data="{
        phone: '{{ old('phone', '') }}',
        password: '',
        fillUser(p, pw) {
            this.phone = p;
            this.password = pw;
        }
     }">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-400 to-emerald-400 flex items-center justify-center text-emerald-950 font-black text-2xl shadow-xl shadow-emerald-950/50 mb-4">
            FAS
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Festival Anak Sholeh
        </h2>
        <p class="text-sm text-emerald-300 font-medium mt-1">Lombok 2026 • Portal Masuk Panitia & Juri</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-2xl rounded-3xl sm:px-10 border border-emerald-800/20">
            @if(session('success'))
                <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 text-emerald-900 text-xs font-semibold border border-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 text-rose-900 text-xs font-semibold border border-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 text-rose-900 text-xs font-semibold border border-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Nomor HP (WhatsApp)
                    </label>
                    <div class="mt-1 relative">
                        <input id="phone" name="phone" type="tel" required 
                               x-model="phone"
                               placeholder="Contoh: 081234567890" 
                               class="appearance-none block w-full px-3.5 py-3 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 sm:text-sm font-medium">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required 
                               x-model="password"
                               placeholder="••••••••" 
                               class="appearance-none block w-full px-3.5 py-3 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 sm:text-sm font-medium">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600 font-medium cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 rounded">
                        <span class="ml-2">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-emerald-700/30 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all active:scale-[0.99]">
                        Masuk ke Aplikasi
                    </button>
                </div>
            </form>

            <!-- Quick Demo Accounts Filler for Testing -->
            <div class="mt-6 pt-6 border-t border-slate-200">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center mb-2.5">
                    Akun Uji Coba Cepat (Klik untuk isi)
                </p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" @click="fillUser('081234567890', 'password123')"
                            class="p-2 text-left bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-emerald-950 transition-colors">
                        <span class="font-bold block text-[11px]">Master Admin</span>
                        <span class="text-[10px] text-emerald-700">081234567890</span>
                    </button>
                    <button type="button" @click="fillUser('081111111111', 'password123')"
                            class="p-2 text-left bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-900 transition-colors">
                        <span class="font-bold block text-[11px]">Ust. Ahmad (Juri Adzan)</span>
                        <span class="text-[10px] text-slate-600">081111111111</span>
                    </button>
                    <button type="button" @click="fillUser('082222222222', 'password123')"
                            class="p-2 text-left bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-900 transition-colors">
                        <span class="font-bold block text-[11px]">Ust. Fauzi (Juri Adzan/Surat)</span>
                        <span class="text-[10px] text-slate-600">082222222222</span>
                    </button>
                    <button type="button" @click="fillUser('083333333333', 'password123')"
                            class="p-2 text-left bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-900 transition-colors">
                        <span class="font-bold block text-[11px]">Usth. Siti (Juri Mewarnai)</span>
                        <span class="text-[10px] text-slate-600">083333333333</span>
                    </button>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                    ← Kembali ke Halaman Publik
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

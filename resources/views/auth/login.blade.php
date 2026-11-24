@extends('layouts.app')

@section('title', 'Login Panitia & Juri - Festival Anak Sholeh & Bazar Remaja 2026')

@section('body')
<div class="min-h-screen bg-gradient-to-br from-purple-950 via-purple-900 to-pink-950 flex flex-col justify-center py-8 sm:px-6 lg:px-8 px-4" 
     x-data="{
        phone: '{{ old('phone', '') }}',
        password: ''
     }">
    <div class="max-w-4xl w-full mx-auto">
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border-2 border-amber-300/40 overflow-hidden grid grid-cols-1 lg:grid-cols-12">
            
            <!-- Left Column: Poster & Event Branding (Desktop) -->
            <div class="lg:col-span-5 bg-gradient-to-b from-purple-950 to-pink-950 p-6 flex flex-col items-center justify-between text-white text-center relative overflow-hidden">
                <div class="w-full space-y-3 relative z-10">
                    <!-- Badges -->
                    <div class="flex items-center justify-center space-x-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-purple-950">
                            LDII
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-white/20 text-white">
                            PPG SAK JAGAT RAYA
                        </span>
                    </div>

                    <!-- Poster Artwork Container -->
                    <div class="rounded-2xl overflow-hidden border-2 border-amber-300/60 shadow-xl max-w-[240px] mx-auto">
                        <img src="/images/poster.jpg" 
                             alt="Festival Anak Sholeh dan Bazar Remaja" 
                             class="w-full h-auto object-cover">
                    </div>

                    <div>
                        <h2 class="text-base sm:text-lg font-black text-amber-300 leading-tight">
                            Festival Anak Sholeh
                        </h2>
                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-pink-600 text-white font-bold text-xs mt-0.5">
                            dan Bazar Remaja
                        </span>
                        <p class="text-[11px] text-purple-200 mt-1 italic">
                            "Membangun Karakter, Menumbuhkan Teladan, Mewujudkan Generasi Masa Depan"
                        </p>
                    </div>
                </div>

                <div class="pt-4 text-[10px] text-purple-300 border-t border-purple-800/60 w-full mt-4">
                    <span>Masjid Uswatun Hasanah • Lombok 2026</span>
                </div>
            </div>

            <!-- Right Column: Login Form -->
            <div class="lg:col-span-7 p-6 sm:p-10 flex flex-col justify-center">
                <div>
                    <div class="mb-6">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-pink-700 block">
                            Portal Akses Petugas
                        </span>
                        <h3 class="text-2xl font-black text-purple-950 tracking-tight">
                            Masuk Panitia & Juri
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Untuk <strong>Dewan Juri</strong>: gunakan <strong>Nomor HP</strong> terdaftar sebagai username dan password.<br>
                            Untuk <strong>Master Admin</strong>: gunakan Nomor HP dan kata sandi panitia.
                        </p>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 p-3 rounded-xl bg-purple-50 text-purple-900 text-xs font-semibold border border-purple-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-900 text-xs font-semibold border border-rose-200">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-900 text-xs font-semibold border border-rose-200">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Nomor HP (WhatsApp)
                            </label>
                            <div class="mt-1">
                                <input id="phone" name="phone" type="tel" required 
                                       x-model="phone"
                                       placeholder="Contoh: 081234567890" 
                                       class="block w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-600 focus:outline-none font-medium">
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
                                       class="block w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-600 focus:outline-none font-medium">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <label class="flex items-center text-slate-600 font-medium cursor-pointer">
                                <input type="checkbox" name="remember" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-slate-300 rounded">
                                <span class="ml-2">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="w-full flex justify-center py-3 px-4 rounded-xl shadow-lg shadow-purple-900/20 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-purple-800 via-pink-700 to-purple-900 hover:from-purple-900 hover:to-pink-800 transition-all active:scale-[0.99]">
                                Masuk ke Aplikasi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-8 pt-5 border-t border-slate-100 text-center">
                    <a href="{{ route('home') }}" class="text-xs font-bold text-pink-700 hover:text-pink-800">
                        ← Kembali ke Halaman Publik
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

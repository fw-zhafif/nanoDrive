<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lg text-zinc-900 tracking-tight">
                System Overview
            </h2>
            <a href="{{ route('drive.index') }}" class="px-3.5 py-1.5 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800 transition">
                Open My Drive
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-zinc-50 min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Bagian 1: Identitas Linux (uid/gid) & Statistik --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 bg-white border border-zinc-200 rounded-lg">
                    <p class="text-xs text-zinc-500">User Identity (uid)</p>
                    <p class="text-lg font-semibold text-zinc-900 mt-1">{{ $user->name }}</p>
                    <p class="text-xs font-mono text-zinc-400 mt-0.5">uid={{ $user->id }} ({{ $user->email }})</p>
                </div>

                <div class="p-5 bg-white border border-zinc-200 rounded-lg">
                    <p class="text-xs text-zinc-500">Group Memberships (gid)</p>
                    <p class="text-lg font-semibold text-zinc-900 mt-1">{{ $groups->count() }} Groups</p>
                    <p class="text-xs text-zinc-500 mt-0.5 truncate">
                        {{ $groups->pluck('name')->join(', ') ?: 'No group assigned' }}
                    </p>
                </div>

                <div class="p-5 bg-white border border-zinc-200 rounded-lg">
                    <p class="text-xs text-zinc-500">Owned Items</p>
                    <p class="text-lg font-semibold text-zinc-900 mt-1 tabular-nums">{{ $ownedItemsCount }} Items</p>
                    <p class="text-xs text-zinc-400 mt-0.5">Files & directories</p>
                </div>

                <div class="p-5 bg-white border border-zinc-200 rounded-lg">
                    <p class="text-xs text-zinc-500">Storage Used</p>
                    <p class="text-lg font-semibold text-zinc-900 mt-1 font-mono tabular-nums">
                        {{ number_format($storageUsed / 1024, 1) }} KB
                    </p>
                    <p class="text-xs text-zinc-400 mt-0.5">Local public disk</p>
                </div>
            </div>

            {{-- Bagian 2 & 3: Recent Files & Linux Permission Reference --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Kolom Kiri (2/3): Item Terbaru --}}
                <div class="lg:col-span-2 bg-white border border-zinc-200 rounded-lg overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-zinc-900">Recent items in NanoDrive</h3>
                        <a href="{{ route('drive.index') }}" class="text-xs text-zinc-600 hover:text-zinc-900 underline underline-offset-4">
                            View all
                        </a>
                    </div>
                    <div class="divide-y divide-zinc-100">
                        @forelse ($recentItems as $item)
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-zinc-900 truncate">{{ $item->name }}</p>
                                    <p class="text-xs text-zinc-500">
                                        Owner: {{ $item->owner->name }} · Group: {{ $item->group?->name ?? 'none' }}
                                    </p>
                                </div>
                                <div class="font-mono text-xs bg-zinc-100 border border-zinc-200 px-2 py-0.5 rounded text-zinc-800 shrink-0 ml-3">
                                    {{ $item->linux_string }} ({{ $item->octal_code }})
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-zinc-400">
                                Belum ada file atau folder yang dibuat.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Kolom Kanan (1/3): Panduan Singkat Linux Permission --}}
                <div class="bg-white border border-zinc-200 rounded-lg p-5 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900">Linux Permission Model</h3>
                        <p class="text-xs text-zinc-500 mt-0.5">Aturan evaluasi yang diterapkan oleh NanoDrive Policy.</p>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="p-2.5 bg-zinc-50 border border-zinc-200/80 rounded flex justify-between">
                            <span class="font-mono font-semibold text-zinc-900">r (Read = 4)</span>
                            <span class="text-zinc-600">Download file / lihat isi folder</span>
                        </div>
                        <div class="p-2.5 bg-zinc-50 border border-zinc-200/80 rounded flex justify-between">
                            <span class="font-mono font-semibold text-zinc-900">w (Write = 2)</span>
                            <span class="text-zinc-600">Rename, delete, upload ke folder</span>
                        </div>
                        <div class="p-2.5 bg-zinc-50 border border-zinc-200/80 rounded flex justify-between">
                            <span class="font-mono font-semibold text-zinc-900">x (Execute = 1)</span>
                            <span class="text-zinc-600">Masuk ke dalam direktori</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-zinc-100 text-xs text-zinc-500 leading-relaxed">
                        <strong class="text-zinc-800">Urutan Cek Eksklusif:</strong><br>
                        1. Owner &rarr; 2. Group &rarr; 3. Others.<br>
                        Pengecekan berhenti pada kecocokan kategori pertama.
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
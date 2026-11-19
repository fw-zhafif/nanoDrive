<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('NanoDrive Explorer') }}
            </h2>
            <span class="text-xs font-mono bg-gray-800 text-green-400 px-3 py-1 rounded">
                user: {{ Auth::user()->name }}
            </span>
        </div>
    </x-slot>

    {{-- State Alpine.js untuk mengatur buka-tutup Modal --}}
    <div class="py-10" x-data="{
        showFolderModal: false,
        showUploadModal: false,
        showRenameModal: false,
        renameUrl: '',
        renameName: '',
        renameExt: ''
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Notifikasi Sukses & Error --}}
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Toolbar Atas: Breadcrumb & Tombol Aksi --}}
            <div class="p-4 bg-white shadow sm:rounded-lg flex flex-wrap items-center justify-between gap-4">
                {{-- Navigasi Breadcrumb --}}
                <div class="flex items-center gap-3">
                    @if ($currentFolder)
                        <a href="{{ $currentFolder->parent_id ? route('drive.index', $currentFolder->parent_id) : route('drive.index') }}"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-semibold">
                            ⬅ Kembali
                        </a>
                    @endif

                    <nav class="flex items-center space-x-2 text-sm text-gray-600">
                        <a href="{{ route('drive.index') }}" class="font-semibold text-indigo-600 hover:underline">
                        /root (My Drive)
                        </a>
                        @foreach ($breadcrumbs as $crumb)
                            <span>/</span>
                            <a href="{{ route('drive.index', $crumb) }}" class="hover:underline {{ $loop->last ? 'font-bold text-gray-900' : '' }}">
                                {{ $crumb->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>
                {{-- Tombol Buat Folder & Upload --}}
                <div class="flex items-center gap-2">
                    <button @click="showFolderModal = true" type="button"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        + Folder Baru
                    </button>
                    <button @click="showUploadModal = true" type="button"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Upload File
                    </button>
                </div>
            </div>

            {{-- Tabel Daftar File & Folder --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-6 py-3 text-left">Nama</th>
                            <th class="px-6 py-3 text-left">Owner</th>
                            <th class="px-6 py-3 text-left">Group</th>
                            <th class="px-6 py-3 text-left">Linux Permissions</th>
                            <th class="px-6 py-3 text-left">Ukuran</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50">
                                {{-- Kolom Nama & Ikon --}}
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    @if ($item->type === 'folder')
                                        <a href="{{ route('drive.index', $item) }}" class="flex items-center gap-2 text-indigo-600 hover:underline">
                                            <span>📁</span>
                                            <span>{{ $item->name }}</span>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span>📄</span>
                                            <span>{{ $item->name }}</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Kolom Owner --}}
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $item->owner->name }}
                                    @if ($item->owner_id === Auth::id())
                                        <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded ml-1">You</span>
                                    @endif
                                </td>

                                {{-- Kolom Group --}}
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $item->group?->name ?? 'none' }}
                                </td>

                                {{-- Kolom Notasi Linux (Accessor dari Fase 2!) --}}
                                <td class="px-6 py-4 font-mono text-xs">
                                    <span class="bg-gray-900 text-amber-300 px-2 py-1 rounded">
                                        {{ $item->linux_string }}
                                    </span>
                                    <span class="text-gray-500 ml-1">({{ $item->octal_code }})</span>
                                </td>

                                {{-- Kolom Ukuran --}}
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    {{ $item->type === 'folder' ? '-' : number_format($item->size / 1024, 1) . ' KB' }}
                                </td>

                                {{-- Kolom Aksi --}}
                                <td class="px-6 py-4 text-right space-x-3 text-xs font-medium">
                                    @if ($item->type === 'file')
                                        <a href="{{ route('drive.item.download', $item) }}" class="text-green-600 hover:underline">
                                            Download
                                        </a>
                                    @endif

                                {{-- MULAI POIN 3 --}}
                                @php
                                    $editableName = $item->type === 'file' ? pathinfo($item->name, PATHINFO_FILENAME) : $item->name;
                                    $fileExt = $item->type === 'file' ? pathinfo($item->name, PATHINFO_EXTENSION) : '';
                                @endphp
                                <button type="button"
                                    @click="
                                        showRenameModal = true;
                                        renameUrl = '{{ route('drive.item.rename', $item) }}';
                                        renameName = '{{ addslashes($editableName) }}';
                                        renameExt = '{{ addslashes($fileExt) }}';
                                    "
                                    class="text-amber-600 hover:underline">
                                    Rename
                                </button>
                                {{-- AKHIR POIN 3 --}}

                                    <form method="POST" action="{{ route('drive.item.destroy', $item) }}" class="inline"
                                        onsubmit="return confirm('Yakin ingin menghapus {{ addslashes($item->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Direktori ini masih kosong. Buat folder baru atau unggah file di pojok kanan atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODAL 1: BUAT FOLDER BARU --}}
        <div x-show="showFolderModal" style="display: none;" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
            <div @click.away="showFolderModal = false" class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Buat Folder Baru</h3>
                <form method="POST" action="{{ route('drive.folder.store') }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                    <x-text-input name="name" type="text" class="w-full mb-4" placeholder="Nama folder..." required />
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showFolderModal = false" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 2: UPLOAD FILE --}}
        <div x-show="showUploadModal" style="display: none;" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
            <div @click.away="showUploadModal = false" class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Upload File (Maks 10MB)</h3>
                <form method="POST" action="{{ route('drive.file.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                    <input type="file" name="file" class="block w-full text-sm text-gray-600 mb-4 border rounded p-2" required>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                        <x-primary-button>Upload</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 3: RENAME ITEM --}}
        <div x-show="showRenameModal" style="display: none;" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
            <div @click.away="showRenameModal = false" class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Ganti Nama Item</h3>
                <form method="POST" :action="renameUrl">
                    @csrf
                    @method('PATCH')

                    {{-- MULAI POIN 4 --}}
                    <div class="flex items-center mb-4">
                        <x-text-input name="name" type="text" class="w-full" x-model="renameName" required />
                        <span x-show="renameExt !== ''" x-text="'.' + renameExt"
                            class="ml-2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm font-mono text-gray-600">
                        </span>
                    </div>
                    {{-- AKHIR POIN 4 --}}

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showRenameModal = false" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                        <x-primary-button>Update</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
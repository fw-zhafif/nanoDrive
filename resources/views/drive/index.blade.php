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

    <div class="py-10" x-data="{
        showFolderModal: false,
        showUploadModal: false,
        showRenameModal: false,
        renameUrl: '',
        renameName: '',
        renameExt: '',
        showPermModal: false,
        permUrl: '',
        permItemName: '',
        permIsFolder: false,
        permGroupId: '',
        owner_r: true, owner_w: true, owner_x: false,
        group_r: true, group_w: false, group_x: false,
        others_r: true, others_w: false, others_x: false,
        openPermModal(data) {
            this.permUrl = data.url;
            this.permItemName = data.name;
            this.permIsFolder = data.isFolder;
            this.permGroupId = data.groupId ? String(data.groupId) : '';
            this.owner_r = data.owner_r; this.owner_w = data.owner_w; this.owner_x = data.owner_x;
            this.group_r = data.group_r; this.group_w = data.group_w; this.group_x = data.group_x;
            this.others_r = data.others_r; this.others_w = data.others_w; this.others_x = data.others_x;
            this.showPermModal = true;
        },
        setPreset(o, g, ot) {
            this.owner_r = !!(o & 4); this.owner_w = !!(o & 2); this.owner_x = !!(o & 1);
            this.group_r = !!(g & 4); this.group_w = !!(g & 2); this.group_x = !!(g & 1);
            this.others_r = !!(ot & 4); this.others_w = !!(ot & 2); this.others_x = !!(ot & 1);
        },
        get liveLinuxString() {
            const p = this.permIsFolder ? 'd' : '-';
            const o = (this.owner_r ? 'r' : '-') + (this.owner_w ? 'w' : '-') + (this.owner_x ? 'x' : '-');
            const g = (this.group_r ? 'r' : '-') + (this.group_w ? 'w' : '-') + (this.group_x ? 'x' : '-');
            const ot = (this.others_r ? 'r' : '-') + (this.others_w ? 'w' : '-') + (this.others_x ? 'x' : '-');
            return p + o + g + ot;
        },
        get liveOctal() {
            const o = (this.owner_r ? 4 : 0) + (this.owner_w ? 2 : 0) + (this.owner_x ? 1 : 0);
            const g = (this.group_r ? 4 : 0) + (this.group_w ? 2 : 0) + (this.group_x ? 1 : 0);
            const ot = (this.others_r ? 4 : 0) + (this.others_w ? 2 : 0) + (this.others_x ? 1 : 0);
            return '' + o + g + ot;
        }
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

                {{-- Tombol Buat Folder & Upload hanya aktif jika di root ATAU punya izin Write ('w') di folder aktif --}}
                @php
                    $canWriteHere = !$currentFolder || Auth::user()->can('writeInto', $currentFolder);
                @endphp

                <div class="flex items-center gap-2">
                    @if ($canWriteHere)
                        <button @click="showFolderModal = true" type="button"
                            class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            + Folder Baru
                        </button>
                        <button @click="showUploadModal = true" type="button"
                            class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Upload File
                        </button>
                    @else
                        <span class="text-xs bg-amber-50 border border-amber-200 text-amber-700 px-3 py-1.5 rounded">
                            Mode Read-Only (Tidak ada izin Write di folder ini)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tabel Daftar File & Folder --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                @if (!$canReadContents)
                    <div class="p-10 text-center space-y-2">
                        <p class="text-red-600 font-semibold">Permission Denied (Missing Read 'r' Bit)</p>
                        <p class="text-sm text-gray-500">
                            Anda memiliki izin masuk (Execute <code>x</code>) ke folder ini, tetapi tidak memiliki izin Read (<code>r</code>) untuk melihat daftar isinya.
                        </p>
                    </div>
                @else
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
                                    {{-- Kolom Nama & Cek Izin Masuk Folder ('enter') --}}
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        @if ($item->type === 'folder')
                                            @can('enter', $item)
                                                <a href="{{ route('drive.index', $item) }}" class="flex items-center gap-2 text-indigo-600 hover:underline">
                                                    <span>📁</span>
                                                    <span>{{ $item->name }}</span>
                                                </a>
                                            @else
                                                <div class="flex items-center gap-2 text-gray-400 cursor-not-allowed" title="Tidak ada izin Execute (x) untuk masuk">
                                                    <span>🔒</span>
                                                    <span>{{ $item->name }}</span>
                                                </div>
                                            @endcan
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

                                    {{-- Kolom Notasi Linux --}}
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

                                    {{-- Kolom Aksi Terproteksi @can --}}
                                    <td class="px-6 py-4 text-right space-x-3 text-xs font-medium">
                                        @can('managePermissions', $item)
                                            <button type="button"
                                                @click="openPermModal(@js([
                                                    'url' => route('drive.item.permissions', $item),
                                                    'name' => $item->name,
                                                    'isFolder' => $item->type === 'folder',
                                                    'groupId' => $item->group_id,
                                                    'owner_r' => $item->owner_r, 'owner_w' => $item->owner_w, 'owner_x' => $item->owner_x,
                                                    'group_r' => $item->group_r, 'group_w' => $item->group_w, 'group_x' => $item->group_x,
                                                    'others_r' => $item->others_r, 'others_w' => $item->others_w, 'others_x' => $item->others_x,
                                                ]))"
                                                class="text-indigo-600 hover:underline font-semibold">
                                                Permissions
                                            </button>
                                        @endcan

                                        @if ($item->type === 'file')
                                            @can('download', $item)
                                                <a href="{{ route('drive.item.download', $item) }}" class="text-green-600 hover:underline">
                                                    Download
                                                </a>
                                            @endcan
                                        @endif

                                        @can('rename', $item)
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
                                        @endcan

                                        @can('delete', $item)
                                            <form method="POST" action="{{ route('drive.item.destroy', $item) }}" class="inline"
                                                onsubmit="return confirm('Yakin ingin menghapus {{ addslashes($item->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan

                                        @if (
                                            Auth::user()->cannot('managePermissions', $item) &&
                                            Auth::user()->cannot('download', $item) &&
                                            Auth::user()->cannot('rename', $item) &&
                                            Auth::user()->cannot('delete', $item) &&
                                            $item->type === 'file'
                                        )
                                            <span class="text-gray-400 italic">No Access</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                        Direktori ini masih kosong.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
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
                    <div class="flex items-center mb-4">
                        <x-text-input name="name" type="text" class="w-full" x-model="renameName" required />
                        <span x-show="renameExt !== ''" x-text="'.' + renameExt"
                              class="ml-2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm font-mono text-gray-600">
                        </span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showRenameModal = false" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                        <x-primary-button>Update</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 4: PENGATURAN HAK AKSES LINUX (CHMOD & CHGRP) --}}
        <div x-show="showPermModal" style="display: none;" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 p-4">
            <div @click.away="showPermModal = false" class="bg-white rounded-lg p-6 w-full max-w-lg shadow-xl space-y-5">
                <div class="flex items-start justify-between border-b pb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Pengaturan Hak Akses (Linux Permissions)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Item: <span class="font-semibold text-gray-800" x-text="permItemName"></span></p>
                    </div>
                    <div class="text-right font-mono">
                        <span class="bg-gray-900 text-amber-300 text-xs px-2.5 py-1 rounded" x-text="liveLinuxString"></span>
                        <div class="text-xs text-gray-500 mt-1">Octal: <strong x-text="liveOctal"></strong></div>
                    </div>
                </div>

                <form method="POST" :action="permUrl" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    {{-- Dropdown Ganti Group (chgrp) --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">
                            Group Kepemilikan (chgrp)
                        </label>
                        <select name="group_id" x-model="permGroupId" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">-- Tidak terikat grup (None) --</option>
                            @foreach ($userGroups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Preset Cepat --}}
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-600 mb-1.5">Preset Cepat (chmod)</span>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button type="button" @click="setPreset(6, 4, 4)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded font-mono">644 (Default File)</button>
                            <button type="button" @click="setPreset(7, 5, 5)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded font-mono">755 (Default Folder)</button>
                            <button type="button" @click="setPreset(6, 6, 0)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded font-mono">660 (Kolaborasi Grup)</button>
                            <button type="button" @click="setPreset(6, 0, 0)" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 rounded font-mono">600 (Privat Owner)</button>
                        </div>
                    </div>

                    {{-- Matriks 3x3 Hak Akses --}}
                    <div class="border rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 text-sm text-center">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Kategori</th>
                                    <th class="px-4 py-2.5">Read (r / 4)</th>
                                    <th class="px-4 py-2.5">Write (w / 2)</th>
                                    <th class="px-4 py-2.5">Execute (x / 1)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-3 text-left font-medium text-gray-800">Owner (Pemilik)</td>
                                    <td><input type="checkbox" name="owner_r" value="1" x-model="owner_r" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="owner_w" value="1" x-model="owner_w" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="owner_x" value="1" x-model="owner_x" class="rounded text-indigo-600"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-left font-medium text-gray-800">Group (Anggota Grup)</td>
                                    <td><input type="checkbox" name="group_r" value="1" x-model="group_r" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="group_w" value="1" x-model="group_w" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="group_x" value="1" x-model="group_x" class="rounded text-indigo-600"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-left font-medium text-gray-800">Others (Publik / Lainnya)</td>
                                    <td><input type="checkbox" name="others_r" value="1" x-model="others_r" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="others_w" value="1" x-model="others_w" class="rounded text-indigo-600"></td>
                                    <td><input type="checkbox" name="others_x" value="1" x-model="others_x" class="rounded text-indigo-600"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPermModal = false" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                        <x-primary-button>Simpan Perubahan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
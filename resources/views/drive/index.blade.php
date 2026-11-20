<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-lg text-zinc-900 tracking-tight">
                    NanoDrive
                </h2>
                <span class="text-zinc-300">/</span>
                <span class="text-sm text-zinc-500">Linux Permission File System</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-zinc-600 bg-zinc-100 border border-zinc-200 px-2.5 py-1 rounded-md font-mono">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span>uid: {{ Auth::user()->name }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-zinc-50 min-h-screen" x-data="{
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
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Notifikasi --}}
            @if (session('status'))
                <div class="px-4 py-3 bg-white border-l-4 border-emerald-600 border-y border-r border-zinc-200 text-zinc-800 rounded-r-md text-sm flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="px-4 py-3 bg-white border-l-4 border-red-600 border-y border-r border-zinc-200 text-red-700 rounded-r-md text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Path Bar & Primary Actions --}}
            <div class="px-4 py-3 bg-white border border-zinc-200 rounded-lg flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    @if ($currentFolder)
                        <a href="{{ $currentFolder->parent_id ? route('drive.index', $currentFolder->parent_id) : route('drive.index') }}"
                           title="Naik ke direktori induk"
                           class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-zinc-100 hover:bg-zinc-200/70 text-zinc-700 rounded-md text-xs font-medium transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Up</span>
                        </a>
                    @endif

                    <nav class="flex items-center flex-wrap gap-1.5 text-sm font-mono">
                        <a href="{{ route('drive.index') }}" class="text-zinc-900 font-semibold hover:underline">
                            /root
                        </a>
                        @foreach ($breadcrumbs as $crumb)
                            <span class="text-zinc-400">/</span>
                            <a href="{{ route('drive.index', $crumb) }}" class="hover:underline {{ $loop->last ? 'text-zinc-900 font-semibold' : 'text-zinc-600' }}">
                                {{ $crumb->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                @php
                    $canWriteHere = !$currentFolder || Auth::user()->can('writeInto', $currentFolder);
                @endphp

                <div class="flex items-center gap-2">
                    @if ($canWriteHere)
                        <button @click="showFolderModal = true" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-zinc-300 rounded-md text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 transition">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>New folder</span>
                        </button>
                        <button @click="showUploadModal = true" type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-zinc-900 border border-zinc-900 rounded-md text-xs font-medium text-white hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-zinc-900 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Upload file</span>
                        </button>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs text-zinc-600 bg-zinc-100 border border-zinc-200 px-3 py-1.5 rounded-md">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Read-only directory</span>
                        </span>
                    @endif
                </div>
            </div>

            {{-- File Explorer Table --}}
            <div class="bg-white border border-zinc-200 rounded-lg overflow-hidden">
                @if (!$canReadContents)
                    <div class="p-12 text-center max-w-md mx-auto space-y-2">
                        <div class="w-10 h-10 mx-auto rounded-full bg-zinc-100 text-zinc-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-zinc-900">Directory listing denied (missing read bit)</p>
                        <p class="text-xs text-zinc-500 leading-relaxed">
                            Bit Execute (<code class="font-mono text-zinc-800">x</code>) mengizinkan Anda masuk ke direktori ini, tetapi bit Read (<code class="font-mono text-zinc-800">r</code>) tidak aktif untuk melihat daftar isinya.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm">
                            <thead class="bg-zinc-50/70 text-xs font-medium text-zinc-500">
                                <tr>
                                    <th class="px-5 py-3 text-left font-medium">Name</th>
                                    <th class="px-5 py-3 text-left font-medium">Owner</th>
                                    <th class="px-5 py-3 text-left font-medium">Group</th>
                                    <th class="px-5 py-3 text-left font-medium">Mode (Owner · Group · Others)</th>
                                    <th class="px-5 py-3 text-right font-medium">Size</th>
                                    <th class="px-5 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse ($items as $item)
                                    @php
                                        $permStr = $item->linux_string;
                                        $segments = [
                                            ['label' => 'Owner', 'bits' => str_split(substr($permStr, 1, 3))],
                                            ['label' => 'Group', 'bits' => str_split(substr($permStr, 4, 3))],
                                            ['label' => 'Others', 'bits' => str_split(substr($permStr, 7, 3))],
                                        ];
                                    @endphp
                                    <tr class="hover:bg-zinc-50/80 transition-colors">
                                        {{-- Name --}}
                                        <td class="px-5 py-3.5">
                                            @if ($item->type === 'folder')
                                                @can('enter', $item)
                                                    <a href="{{ route('drive.index', $item) }}" class="inline-flex items-center gap-2.5 text-zinc-900 font-medium hover:text-blue-700 group">
                                                        <svg class="w-4 h-4 text-zinc-700 group-hover:text-blue-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                        </svg>
                                                        <span>{{ $item->name }}</span>
                                                    </a>
                                                @else
                                                    <div class="inline-flex items-center gap-2.5 text-zinc-400 cursor-not-allowed" title="Permission denied: missing execute (x) bit">
                                                        <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                        <span>{{ $item->name }}</span>
                                                    </div>
                                                @endcan
                                            @else
                                                <div class="inline-flex items-center gap-2.5 text-zinc-800">
                                                    <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{{ $item->name }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Owner --}}
                                        <td class="px-5 py-3.5 text-zinc-600 text-xs">
                                            <span>{{ $item->owner->name }}</span>
                                            @if ($item->owner_id === Auth::id())
                                                <span class="ml-1 text-zinc-400">(you)</span>
                                            @endif
                                        </td>

                                        {{-- Group --}}
                                        <td class="px-5 py-3.5 text-xs">
                                            @if ($item->group)
                                                <span class="text-zinc-700 font-medium">{{ $item->group->name }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Linux Mode (Clean Tabular Monospace) --}}
                                        <td class="px-5 py-3.5">
                                            <div class="inline-flex items-center gap-2 font-mono text-xs" title="{{ $permStr }}">
                                                <span class="px-1.5 py-0.5 bg-zinc-100 border border-zinc-200 text-zinc-700 rounded font-medium tabular-nums">
                                                    {{ $item->octal_code }}
                                                </span>
                                                <div class="inline-flex items-center gap-1.5 tracking-tight">
                                                    @foreach ($segments as $idx => $seg)
                                                        <span title="{{ $seg['label'] }}: {{ implode('', $seg['bits']) }}" class="inline-flex">
                                                            @foreach ($seg['bits'] as $b)
                                                                <span class="{{ $b === '-' ? 'text-zinc-300' : 'text-zinc-900 font-semibold' }}">{{ $b }}</span>
                                                            @endforeach
                                                        </span>
                                                        @if ($idx < 2)
                                                            <span class="text-zinc-300 select-none">·</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Size --}}
                                        <td class="px-5 py-3.5 text-right text-zinc-500 font-mono text-xs tabular-nums">
                                            {{ $item->type === 'folder' ? '—' : number_format($item->size / 1024, 1) . ' KB' }}
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-3.5 text-right">
                                            <div class="inline-flex items-center justify-end gap-3 text-xs font-medium">
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
                                                        class="text-zinc-900 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-900">
                                                        Permissions
                                                    </button>
                                                @endcan

                                                @if ($item->type === 'file')
                                                    @can('download', $item)
                                                        <a href="{{ route('drive.item.download', $item) }}"
                                                           class="text-zinc-600 hover:text-zinc-900">
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
                                                        class="text-zinc-600 hover:text-zinc-900">
                                                        Rename
                                                    </button>
                                                @endcan

                                                @can('delete', $item)
                                                    <form method="POST" action="{{ route('drive.item.destroy', $item) }}" class="inline"
                                                        onsubmit="return confirm('Hapus {{ addslashes($item->name) }} secara permanen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
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
                                                    <span class="text-zinc-400">No access</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-zinc-400 text-xs">
                                            Directory is empty.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- MODAL 1: NEW FOLDER --}}
        <div x-show="showFolderModal" style="display: none;" class="fixed inset-0 bg-zinc-900/40 flex items-center justify-center z-50 p-4">
            <div @click.away="showFolderModal = false" class="bg-white border border-zinc-200 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-zinc-900 mb-1">Create new folder</h3>
                <p class="text-xs text-zinc-500 mb-4">Default mode: <span class="font-mono text-zinc-700">755 (drwxr-xr-x)</span></p>
                <form method="POST" action="{{ route('drive.folder.store') }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                    <input name="name" type="text" class="w-full border-zinc-300 rounded-md text-sm focus:border-zinc-900 focus:ring-zinc-900 mb-4" placeholder="Folder name" required />
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showFolderModal = false" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800">Create</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 2: UPLOAD FILE --}}
        <div x-show="showUploadModal" style="display: none;" class="fixed inset-0 bg-zinc-900/40 flex items-center justify-center z-50 p-4">
            <div @click.away="showUploadModal = false" class="bg-white border border-zinc-200 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-zinc-900 mb-1">Upload file</h3>
                <p class="text-xs text-zinc-500 mb-4">Max 10 MB · Default mode: <span class="font-mono text-zinc-700">644 (-rw-r--r--)</span></p>
                <form method="POST" action="{{ route('drive.file.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                    <input type="file" name="file" class="block w-full text-xs text-zinc-600 mb-4 border border-zinc-300 rounded-md p-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-800 hover:file:bg-zinc-200" required>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showUploadModal = false" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 3: RENAME --}}
        <div x-show="showRenameModal" style="display: none;" class="fixed inset-0 bg-zinc-900/40 flex items-center justify-center z-50 p-4">
            <div @click.away="showRenameModal = false" class="bg-white border border-zinc-200 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-zinc-900 mb-4">Rename item</h3>
                <form method="POST" :action="renameUrl">
                    @csrf
                    @method('PATCH')
                    <div class="flex items-center mb-4">
                        <input name="name" type="text" class="w-full border-zinc-300 rounded-md text-sm focus:border-zinc-900 focus:ring-zinc-900" x-model="renameName" required />
                        <span x-show="renameExt !== ''" x-text="'.' + renameExt"
                              class="ml-2 px-2.5 py-2 bg-zinc-100 border border-zinc-200 rounded-md text-xs font-mono text-zinc-600">
                        </span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showRenameModal = false" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 4: PERMISSIONS INSPECTOR (CHMOD & CHGRP) --}}
        <div x-show="showPermModal" style="display: none;" class="fixed inset-0 bg-zinc-900/40 flex items-center justify-center z-50 p-4">
            <div @click.away="showPermModal = false" class="bg-white border border-zinc-200 rounded-lg p-6 w-full max-w-lg space-y-5">
                <div class="flex items-start justify-between border-b border-zinc-200 pb-3.5">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900">Access permissions</h3>
                        <p class="text-xs text-zinc-500 mt-0.5 font-mono" x-text="permItemName"></p>
                    </div>
                    <div class="text-right font-mono">
                        <div class="inline-flex items-center gap-1.5 bg-zinc-100 border border-zinc-200 px-2.5 py-1 rounded text-xs text-zinc-900">
                            <span x-text="liveLinuxString"></span>
                            <span class="text-zinc-400">|</span>
                            <strong x-text="liveOctal"></strong>
                        </div>
                    </div>
                </div>

                <form method="POST" :action="permUrl" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Assigned group (<code class="font-mono text-zinc-500">chgrp</code>)
                        </label>
                        <select name="group_id" x-model="permGroupId" class="w-full border-zinc-300 rounded-md text-sm focus:border-zinc-900 focus:ring-zinc-900">
                            <option value="">No group assigned</option>
                            @foreach ($userGroups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-zinc-700 mb-1.5">Quick presets (<code class="font-mono text-zinc-500">chmod</code>)</span>
                        <div class="flex flex-wrap gap-1.5 text-xs font-mono">
                            <button type="button" @click="setPreset(6, 4, 4)" class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 rounded text-zinc-800">644 File</button>
                            <button type="button" @click="setPreset(7, 5, 5)" class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 rounded text-zinc-800">755 Dir</button>
                            <button type="button" @click="setPreset(6, 6, 0)" class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 rounded text-zinc-800">660 Group</button>
                            <button type="button" @click="setPreset(6, 0, 0)" class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 rounded text-zinc-800">600 Private</button>
                        </div>
                    </div>

                    <div class="border border-zinc-200 rounded-md overflow-hidden">
                        <table class="min-w-full divide-y divide-zinc-200 text-xs text-center">
                            <thead class="bg-zinc-50 text-zinc-600 font-medium">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-medium">Class</th>
                                    <th class="px-4 py-2.5 font-medium">Read (4)</th>
                                    <th class="px-4 py-2.5 font-medium">Write (2)</th>
                                    <th class="px-4 py-2.5 font-medium">Execute (1)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <tr>
                                    <td class="px-4 py-2.5 text-left font-medium text-zinc-900">Owner</td>
                                    <td><input type="checkbox" name="owner_r" value="1" x-model="owner_r" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="owner_w" value="1" x-model="owner_w" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="owner_x" value="1" x-model="owner_x" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 text-left font-medium text-zinc-900">Group</td>
                                    <td><input type="checkbox" name="group_r" value="1" x-model="group_r" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="group_w" value="1" x-model="group_w" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="group_x" value="1" x-model="group_x" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2.5 text-left font-medium text-zinc-900">Others</td>
                                    <td><input type="checkbox" name="others_r" value="1" x-model="others_r" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="others_w" value="1" x-model="others_w" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                    <td><input type="checkbox" name="others_x" value="1" x-model="others_x" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPermModal = false" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800">Apply changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
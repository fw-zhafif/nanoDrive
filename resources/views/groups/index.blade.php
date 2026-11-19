<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Linux User Groups') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Notifikasi Sukses --}}
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Notifikasi Error Validasi --}}
            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form Buat Grup Baru --}}
            <div class="p-6 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Buat Grup Baru</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Kelompokkan pengguna agar dapat berbagi hak akses file/folder secara bersamaan (mirip perintah <code class="bg-gray-100 px-1 rounded">groupadd</code>).
                </p>

                <form method="POST" action="{{ route('groups.store') }}" class="flex gap-3">
                    @csrf
                    <x-text-input
                        name="name"
                        type="text"
                        class="flex-1"
                        placeholder="Nama grup (misal: Tim Proyek OS, Asisten Dosen)"
                        required
                    />
                    <x-primary-button>Buat Grup</x-primary-button>
                </form>
            </div>

            {{-- Daftar Grup yang Diikuti --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse ($groups as $group)
                    <div class="p-6 bg-white shadow sm:rounded-lg flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between border-b pb-3 mb-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $group->name }}</h4>
                                    <p class="text-xs text-gray-500">
                                        Admin Grup: <span class="font-medium">{{ $group->creator->name }}</span>
                                    </p>
                                </div>

                                @if ($group->created_by === auth()->id())
                                    <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Hapus grup ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">
                                            Hapus Grup
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- Daftar Anggota --}}
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">
                                Anggota ({{ $group->members->count() }})
                            </p>
                            <ul class="space-y-2 mb-5">
                                @foreach ($group->members as $member)
                                    <li class="flex items-center justify-between text-sm bg-gray-50 px-3 py-1.5 rounded">
                                        <div>
                                            <span class="font-medium text-gray-800">{{ $member->name }}</span>
                                            <span class="text-xs text-gray-500">({{ $member->email }})</span>
                                            @if ($member->id === $group->created_by)
                                                <span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-semibold">Admin</span>
                                            @endif
                                        </div>

                                        {{-- Tombol keluarkan anggota (hanya muncul untuk Admin grup & bukan dirinya sendiri) --}}
                                        @if ($group->created_by === auth()->id() && $member->id !== $group->created_by)
                                            <form method="POST" action="{{ route('groups.members.remove', [$group, $member]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                                    Keluarkan
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Form Tambah Anggota (Hanya untuk Admin Grup) --}}
                        @if ($group->created_by === auth()->id())
                            <form method="POST" action="{{ route('groups.members.add', $group) }}" class="pt-3 border-t flex gap-2">
                                @csrf
                                <x-text-input
                                    name="email"
                                    type="email"
                                    class="flex-1 text-sm"
                                    placeholder="Email user untuk ditambahkan..."
                                    required
                                />
                                <x-primary-button class="text-xs">Tambah</x-primary-button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="col-span-2 p-6 bg-white shadow sm:rounded-lg text-center text-gray-500 text-sm">
                        Kamu belum tergabung dalam grup mana pun. Buat grup pertamamu di atas.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
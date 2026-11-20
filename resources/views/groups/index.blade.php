<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lg text-zinc-900 tracking-tight">
                User Groups
            </h2>
            <span class="text-xs font-mono text-zinc-500">/etc/group simulation</span>
        </div>
    </x-slot>

    <div class="py-8 bg-zinc-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="px-4 py-3 bg-white border-l-4 border-emerald-600 border-y border-r border-zinc-200 text-zinc-800 rounded-r-md text-sm">
                    {{ session('status') }}
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

            {{-- Create Group --}}
            <div class="p-5 bg-white border border-zinc-200 rounded-lg">
                <h3 class="text-sm font-semibold text-zinc-900 mb-1">Create new group</h3>
                <p class="text-xs text-zinc-500 mb-4">
                    Group users together to assign shared <code class="font-mono text-zinc-700">rwx</code> permissions on files and folders.
                </p>

                <form method="POST" action="{{ route('groups.store') }}" class="flex gap-2.5">
                    @csrf
                    <input
                        name="name"
                        type="text"
                        class="flex-1 border-zinc-300 rounded-md text-sm focus:border-zinc-900 focus:ring-zinc-900"
                        placeholder="Group name (e.g. Praktikum OS)"
                        required
                    />
                    <button type="submit" class="px-4 py-2 bg-zinc-900 text-white rounded-md text-xs font-medium hover:bg-zinc-800 transition">
                        Create group
                    </button>
                </form>
            </div>

            {{-- Group List --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse ($groups as $group)
                    <div class="p-5 bg-white border border-zinc-200 rounded-lg flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between border-b border-zinc-100 pb-3 mb-3">
                                <div>
                                    <h4 class="text-base font-semibold text-zinc-900">{{ $group->name }}</h4>
                                    <p class="text-xs text-zinc-500 mt-0.5">
                                        Admin: <span class="text-zinc-700 font-medium">{{ $group->creator->name }}</span>
                                    </p>
                                </div>

                                @if ($group->created_by === auth()->id())
                                    <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Hapus grup ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <p class="text-xs font-medium text-zinc-500 mb-2">
                                Members ({{ $group->members->count() }})
                            </p>
                            <ul class="space-y-1.5 mb-4">
                                @foreach ($group->members as $member)
                                    <li class="flex items-center justify-between text-xs bg-zinc-50 border border-zinc-200/70 px-3 py-2 rounded-md">
                                        <div>
                                            <span class="font-medium text-zinc-900">{{ $member->name }}</span>
                                            <span class="text-zinc-500 ml-1">{{ $member->email }}</span>
                                            @if ($member->id === $group->created_by)
                                                <span class="ml-1.5 text-[11px] text-zinc-500 font-mono">[admin]</span>
                                            @endif
                                        </div>

                                        @if ($group->created_by === auth()->id() && $member->id !== $group->created_by)
                                            <form method="POST" action="{{ route('groups.members.remove', [$group, $member]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                                    Remove
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($group->created_by === auth()->id())
                            <form method="POST" action="{{ route('groups.members.add', $group) }}" class="pt-3 border-t border-zinc-100 flex gap-2">
                                @csrf
                                <input
                                    name="email"
                                    type="email"
                                    class="flex-1 border-zinc-300 rounded-md text-xs focus:border-zinc-900 focus:ring-zinc-900"
                                    placeholder="User email address..."
                                    required
                                />
                                <button type="submit" class="px-3 py-1.5 bg-zinc-100 hover:bg-zinc-200 border border-zinc-300 text-zinc-800 rounded-md text-xs font-medium transition">
                                    Add
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="col-span-2 p-8 bg-white border border-zinc-200 rounded-lg text-center text-zinc-400 text-xs">
                        You are not a member of any group yet.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
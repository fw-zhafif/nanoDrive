<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $groups = $user->groups()
            ->with(['creator', 'members'])
            ->latest()
            ->get();

        return view('groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'created_by' => Auth::id(),
        ]);

        // Pembuat grup otomatis didaftarkan sebagai anggota pertama
        $group->members()->attach(Auth::id());

        return back()->with('status', "Grup '{$group->name}' berhasil dibuat.");
    }

    public function addMember(Request $request, Group $group)
    {
        // Hanya pembuat grup yang boleh menambah anggota
        abort_if($group->created_by !== Auth::id(), 403, 'Hanya pembuat grup yang dapat mengelola anggota.');

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'User dengan email tersebut belum terdaftar di NanoDrive.',
        ]);

        $userToAdd = User::where('email', $validated['email'])->first();

        // Cek agar tidak duplikat di tabel pivot
        if ($group->members()->where('user_id', $userToAdd->id)->exists()) {
            return back()->withErrors(['email' => "{$userToAdd->name} sudah menjadi anggota grup ini."]);
        }

        $group->members()->attach($userToAdd->id);

        return back()->with('status', "{$userToAdd->name} berhasil ditambahkan ke grup {$group->name}.");
    }

    public function removeMember(Group $group, User $user)
    {
        abort_if($group->created_by !== Auth::id(), 403);

        // Cegah pembuat grup mengeluarkan dirinya sendiri
        if ($user->id === $group->created_by) {
            return back()->withErrors(['member' => 'Pembuat grup tidak dapat dikeluarkan dari grupnya sendiri.']);
        }

        $group->members()->detach($user->id);

        return back()->with('deleted', "{$user->name} dikeluarkan dari grup {$group->name}.");
    }

    public function destroy(Group $group)
    {
        abort_if($group->created_by !== Auth::id(), 403);

        $group->delete();

        return back()->with('deleted', "Grup '{$group->name}' berhasil dihapus.");
    }
}
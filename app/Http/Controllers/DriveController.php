<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    public function index(?Item $folder = null)
    {
        abort_if($folder && $folder->type !== 'folder', 404);

        // Jika membuka subfolder, wajib punya izin Execute ('x') pada folder tersebut
        if ($folder) {
            Gate::authorize('enter', $folder);
        }

        // Bangun jalur Breadcrumb
        $breadcrumbs = [];
        $current = $folder;
        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }

        // Cek apakah user punya izin Read ('r') untuk melihat isi folder ini
        $canReadContents = $folder ? Gate::allows('readContents', $folder) : true;

        $items = $canReadContents
            ? Item::with(['owner', 'group'])
                ->where('parent_id', $folder?->id)
                ->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                ->latest()
                ->get()
            : collect();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('drive.index', [
            'currentFolder' => $folder,
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
            'canReadContents' => $canReadContents,
            'userGroups' => $user->groups()->get(),
        ]);
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:items,id'],
        ]);

        $parentFolder = !empty($validated['parent_id']) ? Item::findOrFail($validated['parent_id']) : null;
        if ($parentFolder) {
            Gate::authorize('writeInto', $parentFolder);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        Item::create([
            'name' => $validated['name'],
            'type' => 'folder',
            'parent_id' => $parentFolder?->id,
            'owner_id' => $user->id,
            'group_id' => $parentFolder?->group_id ?? $user->groups()->first()?->id,
            'owner_r' => true, 'owner_w' => true, 'owner_x' => true,
            'group_r' => true, 'group_w' => false, 'group_x' => true,
            'others_r' => true, 'others_w' => false, 'others_x' => true,
        ]);

        return back()->with('status', "Folder '{$validated['name']}' berhasil dibuat.");
    }

    public function uploadFile(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'parent_id' => ['nullable', 'exists:items,id'],
        ]);

        $parentFolder = !empty($validated['parent_id']) ? Item::findOrFail($validated['parent_id']) : null;
        if ($parentFolder) {
            Gate::authorize('writeInto', $parentFolder);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('nanodrive');

        Item::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'type' => 'file',
            'parent_id' => $parentFolder?->id,
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
            'owner_id' => $user->id,
            'group_id' => $parentFolder?->group_id ?? $user->groups()->first()?->id,
            'owner_r' => true, 'owner_w' => true, 'owner_x' => false,
            'group_r' => true, 'group_w' => false, 'group_x' => false,
            'others_r' => true, 'others_w' => false, 'others_x' => false,
        ]);

        return back()->with('status', "File '{$uploadedFile->getClientOriginalName()}' berhasil diunggah.");
    }

    public function download(Item $item)
    {
        Gate::authorize('download', $item);

        abort_if(!$item->file_path, 404);
        abort_unless(Storage::exists($item->file_path), 404, 'File fisik tidak ditemukan di storage.');

        return Storage::download($item->file_path, $item->name);
    }

    public function rename(Request $request, Item $item)
    {
        Gate::authorize('rename', $item);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $newName = trim($validated['name']);

        if ($item->type === 'file') {
            $originalExt = pathinfo($item->name, PATHINFO_EXTENSION);
            if ($originalExt !== '') {
                $cleanBase = preg_replace('/\.' . preg_quote($originalExt, '/') . '$/i', '', $newName);
                $newName = "{$cleanBase}.{$originalExt}";
            }
        }

        $item->update(['name' => $newName]);

        return back()->with('status', 'Nama item berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        Gate::authorize('delete', $item);

        $name = $item->name;
        $this->deleteItemRecursively($item);

        return back()->with('deleted', "'{$name}' berhasil dihapus.");
    }

    // Method baru: Mengatur Group (chgrp) dan 9 Bit Permissions (chmod)
    public function updatePermissions(Request $request, Item $item)
    {
        Gate::authorize('managePermissions', $item);

        $validated = $request->validate([
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Pastikan group yang dipilih memang grup yang diikuti oleh Owner
        if (!empty($validated['group_id'])) {
            abort_unless(
                $user->groups()->where('groups.id', $validated['group_id'])->exists(),
                403,
                'Anda bukan anggota dari grup tersebut.'
            );
        }

        $item->update([
            'group_id' => $validated['group_id'] ?? null,
            'owner_r' => $request->boolean('owner_r'),
            'owner_w' => $request->boolean('owner_w'),
            'owner_x' => $request->boolean('owner_x'),
            'group_r' => $request->boolean('group_r'),
            'group_w' => $request->boolean('group_w'),
            'group_x' => $request->boolean('group_x'),
            'others_r' => $request->boolean('others_r'),
            'others_w' => $request->boolean('others_w'),
            'others_x' => $request->boolean('others_x'),
        ]);

        return back()->with('status', "Hak akses '{$item->name}' diperbarui menjadi {$item->linux_string} ({$item->octal_code}).");
    }

    private function deleteItemRecursively(Item $item): void
    {
        if ($item->type === 'folder') {
            foreach ($item->children as $child) {
                $this->deleteItemRecursively($child);
            }
        } elseif ($item->file_path) {
            Storage::delete($item->file_path);
        }

        $item->delete();
    }
}
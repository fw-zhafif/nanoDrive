<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    public function index(?Item $folder = null)
    {
        // Pastikan jika parameter folder diisi, item tersebut memang bertipe 'folder'
        abort_if($folder && $folder->type !== 'folder', 404);

        // Bangun jalur Breadcrumb dari folder saat ini naik ke folder induknya
        $breadcrumbs = [];
        $current = $folder;
        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }

        // Ambil isi direktori saat ini (Folder diurutkan di atas File)
        $items = Item::with(['owner', 'group'])
            ->where('parent_id', $folder?->id)
            ->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('drive.index', [
            'currentFolder' => $folder,
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
        ]);
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:items,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Default folder Linux: 755 (rwxr-xr-x)
        Item::create([
            'name' => $validated['name'],
            'type' => 'folder',
            'parent_id' => $validated['parent_id'] ?? null,
            'owner_id' => $user->id,
            'group_id' => $user->groups()->first()?->id, // Otomatis memakai grup pertama user (jika ada)
            'owner_r' => true, 'owner_w' => true, 'owner_x' => true,
            'group_r' => true, 'group_w' => false, 'group_x' => true,
            'others_r' => true, 'others_w' => false, 'others_x' => true,
        ]);

        return back()->with('status', "Folder '{$validated['name']}' berhasil dibuat.");
    }

    public function uploadFile(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // Maksimal 10 MB
            'parent_id' => ['nullable', 'exists:items,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $uploadedFile = $request->file('file');

        // Simpan file fisik ke storage/app/public/nanodrive
        $path = $uploadedFile->store('nanodrive', 'public');

        // Default file Linux: 644 (rw-r--r--)
        Item::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'type' => 'file',
            'parent_id' => $validated['parent_id'] ?? null,
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
            'owner_id' => $user->id,
            'group_id' => $user->groups()->first()?->id,
            'owner_r' => true, 'owner_w' => true, 'owner_x' => false,
            'group_r' => true, 'group_w' => false, 'group_x' => false,
            'others_r' => true, 'others_w' => false, 'others_x' => false,
        ]);

        return back()->with('status', "File '{$uploadedFile->getClientOriginalName()}' berhasil diunggah.");
    }

    public function download(Item $item)
    {
        abort_if($item->type !== 'file' || !$item->file_path, 404);

        $fullPath = Storage::disk('public')->path($item->file_path);
        abort_unless(file_exists($fullPath), 404, 'File fisik tidak ditemukan di server.');

        return response()->download($fullPath, $item->name);
    }

    public function rename(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $newName = trim($validated['name']);

        // Jika item adalah file, pertahankan ekstensi aslinya agar file tidak rusak saat diunduh
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
        $name = $item->name;
        $this->deleteItemRecursively($item);

        return back()->with('status', "'{$name}' berhasil dihapus.");
    }

    // Helper rekursif untuk menghapus file fisik di dalam subfolder sebelum menghapus record DB
    private function deleteItemRecursively(Item $item): void
    {
        if ($item->type === 'folder') {
            foreach ($item->children as $child) {
                $this->deleteItemRecursively($child);
            }
        } elseif ($item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }

        $item->delete();
    }
}
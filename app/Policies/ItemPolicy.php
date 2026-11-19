<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Inti Algoritma Linux:
     * Mengecek bit ('r', 'w', atau 'x') berdasarkan urutan eksklusif:
     * 1. Owner -> 2. Group -> 3. Others
     */
    private function checkLinuxBit(User $user, Item $item, string $bit): bool
    {
        // 1. Cek apakah user adalah Owner
        if ($user->id === $item->owner_id) {
            return (bool) $item->{"owner_{$bit}"};
        }

        // 2. Jika bukan Owner, cek apakah user tergabung dalam Group milik item
        if ($item->group_id && $user->groups()->where('groups.id', $item->group_id)->exists()) {
            return (bool) $item->{"group_{$bit}"};
        }

        // 3. Jika bukan Owner maupun anggota Group, gunakan izin Others
        return (bool) $item->{"others_{$bit}"};
    }

    // Masuk ke dalam folder (membutuhkan izin Execute 'x' pada folder)
    public function enter(User $user, Item $item): bool
    {
        return $item->type === 'folder' && $this->checkLinuxBit($user, $item, 'x');
    }

    // Melihat daftar isi di dalam folder (membutuhkan izin Read 'r' pada folder)
    public function readContents(User $user, Item $item): bool
    {
        return $item->type === 'folder' && $this->checkLinuxBit($user, $item, 'r');
    }

    // Mengunduh file (membutuhkan izin Read 'r' pada file)
    public function download(User $user, Item $item): bool
    {
        return $item->type === 'file' && $this->checkLinuxBit($user, $item, 'r');
    }

    // Membuat subfolder atau mengunggah file di dalam folder (membutuhkan Write 'w' pada folder)
    public function writeInto(User $user, Item $folder): bool
    {
        return $folder->type === 'folder' && $this->checkLinuxBit($user, $folder, 'w');
    }

    // Mengubah nama item (membutuhkan izin Write 'w')
    public function rename(User $user, Item $item): bool
    {
        return $this->checkLinuxBit($user, $item, 'w');
    }

    // Menghapus item (membutuhkan izin Write 'w')
    public function delete(User $user, Item $item): bool
    {
        return $this->checkLinuxBit($user, $item, 'w');
    }

    // Mengubah hak akses (chmod) dan grup (chgrp): HANYA boleh dilakukan oleh Owner
    public function managePermissions(User $user, Item $item): bool
    {
        return $user->id === $item->owner_id;
    }
}
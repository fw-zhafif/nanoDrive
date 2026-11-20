<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan file dummy lama di storage agar tidak menumpuk
        Storage::disk('public')->deleteDirectory('nanodrive');

        // 1. Buat 3 Akun Demo (Owner, Anggota Group, dan Others)
        $faiz = User::create([
            'name' => 'Faiz',
            'email' => 'faiz@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $surya = User::create([
            'name' => 'Pak Surya',
            'email' => 'surya@kampus.ac.id',
            'password' => Hash::make('password'),
        ]);

        $budi = User::create([
            'name' => 'Budi',
            'email' => 'budi@kampus.ac.id',
            'password' => Hash::make('password'),
        ]);

        // 2. Buat Grup Praktikum OS (Beranggotakan Faiz & Pak Surya, tanpa Budi)
        $group = Group::create([
            'name' => 'Praktikum OS',
            'created_by' => $faiz->id,
        ]);
        $group->members()->attach([$faiz->id, $surya->id]);

        // 3. Buat Folder Demo (Mode 750: drwxr-x--- -> Owner & Group bisa masuk, Others terkunci)
        $folder = Item::create([
            'name' => 'Materi_Praktikum_OS',
            'type' => 'folder',
            'owner_id' => $faiz->id,
            'group_id' => $group->id,
            'owner_r' => true, 'owner_w' => true, 'owner_x' => true,
            'group_r' => true, 'group_w' => false, 'group_x' => true,
            'others_r' => false, 'others_w' => false, 'others_x' => false,
        ]);

        // 4. Buat File Fisik Dummy di dalam folder tersebut (Mode 660: Kolaborasi Grup)
        $sampleContent = "Laporan Praktikum Sistem Operasi - Simulasi Linux File Permissions (rwx) pada NanoDrive.\n";
        $samplePath = 'nanodrive/catatan_sistem_operasi.txt';
        Storage::disk('public')->put($samplePath, $sampleContent);

        Item::create([
            'name' => 'catatan_sistem_operasi.txt',
            'type' => 'file',
            'parent_id' => $folder->id,
            'file_path' => $samplePath,
            'mime_type' => 'text/plain',
            'size' => strlen($sampleContent),
            'owner_id' => $faiz->id,
            'group_id' => $group->id,
            'owner_r' => true, 'owner_w' => true, 'owner_x' => false,
            'group_r' => true, 'group_w' => true, 'group_x' => false,
            'others_r' => false, 'others_w' => false, 'others_x' => false,
        ]);
    }
}
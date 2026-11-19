<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name', 'type', 'parent_id', 'file_path', 'mime_type', 'size',
        'owner_id', 'group_id',
        'owner_r', 'owner_w', 'owner_x',
        'group_r', 'group_w', 'group_x',
        'others_r', 'others_w', 'others_x',
    ];

    // Memastikan nilai dari database selalu dibaca sebagai boolean murni (true/false)
    protected $casts = [
        'owner_r' => 'boolean', 'owner_w' => 'boolean', 'owner_x' => 'boolean',
        'group_r' => 'boolean', 'group_w' => 'boolean', 'group_x' => 'boolean',
        'others_r' => 'boolean', 'others_w' => 'boolean', 'others_x' => 'boolean',
    ];

    // --- RELASI ---
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function parent()
    {
        return $this->belongsTo(Item::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Item::class, 'parent_id');
    }

    // ---  LOGIKA LINUX ---
    // Dipanggil lewat: $item->linux_string (Hasil contoh: "-rw-r--r--" atau "drwxr-xr-x")
    protected function linuxString(): Attribute
    {
        return Attribute::get(function () {
            $prefix = $this->type === 'folder' ? 'd' : '-';

            $owner = ($this->owner_r ? 'r' : '-') . ($this->owner_w ? 'w' : '-') . ($this->owner_x ? 'x' : '-');
            $group = ($this->group_r ? 'r' : '-') . ($this->group_w ? 'w' : '-') . ($this->group_x ? 'x' : '-');
            $others = ($this->others_r ? 'r' : '-') . ($this->others_w ? 'w' : '-') . ($this->others_x ? 'x' : '-');

            return $prefix . $owner . $group . $others;
        });
    }

    // Dipanggil lewat: $item->octal_code (Hasil contoh: "644" atau "755")
    protected function octalCode(): Attribute
    {
        return Attribute::get(function () {
            $owner = ($this->owner_r ? 4 : 0) + ($this->owner_w ? 2 : 0) + ($this->owner_x ? 1 : 0);
            $group = ($this->group_r ? 4 : 0) + ($this->group_w ? 2 : 0) + ($this->group_x ? 1 : 0);
            $others = ($this->others_r ? 4 : 0) + ($this->others_w ? 2 : 0) + ($this->others_x ? 1 : 0);

            return "{$owner}{$group}{$others}";
        });
    }
}
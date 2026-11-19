<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['file', 'folder']);

            // Self-referencing: jika parent dihapus, seluruh isi di dalamnya ikut terhapus
            $table->foreignId('parent_id')->nullable()->constrained('items')->cascadeOnDelete();

            // Metadata fisik file (null jika item adalah folder)
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            // Kepemilikan Linux: Owner & Group
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();

            // 9 Bit Hak Akses Linux (Default 644: rw-r--r--)
            $table->boolean('owner_r')->default(true);
            $table->boolean('owner_w')->default(true);
            $table->boolean('owner_x')->default(false);

            $table->boolean('group_r')->default(true);
            $table->boolean('group_w')->default(false);
            $table->boolean('group_x')->default(false);

            $table->boolean('others_r')->default(true);
            $table->boolean('others_w')->default(false);
            $table->boolean('others_x')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('outro');
            $table->string('file_path');
            $table->string('storage_type')->default('local'); // 'nas' | 'local'
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};

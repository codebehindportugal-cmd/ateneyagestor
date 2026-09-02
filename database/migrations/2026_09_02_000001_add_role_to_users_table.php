<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Valores em App\Models\User::roleOptions(). O default é o papel mais
            // limitado: quem for criado sem se pensar no assunto fica estagiário,
            // nunca administrador.
            $table->string('role', 20)->default('estagiario')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('job_title')->nullable()->after('is_active');
        });

        // Quem já cá estava antes dos papéis existirem é gente da casa.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'job_title']);
        });
    }
};

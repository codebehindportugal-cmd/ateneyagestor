<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * As contas dos estagiários de 2026. Ficam aqui, e não num seeder, porque o
 * deploy só corre migrações — assim entram sozinhas quando o código subir.
 *
 * A password é temporária e cada um muda a sua em Perfil (canto superior
 * direito do painel). Se a conta já existir, não se lhe toca: uma migração
 * que corra duas vezes não pode ir apagar a password que a pessoa entretanto
 * escolheu.
 */
return new class extends Migration
{
    private const ESTAGIARIOS = [
        ['numero' => 'a23399', 'password' => 'Estagio-3r66mv75'],
        ['numero' => 'a29132', 'password' => 'Estagio-x8yoqfq9'],
        ['numero' => 'a27720', 'password' => 'Estagio-d7hf2i1h'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ESTAGIARIOS as $pessoa) {
            $email = $pessoa['numero'] . '@ead-aerbp.pt';

            if (DB::table('users')->where('email', $email)->exists()) {
                continue;
            }

            DB::table('users')->insert([
                'name'       => $pessoa['numero'],
                'email'      => $email,
                'password'   => Hash::make($pessoa['password']),
                'role'       => 'estagiario',
                'is_active'  => true,
                'job_title'  => 'Estagiário',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::ESTAGIARIOS as $pessoa) {
            DB::table('users')
                ->where('email', $pessoa['numero'] . '@ead-aerbp.pt')
                ->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onde é o login de cada site.
 *
 * Vários sites escondem o /wp-admin atrás de um endereço próprio. Isso vivia
 * na cabeça de quem os montou — e uma pessoa nova que precise de entrar não
 * tem por onde adivinhar. Em branco lê-se `/wp-admin`; preenchido, manda o que
 * lá estiver.
 */
return new class extends Migration
{
    /** O que já se sabe, por nome de site. */
    private const LOGINS = [
        'ateneya'             => 'https://ateneya.com/leonardo',
        'codebehindtech-com'  => 'https://codebehindtech.com/leonardo',
        'horaciovleal-com'    => 'https://horaciovleal.com/vascodagama',
        'hortadamaria-loja'   => 'https://hortadamaria.com/wp-admin/',
        'faustinoclemente-pt' => 'https://www.faustinoclemente.pt/logincodebehind',
        'alvorfisconta-com'   => 'https://alvorfisconta.com/logincodebehind',
    ];

    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('login_url')->nullable()->after('domain');
        });

        foreach (self::LOGINS as $nome => $url) {
            DB::table('sites')->where('name', $nome)->update(['login_url' => $url]);
        }
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('login_url');
        });
    }
};

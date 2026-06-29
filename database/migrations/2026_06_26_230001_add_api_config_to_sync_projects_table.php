<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->string('wintouch_base_url')->nullable()->after('runner_schedule');
            $table->text('wintouch_api_key')->nullable()->after('wintouch_base_url');
            $table->string('wintouch_login_email')->nullable()->after('wintouch_api_key');
            $table->text('wintouch_login_password')->nullable()->after('wintouch_login_email');

            $table->text('woo_consumer_key')->nullable()->after('site_url');
            $table->text('woo_consumer_secret')->nullable()->after('woo_consumer_key');
            $table->string('woo_api_version', 20)->default('wc/v3')->after('woo_consumer_secret');
            $table->string('woo_admin_username')->nullable()->after('woo_api_version');
            $table->text('woo_admin_app_password')->nullable()->after('woo_admin_username');
            $table->text('images_base_url')->nullable()->after('woo_admin_app_password');

            $table->unsignedInteger('sync_batch_size')->default(50)->after('images_base_url');
            $table->string('sync_default_currency', 3)->default('EUR')->after('sync_batch_size');
            $table->boolean('sync_download_images')->default(true)->after('sync_default_currency');

            $table->string('smtp_host')->nullable()->after('sync_download_images');
            $table->unsignedInteger('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_user')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_user');
            $table->string('smtp_from')->nullable()->after('smtp_password');
            $table->string('smtp_to')->nullable()->after('smtp_from');
        });
    }

    public function down(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn([
                'wintouch_base_url',
                'wintouch_api_key',
                'wintouch_login_email',
                'wintouch_login_password',
                'woo_consumer_key',
                'woo_consumer_secret',
                'woo_api_version',
                'woo_admin_username',
                'woo_admin_app_password',
                'images_base_url',
                'sync_batch_size',
                'sync_default_currency',
                'sync_download_images',
                'smtp_host',
                'smtp_port',
                'smtp_user',
                'smtp_password',
                'smtp_from',
                'smtp_to',
            ]);
        });
    }
};

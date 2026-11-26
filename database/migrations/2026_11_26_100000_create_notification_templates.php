<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 160);
            $table->string('description', 500)->nullable();
            $table->string('event_type', 40);
            $table->string('channel', 20);
            $table->timestamps();
        });
        Schema::create('notification_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_template_id')->constrained();
            $table->unsignedInteger('number');
            $table->string('state', 20)->default('draft');
            $table->string('format', 10);
            $table->string('subject', 160)->nullable();
            $table->longText('body');
            $table->string('content_hash', 64);
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_template_id', 'number'], 'notification_version_number');
        });
        Schema::table('communication_rules', fn (Blueprint $table) => $table->foreignId('notification_template_id')->nullable()->constrained());
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->foreignId('notification_template_version_id')->nullable()->constrained();
            $table->longText('message_snapshot')->nullable();
        });
        (new Database\Seeders\ModulesAndPermissionsSeeder)->run();
        (new Database\Seeders\MenubarItemsSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('notification_template_version_id');
            $table->dropColumn('message_snapshot');
        });
        Schema::table('communication_rules', fn (Blueprint $table) => $table->dropConstrainedForeignId('notification_template_id'));
        Schema::dropIfExists('notification_template_versions');
        Schema::dropIfExists('notification_templates');
    }
};

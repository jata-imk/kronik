<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creditos', fn (Blueprint $table) => $table->foreignId('team_id')->nullable()->after('solicitud_id')->constrained('teams'));
        Schema::table('communication_events', fn (Blueprint $table) => $table->foreignId('team_id')->nullable()->after('event_type')->constrained('teams'));

        // Historical ownership is recovered only from the original disbursement audit.
        DB::table('creditos')->select('id')->orderBy('id')->chunkById(100, function ($credits) {
            foreach ($credits as $credit) {
                $teams = DB::table('activity_log')->where('event', 'credito.disbursed')
                    ->where('subject_type', App\Models\Credito::class)->where('subject_id', $credit->id)
                    ->whereNotNull('team_id')->distinct()->pluck('team_id');
                if ($teams->count() === 1) {
                    DB::table('creditos')->where('id', $credit->id)->update(['team_id' => $teams->first()]);
                    DB::table('communication_events')->where('credito_id', $credit->id)->update(['team_id' => $teams->first()]);
                }
            }
        });

        Schema::create('communication_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->string('channel', 20);
            $table->string('provider', 40)->nullable();
            $table->string('sender', 255)->nullable();
            $table->text('secret')->nullable();
            $table->string('eligibility', 30)->default('unverified');
            $table->string('eligibility_reference', 255)->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'channel']);
        });
        Schema::create('communication_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('channel', 20);
            $table->string('purpose', 30);
            $table->string('contact_hash', 64);
            $table->string('source', 80);
            $table->string('evidence_reference', 255);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'cliente_id', 'channel', 'purpose', 'revoked_at'], 'communication_consent_lookup');
        });
        Schema::create('communication_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->string('event_type', 40);
            $table->string('channel', 20);
            $table->boolean('enabled')->default(false);
            $table->string('timezone', 80)->default('America/Mexico_City');
            $table->time('window_start')->default('09:00:00');
            $table->time('window_end')->default('20:00:00');
            $table->unsignedSmallInteger('daily_limit')->default(1);
            $table->string('subject', 160)->nullable();
            $table->text('body');
            $table->unsignedInteger('template_version')->default(1);
            $table->string('external_template_name', 120)->nullable();
            $table->string('external_template_locale', 20)->nullable();
            $table->string('external_template_status', 30)->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'event_type', 'channel']);
        });
        Schema::create('communication_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('communication_event_id')->constrained('communication_events');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('credito_id')->constrained('creditos');
            $table->string('channel', 20);
            $table->string('recipient_hash', 64);
            $table->unsignedInteger('template_version');
            $table->string('status', 30)->default('prepared');
            $table->string('reason', 80)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['communication_event_id', 'channel']);
            $table->index(['team_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_deliveries');
        Schema::dropIfExists('communication_rules');
        Schema::dropIfExists('communication_consents');
        Schema::dropIfExists('communication_channels');
        Schema::table('communication_events', fn (Blueprint $table) => $table->dropConstrainedForeignId('team_id'));
        Schema::table('creditos', fn (Blueprint $table) => $table->dropConstrainedForeignId('team_id'));
    }
};

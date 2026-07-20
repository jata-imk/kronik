<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menubar_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('type')->default('route:name'); // menu, route:name, route:static, route:referer_fallback, route:dynamic, etc.
            $table->text('value')->nullable(); // depende de 'type', route:static: url, route:name: route_name, route:dynamic: array [{condition_type, condition_value, route_name, params}], etc.
            $table->json('params')->nullable(); // {"cliente": "{cliente}"}
            $table->foreignId('parent_id')->nullable()->constrained('menubar_items')->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menubar_items');
    }
};

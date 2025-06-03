<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menubar_item_module', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('menubar_item_id');
            $table->unsignedBigInteger('module_id');
            $table->json('routes'); // Ej: 'create', 'edit', 'show', 'index'

            $table->timestamps();

            $table->foreign('menubar_item_id')
                ->references('id')
                ->on('menubar_items')
                ->onDelete('cascade');

            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');

            $table->unique(['menubar_item_id', 'module_id'], 'unique_menubar_item_module');
        });
    }

    public function down()
    {
        Schema::dropIfExists('menubar_item_module');
    }
};

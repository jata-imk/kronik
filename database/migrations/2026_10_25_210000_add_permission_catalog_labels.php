<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['modules', 'permissions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('etiqueta')->nullable();
                $table->text('descripcion')->nullable();
            });
            DB::table($name)->orderBy('id')->each(function ($row) use ($name) {
                DB::table($name)->where('id', $row->id)->update($name === 'permissions'
                    ? PermissionCatalog::permission($row->name) : PermissionCatalog::module($row->name));
            });
        }
    }

    public function down(): void
    {
        foreach (['modules', 'permissions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['etiqueta', 'descripcion']));
        }
    }
};

<?php

use Database\Seeders\MenubarItemsSeeder;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new ModulesAndPermissionsSeeder)->run();
        (new MenubarItemsSeeder)->run();
    }

    public function down(): void
    {
        // Preserve role assignments and administrator-customized navigation.
    }
};

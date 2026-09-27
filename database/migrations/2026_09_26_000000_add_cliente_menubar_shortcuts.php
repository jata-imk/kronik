<?php

use Database\Seeders\ClienteAccesosMenubarSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new ClienteAccesosMenubarSeeder)->run();
    }

    public function down(): void
    {
        // Keep user-editable menu entries; deleting them could remove later customization.
    }
};

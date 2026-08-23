<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ModulesAndPermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(MenubarItemsSeeder::class);
        $this->call(SicsSeeder::class);
        $this->call(ConceptosComisionSeeder::class);
    }
}

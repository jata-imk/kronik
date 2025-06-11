<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Database\Seeders\PaisesSeeder;
use Database\Seeders\CatalogoCfdiSeeder;
use Database\Seeders\SepomexSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();

        User::factory()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(PaisesSeeder::class);
        $this->call(CatalogoCfdiSeeder::class);
        $this->call(SepomexSeeder::class);
        $this->call(ModulesAndPermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(MenubarItemsSeeder::class);
        $this->call(SicsSeeder::class);
    }
}

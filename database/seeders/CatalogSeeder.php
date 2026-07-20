<?php

namespace Database\Seeders;

use App\Models\CodigoPostal;
use App\Models\RegimenFiscal;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PaisesSeeder::class);

        if (RegimenFiscal::query()->doesntExist()) {
            $this->call(CatalogoCfdiSeeder::class);
        }

        if (CodigoPostal::query()->doesntExist()) {
            $this->call(SepomexSeeder::class);
        }
    }
}

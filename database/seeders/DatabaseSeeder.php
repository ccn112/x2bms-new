<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
        $this->call(DocsPermissionSeeder::class); // quyền module Tài liệu (sau khi role đã có)

        // Localization base catalog: locales, namespaces, UI keys/values, glossary,
        // notification templates and seed releases. Idempotent and production-safe.
        $this->call(LocalizationMasterSeeder::class);

        // Demo locale settings + sample content translations. Never runs in production.
        if (! app()->environment('production')) {
            $this->call(LocalizationDemoSeeder::class);
        }
    }
}

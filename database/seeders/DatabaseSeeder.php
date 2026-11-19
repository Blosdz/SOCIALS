<?php

namespace Database\Seeders;

use App\Models\Adviser;
use App\Models\MarketingContent;
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
        Adviser::updateOrCreate(
            ['email' => 'asesor@example.com'],
            ['name' => 'Asesor Demo', 'password' => 'password123'],
        );

        MarketingContent::query()->delete();
        MarketingContent::insert([
            [
                'title' => 'Protege lo que más importa',
                'description' => 'Conoce nuestros planes de protección y encuentra la opción ideal para ti.',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80',
                ]),
                'purchase_url' => 'https://example.com/planes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Tu próximo viaje, con respaldo',
                'description' => 'Viaja con tranquilidad. Revisa las coberturas disponibles y cotiza en línea.',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80',
                ]),
                'purchase_url' => 'https://example.com/cotizar',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

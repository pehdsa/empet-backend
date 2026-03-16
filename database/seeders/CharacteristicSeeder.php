<?php

namespace Database\Seeders;

use App\Enums\CharacteristicCategory;
use App\Models\Characteristic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CharacteristicSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $characteristics = [
            [
                'category' => CharacteristicCategory::Marking,
                'names' => [
                    'Mancha no focinho',
                    'Orelha cortada',
                    'Pata branca',
                    'Cicatriz visível',
                    'Mancha nos olhos',
                    'Heterocromia',
                    'Cauda curta',
                    'Manchas no corpo',
                ],
            ],
            [
                'category' => CharacteristicCategory::Coat,
                'names' => [
                    'Pelo longo',
                    'Pelo curto',
                    'Pelo médio',
                    'Bicolor',
                    'Tricolor',
                    'Pelo crespo',
                    'Pelo liso',
                    'Pelo duro/arame',
                    'Rajado (tabby)',
                    'Merle',
                    'Albino',
                ],
            ],
            [
                'category' => CharacteristicCategory::Behavior,
                'names' => [
                    'Usa coleira',
                    'Usa roupa',
                    'Muito dócil',
                    'Assustado com estranhos',
                    'Agressivo quando acuado',
                    'Sociável com outros animais',
                    'Treinado para comandos',
                    'Castrado',
                ],
            ],
            [
                'category' => CharacteristicCategory::Identification,
                'names' => [
                    'Microchip',
                    'Tatuagem de identificação',
                    'Plaqueta na coleira',
                    'Registro em órgão oficial',
                ],
            ],
        ];

        foreach ($characteristics as $group) {
            foreach ($group['names'] as $name) {
                Characteristic::updateOrCreate(
                    ['name' => $name, 'category' => $group['category']],
                    ['is_active' => true],
                );
            }
        }
    }
}

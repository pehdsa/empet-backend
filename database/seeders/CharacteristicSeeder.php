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
            CharacteristicCategory::Marking => [
                'Mancha no focinho',
                'Orelha cortada',
                'Pata branca',
                'Cicatriz visível',
                'Mancha nos olhos',
                'Heterocromia',
                'Cauda curta',
                'Manchas no corpo',
            ],
            CharacteristicCategory::Coat => [
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
            CharacteristicCategory::Behavior => [
                'Usa coleira',
                'Usa roupa',
                'Muito dócil',
                'Assustado com estranhos',
                'Agressivo quando acuado',
                'Sociável com outros animais',
                'Treinado para comandos',
                'Castrado',
            ],
            CharacteristicCategory::Identification => [
                'Microchip',
                'Tatuagem de identificação',
                'Plaqueta na coleira',
                'Registro em órgão oficial',
            ],
        ];

        foreach ($characteristics as $category => $names) {
            foreach ($names as $name) {
                Characteristic::updateOrCreate(
                    ['name' => $name, 'category' => $category],
                    ['is_active' => true],
                );
            }
        }
    }
}

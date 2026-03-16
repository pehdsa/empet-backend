<?php

namespace Database\Seeders;

use App\Enums\PetSpecies;
use App\Models\Breed;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BreedSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $breeds = [
            [
                'species' => PetSpecies::Dog,
                'names' => [
                    'Akita',
                    'American Bully',
                    'American Staffordshire Terrier',
                    'Basset Hound',
                    'Beagle',
                    'Bernese Mountain Dog',
                    'Bichon Frisé',
                    'Border Collie',
                    'Boston Terrier',
                    'Boxer',
                    'Buldogue Francês',
                    'Buldogue Inglês',
                    'Bull Terrier',
                    'Cane Corso',
                    'Cavalier King Charles Spaniel',
                    'Chihuahua',
                    'Chow Chow',
                    'Cocker Spaniel Americano',
                    'Cocker Spaniel Inglês',
                    'Dachshund',
                    'Dálmata',
                    'Doberman',
                    'Dogo Argentino',
                    'Fila Brasileiro',
                    'Fox Paulistinha',
                    'Golden Retriever',
                    'Husky Siberiano',
                    'Jack Russell Terrier',
                    'Labrador Retriever',
                    'Lhasa Apso',
                    'Lulu da Pomerânia',
                    'Maltês',
                    'Malinois (Pastor Belga)',
                    'Mastiff Inglês',
                    'Mastim Tibetano',
                    'Pinscher Miniatura',
                    'Pit Bull',
                    'Pointer Inglês',
                    'Poodle',
                    'Pug',
                    'Rottweiler',
                    'Samoieda',
                    'Schnauzer',
                    'Shar-Pei',
                    'Shiba Inu',
                    'Shih Tzu',
                    'Staffordshire Bull Terrier',
                    'Weimaraner',
                    'West Highland White Terrier',
                    'Whippet',
                    'Yorkshire Terrier',
                    'SRD (Sem Raça Definida)',
                ],
            ],
            [
                'species' => PetSpecies::Cat,
                'names' => [
                    'Abissínio',
                    'American Shorthair',
                    'Angorá',
                    'Bengal',
                    'British Shorthair',
                    'Burmês',
                    'Chartreux',
                    'Cornish Rex',
                    'Devon Rex',
                    'Exótico',
                    'Himalaia',
                    'Maine Coon',
                    'Munchkin',
                    'Norueguês da Floresta',
                    'Persa',
                    'Ragdoll',
                    'Russian Blue',
                    'Scottish Fold',
                    'Siamês',
                    'Singapura',
                    'Somali',
                    'Sphynx',
                    'Tonquinês',
                    'Turkish Van',
                    'SRD (Sem Raça Definida)',
                ],
            ],
        ];

        foreach ($breeds as $group) {
            foreach ($group['names'] as $name) {
                Breed::updateOrCreate(
                    ['name' => $name, 'species' => $group['species']],
                    ['is_active' => true],
                );
            }
        }
    }
}

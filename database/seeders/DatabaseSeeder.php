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
        $this->call(BreedSeeder::class);
        $this->call(CharacteristicSeeder::class);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])->toArray()
        );

        $this->call(PetSeeder::class);
    }
}

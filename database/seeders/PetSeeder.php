<?php

namespace Database\Seeders;

use App\Enums\CharacteristicCategory;
use App\Enums\PetMatchStatus;
use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Models\Breed;
use App\Models\Characteristic;
use App\Models\Pet;
use App\Models\PetMatch;
use App\Models\PetPhoto;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PetSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Emails dos usuários controlados por este seeder.
     *
     * @var list<string>
     */
    private const CONTROLLED_EMAILS = [
        'test@example.com',
        'owner2@example.com',
        'witness@example.com',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->cleanup();

        [$dogBreeds, $catBreeds, $characteristics] = $this->loadPrerequisites();

        [$testUser, $owner2, $witness] = $this->createUsers();

        $pets = $this->createPets($testUser, $owner2, $witness, $dogBreeds, $catBreeds);

        $this->createPhotos($pets);

        $this->attachCharacteristics($pets, $characteristics);

        $reports = $this->createReports($pets, $testUser, $owner2, $witness);

        // Soft delete Pet 7 AFTER creating its report (R5)
        $pets[7]->delete();

        $this->createSightings($reports, $testUser, $owner2, $witness);

        $this->createMatches($reports, $pets);
    }

    /**
     * Remove all data controlled by this seeder (scoped to controlled users).
     */
    private function cleanup(): void
    {
        $userIds = User::withTrashed()
            ->whereIn('email', self::CONTROLLED_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $petIds = Pet::withTrashed()
            ->whereIn('user_id', $userIds)
            ->pluck('id');

        $reportIds = $petIds->isNotEmpty()
            ? PetReport::withTrashed()->whereIn('pet_id', $petIds)->pluck('id')
            : collect();

        // Delete in FK-safe order
        if ($reportIds->isNotEmpty()) {
            PetMatch::query()
                ->where(function ($q) use ($reportIds, $petIds) {
                    $q->whereIn('report_id', $reportIds)
                        ->orWhereIn('matched_pet_id', $petIds);
                })
                ->delete();

            PetSighting::withTrashed()
                ->whereIn('report_id', $reportIds)
                ->forceDelete();

            PetReport::withTrashed()
                ->whereIn('pet_id', $petIds)
                ->forceDelete();
        }

        if ($petIds->isNotEmpty()) {
            PetPhoto::query()
                ->whereIn('pet_id', $petIds)
                ->delete();

            DB::table('pet_characteristics')
                ->whereIn('pet_id', $petIds)
                ->delete();

            Pet::withTrashed()
                ->whereIn('user_id', $userIds)
                ->forceDelete();
        }

        UserPhone::query()
            ->whereIn('user_id', $userIds)
            ->delete();

        $auxiliaryEmails = ['owner2@example.com', 'witness@example.com'];
        User::withTrashed()
            ->whereIn('email', $auxiliaryEmails)
            ->forceDelete();
    }

    /**
     * Load breeds and characteristics from the database.
     *
     * @return array{Collection<int, Breed>, Collection<int, Breed>, array<string, Characteristic>}
     */
    private function loadPrerequisites(): array
    {
        $dogBreeds = Breed::query()
            ->where('species', PetSpecies::Dog)
            ->limit(5)
            ->get();

        $catBreeds = Breed::query()
            ->where('species', PetSpecies::Cat)
            ->limit(5)
            ->get();

        if ($dogBreeds->isEmpty() || $catBreeds->isEmpty()) {
            throw new RuntimeException(
                'PetSeeder requires breeds in the database. Run BreedSeeder first.'
            );
        }

        $characteristics = [];
        $requiredCategories = [
            CharacteristicCategory::Marking,
            CharacteristicCategory::Coat,
            CharacteristicCategory::Behavior,
            CharacteristicCategory::Identification,
        ];

        foreach ($requiredCategories as $category) {
            $characteristic = Characteristic::query()
                ->where('category', $category)
                ->first();

            if (! $characteristic) {
                throw new RuntimeException(
                    "PetSeeder requires characteristics for category [{$category->value}]. Run CharacteristicSeeder first."
                );
            }

            $characteristics[$category->value] = $characteristic;
        }

        return [$dogBreeds, $catBreeds, $characteristics];
    }

    /**
     * Create or fetch the 3 controlled users with their phones.
     *
     * @return array{User, User, User}
     */
    private function createUsers(): array
    {
        $testUser = User::withTrashed()->where('email', 'test@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        if ($testUser->trashed()) {
            $testUser->restore();
        }

        $owner2 = User::withTrashed()->where('email', 'owner2@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Owner Two',
                'email' => 'owner2@example.com',
            ]);

        if ($owner2->trashed()) {
            $owner2->restore();
        }

        $witness = User::withTrashed()->where('email', 'witness@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Witness User',
                'email' => 'witness@example.com',
            ]);

        if ($witness->trashed()) {
            $witness->restore();
        }

        // Create phones for all controlled users
        UserPhone::factory()->primary()->create([
            'user_id' => $testUser->id,
            'phone' => '+5511999990001',
        ]);

        UserPhone::factory()->primary()->whatsapp()->create([
            'user_id' => $owner2->id,
            'phone' => '+5511999990002',
        ]);

        UserPhone::factory()->primary()->create([
            'user_id' => $witness->id,
            'phone' => '+5511999990003',
        ]);

        return [$testUser, $owner2, $witness];
    }

    /**
     * Create all pets for the 3 users.
     *
     * @param  Collection<int, Breed>  $dogBreeds
     * @param  Collection<int, Breed>  $catBreeds
     * @return array<int, Pet>
     */
    private function createPets(
        User $testUser,
        User $owner2,
        User $witness,
        Collection $dogBreeds,
        Collection $catBreeds,
    ): array {
        // Pet 1: Cachorro completo (happy path)
        $pet1 = Pet::factory()
            ->dog()
            ->withSecondaryBreed()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Rex',
                'size' => PetSize::Medium,
                'sex' => PetSex::Male,
                'breed_id' => $dogBreeds->first()->id,
                'primary_color' => 'caramelo',
                'notes' => 'Muito brincalhão, usa coleira azul.',
            ]);

        // Pet 2: Gato completo
        $pet2 = Pet::factory()
            ->cat()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Mimi',
                'size' => PetSize::Small,
                'sex' => PetSex::Female,
                'breed_id' => $catBreeds->first()->id,
                'primary_color' => 'branco',
                'notes' => 'Gata dócil, castrada.',
            ]);

        // Pet 3: Pet sem fotos
        $pet3 = Pet::factory()
            ->dog()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Thor',
                'size' => PetSize::Large,
                'sex' => PetSex::Male,
                'breed_id' => $dogBreeds->skip(1)->first()->id,
                'primary_color' => 'preto',
            ]);

        // Pet 4: Pet sem características
        $pet4 = Pet::factory()
            ->cat()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Frajola',
                'size' => PetSize::Medium,
                'sex' => PetSex::Unknown,
                'breed_id' => $catBreeds->skip(1)->first()->id,
                'primary_color' => 'preto e branco',
            ]);

        // Pet 5: Pet inativo
        $pet5 = Pet::factory()
            ->dog()
            ->inactive()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Mel',
                'size' => PetSize::Small,
                'sex' => PetSex::Female,
                'breed_id' => $dogBreeds->skip(2)->first()->id,
                'primary_color' => 'dourado',
            ]);

        // Pet 6: Pet sem raça definida (Pet::create direto para evitar afterCreating da factory)
        $pet6 = Pet::create([
            'user_id' => $testUser->id,
            'name' => 'Bolinha',
            'species' => PetSpecies::Dog,
            'size' => PetSize::Medium,
            'sex' => PetSex::Male,
            'breed_id' => null,
            'secondary_breed_id' => null,
            'breed_description' => 'Mestiço de poodle com vira-lata, porte médio, pelo cacheado.',
            'primary_color' => 'cinza',
            'is_active' => true,
        ]);

        // Pet 7: Pet que será soft-deleted (após criar report)
        $pet7 = Pet::factory()
            ->cat()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'Luna',
                'size' => PetSize::Large,
                'sex' => PetSex::Female,
                'breed_id' => $catBreeds->skip(2)->first()->id,
            ]);

        // Pet 8: Cachorro do owner2 (matched pet)
        $pet8 = Pet::factory()
            ->dog()
            ->create([
                'user_id' => $owner2->id,
                'name' => 'Max',
                'size' => PetSize::Medium,
                'sex' => PetSex::Male,
                'breed_id' => $dogBreeds->first()->id,
                'primary_color' => 'caramelo',
                'notes' => 'Parece muito com um caramelo típico.',
            ]);

        // Pet 9: Gato do owner2 (report LOST adicional para mapa)
        $pet9 = Pet::factory()
            ->cat()
            ->create([
                'user_id' => $owner2->id,
                'name' => 'Pipoca',
                'size' => PetSize::Small,
                'sex' => PetSex::Female,
                'breed_id' => $catBreeds->first()->id,
                'primary_color' => 'laranja',
            ]);

        // Pet 10: Cachorro do witness (cross-owner match)
        $pet10 = Pet::factory()
            ->dog()
            ->create([
                'user_id' => $witness->id,
                'name' => 'Bob',
                'size' => PetSize::Large,
                'sex' => PetSex::Male,
                'breed_id' => $dogBreeds->skip(1)->first()->id,
                'primary_color' => 'marrom',
            ]);

        return [
            1 => $pet1,
            2 => $pet2,
            3 => $pet3,
            4 => $pet4,
            5 => $pet5,
            6 => $pet6,
            7 => $pet7,
            8 => $pet8,
            9 => $pet9,
            10 => $pet10,
        ];
    }

    /**
     * Create photos for pets that should have them.
     *
     * @param  array<int, Pet>  $pets
     */
    private function createPhotos(array $pets): void
    {
        $photoConfig = [
            1 => 3, // Pet 1: 3 fotos
            2 => 2, // Pet 2: 2 fotos
            // Pet 3: 0 fotos
            4 => 1, // Pet 4: 1 foto
            5 => 1, // Pet 5: 1 foto
            6 => 1, // Pet 6: 1 foto
            // Pet 7: 0 fotos
            8 => 2, // Pet 8: 2 fotos
            9 => 1, // Pet 9: 1 foto
            10 => 1, // Pet 10: 1 foto
        ];

        foreach ($photoConfig as $petNumber => $count) {
            $pet = $pets[$petNumber];
            for ($position = 0; $position < $count; $position++) {
                PetPhoto::factory()
                    ->position($position)
                    ->create([
                        'pet_id' => $pet->id,
                        'path' => "pets/seed/pet-{$petNumber}-{$position}.jpg",
                    ]);
            }
        }
    }

    /**
     * Attach characteristics to pets via pivot table.
     *
     * @param  array<int, Pet>  $pets
     * @param  array<string, Characteristic>  $characteristics
     */
    private function attachCharacteristics(array $pets, array $characteristics): void
    {
        // Pet 1: 3 characteristics (Marking, Coat, Behavior)
        $pets[1]->characteristics()->attach([
            $characteristics[CharacteristicCategory::Marking->value]->id,
            $characteristics[CharacteristicCategory::Coat->value]->id,
            $characteristics[CharacteristicCategory::Behavior->value]->id,
        ]);

        // Pet 2: 2 characteristics (Coat, Identification)
        $pets[2]->characteristics()->attach([
            $characteristics[CharacteristicCategory::Coat->value]->id,
            $characteristics[CharacteristicCategory::Identification->value]->id,
        ]);

        // Pet 3: 1 characteristic (Behavior)
        $pets[3]->characteristics()->attach([
            $characteristics[CharacteristicCategory::Behavior->value]->id,
        ]);

        // Pet 6: 1 characteristic (Marking)
        $pets[6]->characteristics()->attach([
            $characteristics[CharacteristicCategory::Marking->value]->id,
        ]);
    }

    /**
     * Create reports with PostGIS locations.
     *
     * @param  array<int, Pet>  $pets
     * @return array<string, PetReport>
     */
    private function createReports(
        array $pets,
        User $testUser,
        User $owner2,
        User $witness,
    ): array {
        // R1: Pet 1, Lost, Centro de Campo Grande
        $r1 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[1]->id,
                'user_id' => $testUser->id,
                'location' => $this->geographyPoint(-54.6156, -20.4697),
                'address_hint' => 'Praça Ary Coelho, Centro, Campo Grande',
                'description' => 'Escapou durante passeio no centro. Estava sem coleira.',
                'lost_at' => now()->subDays(5),
            ]);

        // R2: Pet 2, Found, Parque das Nações Indígenas
        $r2 = PetReport::factory()
            ->found()
            ->create([
                'pet_id' => $pets[2]->id,
                'user_id' => $testUser->id,
                'location' => $this->geographyPoint(-54.5836, -20.4582),
                'address_hint' => 'Parque das Nações Indígenas, Campo Grande',
                'description' => 'Encontrada próximo ao parque, estava assustada.',
                'found_at' => now()->subDays(2),
            ]);

        // R3: Pet 1, Cancelled, Jardim dos Estados
        $r3 = PetReport::factory()
            ->cancelled()
            ->create([
                'pet_id' => $pets[1]->id,
                'user_id' => $testUser->id,
                'location' => $this->geographyPoint(-54.5980, -20.4620),
                'address_hint' => 'Jardim dos Estados, Campo Grande',
                'description' => 'Report antigo cancelado, pet já foi encontrado.',
                'lost_at' => now()->subDays(30),
            ]);

        // R4: Pet 8 (owner2), Lost, Tiradentes
        $r4 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[8]->id,
                'user_id' => $owner2->id,
                'location' => $this->geographyPoint(-54.6350, -20.4450),
                'address_hint' => 'Tiradentes, Campo Grande',
                'description' => 'Fugiu pelo portão aberto. Muito dócil.',
                'lost_at' => now()->subDay(),
            ]);

        // R5: Pet 7 (será soft-deleted), Lost, Vila Planalto
        $r5 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[7]->id,
                'user_id' => $testUser->id,
                'location' => $this->geographyPoint(-54.5700, -20.4850),
                'address_hint' => 'Vila Planalto, Campo Grande',
                'description' => 'Desapareceu há dias, sem notícias.',
                'lost_at' => now()->subDays(10),
            ]);

        // R6: Pet 3, Lost, Carandá Bosque
        $r6 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[3]->id,
                'user_id' => $testUser->id,
                'location' => $this->geographyPoint(-54.5950, -20.4920),
                'address_hint' => 'Carandá Bosque, Campo Grande',
                'description' => 'Sumiu durante a noite, portão ficou aberto.',
                'lost_at' => now()->subDays(3),
            ]);

        // R7: Pet 9 (owner2), Lost, Rita Vieira
        $r7 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[9]->id,
                'user_id' => $owner2->id,
                'location' => $this->geographyPoint(-54.5520, -20.4730),
                'address_hint' => 'Rita Vieira, Campo Grande',
                'description' => 'Gata fugiu pela janela. Usar ração para atrair.',
                'lost_at' => now()->subDays(2),
            ]);

        // R8: Pet 10 (witness), Lost, Coronel Antonino
        $r8 = PetReport::factory()
            ->lost()
            ->create([
                'pet_id' => $pets[10]->id,
                'user_id' => $witness->id,
                'location' => $this->geographyPoint(-54.6280, -20.4530),
                'address_hint' => 'Coronel Antonino, Campo Grande',
                'description' => 'Cachorro grande, pode assustar mas é dócil.',
                'lost_at' => now()->subDays(4),
            ]);

        return [
            'R1' => $r1,
            'R2' => $r2,
            'R3' => $r3,
            'R4' => $r4,
            'R5' => $r5,
            'R6' => $r6,
            'R7' => $r7,
            'R8' => $r8,
        ];
    }

    /**
     * Create sightings with PostGIS locations.
     *
     * @param  array<string, PetReport>  $reports
     */
    private function createSightings(
        array $reports,
        User $testUser,
        User $owner2,
        User $witness,
    ): void {
        // S1: R1, witness, sem share_phone, ativo
        PetSighting::factory()->create([
            'report_id' => $reports['R1']->id,
            'user_id' => $witness->id,
            'location' => $this->geographyPoint(-54.6100, -20.4750),
            'address_hint' => 'Av. Afonso Pena, Centro, Campo Grande',
            'description' => 'Vi um cachorro parecido perto da Afonso Pena.',
            'sighted_at' => now()->subDays(4),
            'share_phone' => false,
        ]);

        // S2: R1, owner2, com share_phone, ativo
        PetSighting::factory()->withSharePhone()->create([
            'report_id' => $reports['R1']->id,
            'user_id' => $owner2->id,
            'location' => $this->geographyPoint(-54.5900, -20.4550),
            'address_hint' => 'Chácara Cachoeira, Campo Grande',
            'description' => 'Cachorro caramelo visto na praça.',
            'sighted_at' => now()->subDays(3),
        ]);

        // S3: R1, witness, sem share_phone, INATIVO
        PetSighting::factory()->inactive()->create([
            'report_id' => $reports['R1']->id,
            'user_id' => $witness->id,
            'location' => $this->geographyPoint(-54.6050, -20.4650),
            'address_hint' => 'Monte Castelo, Campo Grande',
            'description' => 'Avistamento descartado, era outro animal.',
            'sighted_at' => now()->subDays(4),
            'share_phone' => false,
        ]);

        // S4: R4, test user, com share_phone, ativo
        PetSighting::factory()->withSharePhone()->create([
            'report_id' => $reports['R4']->id,
            'user_id' => $testUser->id,
            'location' => $this->geographyPoint(-54.6400, -20.4480),
            'address_hint' => 'Pioneiros, Campo Grande',
            'description' => 'Vi um cachorro parecido na rua.',
            'sighted_at' => now()->subHours(12),
        ]);
    }

    /**
     * Create matches between reports and pets.
     *
     * @param  array<string, PetReport>  $reports
     * @param  array<int, Pet>  $pets
     */
    private function createMatches(array $reports, array $pets): void
    {
        // M1: R1 (Pet 1, test) -> Pet 8 (owner2), Pending, score alto
        PetMatch::factory()->create([
            'report_id' => $reports['R1']->id,
            'matched_pet_id' => $pets[8]->id,
            'score' => 85.50,
            'distance_meters' => 1200.00,
            'status' => PetMatchStatus::Pending,
        ]);

        // M2: R4 (Pet 8, owner2) -> Pet 1 (test), Confirmed, cross-owner reverso
        PetMatch::factory()->confirmed()->create([
            'report_id' => $reports['R4']->id,
            'matched_pet_id' => $pets[1]->id,
            'score' => 72.30,
            'distance_meters' => 1200.00,
        ]);

        // M3: R1 (Pet 1, test) -> Pet 10 (witness), Dismissed, score baixo
        PetMatch::factory()->dismissed()->create([
            'report_id' => $reports['R1']->id,
            'matched_pet_id' => $pets[10]->id,
            'score' => 30.00,
            'distance_meters' => 15000.00,
        ]);
    }

    /**
     * Create a PostGIS geography point expression.
     */
    private function geographyPoint(float $lng, float $lat): Expression
    {
        return DB::raw(sprintf(
            'ST_SetSRID(ST_MakePoint(%s, %s), 4326)::geography',
            number_format($lng, 6, '.', ''),
            number_format($lat, 6, '.', ''),
        ));
    }
}

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
        // The test login has a well-known password; never create it on a real host.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Skipping the test user outside local and testing. Use `php artisan user:create`.');

            return;
        }

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if (app()->environment('local')) {
            $this->call(SampleEntriesSeeder::class, parameters: ['author' => $user]);
        }
    }
}

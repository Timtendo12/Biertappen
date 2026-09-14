<?php

namespace Database\Seeders;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BaseDeckSeeder::class);

        // Local development accounts only. Guarded so a production seed run
        // cannot create a known-password administrator.
        if (! app()->environment('local')) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'admin@biertappen.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'locale' => 'nl',
            ],
        );

        $player = User::firstOrCreate(
            ['email' => 'speler@biertappen.test'],
            [
                'name' => 'Speler',
                'password' => 'password',
                'role' => User::ROLE_USER,
                'email_verified_at' => now(),
                'locale' => 'nl',
            ],
        );

        $player->entitlements()->firstOrCreate(
            ['type' => Entitlement::TYPE_DECK_CREATOR],
            [
                'status' => Entitlement::STATUS_ACTIVE,
                'source' => Entitlement::SOURCE_MANUAL,
                'granted_at' => now(),
            ],
        );
    }
}

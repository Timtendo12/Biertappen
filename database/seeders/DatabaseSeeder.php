<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Pack;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         User::factory(1)->create();
         Pack::create([
             'id' => 1,
             'uuid' => '289aaceb-6edc-4f26-949c-4fd0ffbddacf',
             'name' => 'test Pack',
             'description' => 'Dit is een test description van meer dan 130 karakters lang. Whahaouw ik weet niet wat ik hier in moet typen want dit is erg lang. Hoezo past dit hier allemaal?',
             'creator' => 'Biertappen',
             'image' => '/storage/packs/4.png',
         ]);
    }
}

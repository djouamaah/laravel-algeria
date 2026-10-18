<?php

namespace Djouamaah\Algeria\Tests;

use Djouamaah\Algeria\Database\AlgeriaSeeder;
use Djouamaah\Algeria\Models\Commune;
use Djouamaah\Algeria\Models\Daira;
use Djouamaah\Algeria\Models\PostalCode;
use Djouamaah\Algeria\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_command_inserts_everything(): void
    {
        $this->artisan('algeria:seed')->assertSuccessful();

        $this->assertSame(69, Wilaya::count());
        $this->assertSame(549, Daira::count());
        $this->assertSame(1541, Commune::count());

        $wilaya = Wilaya::code(68)->with('parent')->first();
        $this->assertSame('Bou Saâda', $wilaya->name);
        $this->assertSame("M'Sila", $wilaya->parent->name);
        $this->assertSame(8, $wilaya->dairas()->count());
        $this->assertSame(23, $wilaya->communes()->count());
        $this->assertSame(1, Wilaya::find(28)->children()->count());
    }

    public function test_postal_codes_are_seeded(): void
    {
        $this->artisan('algeria:seed')->assertSuccessful();

        $this->assertSame(3752, PostalCode::count());
        $this->assertSame(1505, Commune::whereNotNull('postal_code')->count());

        $commune = Commune::where('postal_code', '16000')->first();
        $this->assertSame('Alger Centre', $commune->name);
        $this->assertTrue($commune->postalCodes->contains('code', '16000'));
        $this->assertSame('Alger Centre', PostalCode::find('16000')->commune->name);
        $this->assertSame('01000', PostalCode::find('01000')->code);

        $this->artisan('algeria:seed', ['--division' => 58])->assertSuccessful();
        $this->assertSame(3752, PostalCode::count());
        $this->assertSame(28, PostalCode::find('28001')->commune->wilaya_id);
    }

    public function test_install_command(): void
    {
        $this->artisan('algeria:install', ['--no-config' => true, '--division' => 58])->assertSuccessful();

        $this->assertSame(58, Wilaya::count());
    }

    public function test_seeding_is_idempotent_and_switches_division(): void
    {
        $this->artisan('algeria:seed')->assertSuccessful();
        $this->artisan('algeria:seed')->assertSuccessful();
        $this->assertSame(1541, Commune::count());

        $this->artisan('algeria:seed', ['--division' => 58])->assertSuccessful();
        $this->assertSame(58, Wilaya::count());
        $this->assertSame(548, Daira::count());
        $this->assertSame(1541, Commune::count());
        $this->assertSame(0, Commune::where('wilaya_id', '>', 58)->count());

        $this->artisan('algeria:seed', ['--division' => 69])->assertSuccessful();
        $this->assertSame(69, Wilaya::count());
        $this->assertSame(549, Daira::count());

        $this->artisan('algeria:seed', ['--division' => 50])->assertFailed();
    }

    public function test_seeder_class_and_localized_names(): void
    {
        $this->seed(AlgeriaSeeder::class);

        $commune = Commune::search('Bejaia')->where('wilaya_id', 6)->first();
        $this->assertSame(6, $commune->daira->wilaya->id);

        app()->setLocale('ar');
        $this->assertSame('بجاية', $commune->localized_name);
        app()->setLocale('fr');
        $this->assertSame('Bejaia', $commune->localized_name);
        $this->assertArrayHasKey('localized_name', $commune->toArray());
    }
}

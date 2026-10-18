<?php

namespace Djouamaah\Algeria\Tests;

use Djouamaah\Algeria\Algeria;
use Djouamaah\Algeria\Facades\Algeria as AlgeriaFacade;
use Djouamaah\Algeria\Rules\ValidCommune;
use Djouamaah\Algeria\Rules\ValidDaira;
use Djouamaah\Algeria\Rules\ValidPostalCode;
use Djouamaah\Algeria\Rules\ValidWilaya;
use Illuminate\Support\Facades\Validator;

class DatasetTest extends TestCase
{
    public function test_division_69_counts(): void
    {
        $algeria = new Algeria(69);

        $this->assertCount(69, $algeria->wilayas());
        $this->assertCount(549, $algeria->dairas());
        $this->assertCount(1541, $algeria->communes());
    }

    public function test_division_58_counts(): void
    {
        $algeria = new Algeria(58);

        $this->assertCount(58, $algeria->wilayas());
        $this->assertCount(548, $algeria->dairas());
        $this->assertCount(1541, $algeria->communes());
    }

    public function test_every_row_is_consistent(): void
    {
        foreach ([58, 69] as $division) {
            $algeria = new Algeria($division);

            foreach ($algeria->communes() as $commune) {
                $daira = $algeria->daira($commune['daira_id']);
                $this->assertNotNull($daira, "commune {$commune['id']}");
                $this->assertSame($daira['wilaya_id'], $commune['wilaya_id'], "commune {$commune['id']}");
                $this->assertNotSame('', trim($commune['name']));
                $this->assertNotSame('', trim($commune['name_ar']));
            }

            foreach ($algeria->dairas() as $daira) {
                $this->assertNotNull($algeria->wilaya($daira['wilaya_id']));
                $this->assertNotEmpty($algeria->communesOfDaira($daira['id']), "daira {$daira['id']} has no commune");
            }
        }
    }

    public function test_new_wilayas_commune_counts(): void
    {
        $expected = [59 => 12, 60 => 8, 61 => 5, 62 => 4, 63 => 4, 64 => 6, 65 => 10, 66 => 8, 67 => 21, 68 => 23, 69 => 7];

        foreach ($expected as $code => $count) {
            $this->assertCount($count, AlgeriaFacade::communes($code), "wilaya {$code}");
        }

        $this->assertSame(28, AlgeriaFacade::wilaya(68)['parent_id']);
        $this->assertSame('بوسعادة', AlgeriaFacade::wilaya('68')['name_ar']);
    }

    public function test_commune_moves_between_divisions(): void
    {
        $bouSaada = (new Algeria(69))->search('Bou Saada')->firstWhere('name', 'Bou Saada');

        $this->assertSame(68, $bouSaada['wilaya_id']);
        $this->assertSame(28, $bouSaada['legacy_wilaya_id']);
        $this->assertSame(28, (new Algeria(58))->commune($bouSaada['id'])['wilaya_id']);
    }

    public function test_lookup_by_code_and_search(): void
    {
        $this->assertSame('Alger', AlgeriaFacade::wilaya('16')['name']);
        $this->assertSame('Alger', AlgeriaFacade::wilaya('016')['name']);
        $this->assertNull(AlgeriaFacade::wilaya(70));
        $this->assertNull(AlgeriaFacade::wilaya('abc'));

        $this->assertTrue(AlgeriaFacade::search('بجاية')->contains('name', 'Bejaia'));
        $this->assertTrue(AlgeriaFacade::search('tizi ouzou')->contains('name', 'Tizi Ouzou'));
        $this->assertTrue(AlgeriaFacade::search('BÉJAÏA')->contains('name', 'Bejaia'));
    }

    public function test_postal_codes(): void
    {
        $this->assertCount(3752, AlgeriaFacade::postalCodes());
        $this->assertCount(1505, AlgeriaFacade::communes()->whereNotNull('postal_code'));

        $algerCentre = AlgeriaFacade::communeByPostalCode('16000');
        $this->assertSame('Alger Centre', $algerCentre['name']);
        $this->assertSame('16000', $algerCentre['postal_code']);
        $this->assertSame($algerCentre['id'], AlgeriaFacade::postalCode(16000)['commune_id']);
        $this->assertSame('01000', AlgeriaFacade::postalCode('1000')['code']);
        $this->assertNull(AlgeriaFacade::postalCode('99999'));
        $this->assertNull(AlgeriaFacade::postalCode('16 000x'));

        // Bou Saâda (wilaya 68 since 2026) keeps the postal prefix of M'Sila (28).
        $this->assertSame(68, AlgeriaFacade::communeByPostalCode('28001')['wilaya_id']);

        foreach ([58, 69] as $division) {
            $algeria = new Algeria($division);

            foreach ($algeria->communes()->whereNotNull('postal_code') as $commune) {
                $this->assertSame($commune['id'], $algeria->postalCode($commune['postal_code'])['commune_id']);
            }
            foreach ($algeria->postalCodes() as $postalCode) {
                $this->assertMatchesRegularExpression('/^\d{5}$/', $postalCode['code']);
                $this->assertNotNull($algeria->commune($postalCode['commune_id']));
            }
        }

        $this->assertTrue(AlgeriaFacade::postalCodes($algerCentre['id'])->contains('code', '16000'));
    }

    public function test_postal_code_rule(): void
    {
        $algerCentre = AlgeriaFacade::communeByPostalCode('16000');
        $passes = fn ($value, $rule) => Validator::make(['p' => $value], ['p' => [$rule]])->passes();

        $this->assertTrue($passes('16000', new ValidPostalCode));
        $this->assertTrue($passes(16000, new ValidPostalCode));
        $this->assertFalse($passes('99999', new ValidPostalCode));
        $this->assertTrue($passes('16000', new ValidPostalCode(commune: $algerCentre['id'])));
        $this->assertFalse($passes('31000', new ValidPostalCode(commune: $algerCentre['id'])));
    }

    public function test_validation_rules(): void
    {
        $bejaia = AlgeriaFacade::search('Bejaia', 6)->firstWhere('name', 'Bejaia');

        $passes = fn (array $data, array $rules) => Validator::make($data, $rules)->passes();

        $this->assertTrue($passes(['w' => 6], ['w' => [new ValidWilaya]]));
        $this->assertFalse($passes(['w' => 99], ['w' => [new ValidWilaya]]));
        $this->assertTrue($passes(['c' => $bejaia['id']], ['c' => [new ValidCommune(wilaya: 6)]]));
        $this->assertFalse($passes(['c' => $bejaia['id']], ['c' => [new ValidCommune(wilaya: 16)]]));
        $this->assertTrue($passes(['c' => $bejaia['id']], ['c' => [new ValidCommune(daira: $bejaia['daira_id'])]]));
        $this->assertTrue($passes(['d' => $bejaia['daira_id']], ['d' => [new ValidDaira(6)]]));
        $this->assertFalse($passes(['d' => $bejaia['daira_id']], ['d' => [new ValidDaira(16)]]));
        $this->assertFalse($passes(['c' => ['x']], ['c' => [new ValidCommune]]));

        app()->setLocale('ar');
        $errors = Validator::make(['w' => 99], ['w' => [new ValidWilaya]])->errors();
        $this->assertSame('الولاية المختارة غير صحيحة.', $errors->first('w'));
    }

    public function test_api_routes(): void
    {
        $this->getJson('/api/algeria/wilayas')->assertOk()->assertJsonCount(69, 'data');
        $this->getJson('/api/algeria/wilayas/16')->assertOk()->assertJsonPath('data.name_ar', 'الجزائر');
        $this->getJson('/api/algeria/wilayas/68/dairas')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson('/api/algeria/wilayas/68/communes')->assertOk()->assertJsonCount(23, 'data');
        $this->getJson('/api/algeria/wilayas/16/communes?q=bab')->assertOk()->assertJsonPath('data.0.wilaya_id', 16);
        $this->getJson('/api/algeria/dairas/549/communes')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/algeria/wilayas/99/dairas')->assertNotFound();

        $this->getJson('/api/algeria/postal-codes/16000')->assertOk()
            ->assertJsonPath('data.code', '16000')
            ->assertJsonPath('data.commune.name', 'Alger Centre')
            ->assertJsonPath('data.wilaya.id', 16);
        $this->getJson('/api/algeria/postal-codes/99999')->assertNotFound();

        $commune = AlgeriaFacade::communeByPostalCode('16000')['id'];
        $this->getJson("/api/algeria/communes/{$commune}/postal-codes")->assertOk()
            ->assertJsonCount(AlgeriaFacade::postalCodes($commune)->count(), 'data');
    }
}

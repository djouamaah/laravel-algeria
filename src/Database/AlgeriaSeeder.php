<?php

namespace Djouamaah\Algeria\Database;

use Djouamaah\Algeria\Algeria;
use Djouamaah\Algeria\Models\Commune;
use Djouamaah\Algeria\Models\Daira;
use Djouamaah\Algeria\Models\PostalCode;
use Djouamaah\Algeria\Models\Wilaya;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent: safe to run again, and running it after changing the division
 * moves communes and removes wilayas/dairas that do not exist in that division.
 *
 * In your DatabaseSeeder: $this->call(\Djouamaah\Algeria\Database\AlgeriaSeeder::class);
 */
class AlgeriaSeeder extends Seeder
{
    /** Keeps every statement under SQL Server's 2100 bound parameters. */
    protected const CHUNK = 300;

    public function __construct(protected ?Algeria $algeria = null)
    {
    }

    public function run(): void
    {
        $data = ($this->algeria ?? app(Algeria::class))->data();

        DB::connection((new Wilaya)->getConnectionName())->transaction(function () use ($data) {
            $this->sync(Wilaya::query(), $data['wilayas'], ['code', 'name', 'name_ar', 'parent_id']);
            $this->sync(Daira::query(), $data['dairas'], ['wilaya_id', 'name', 'name_ar']);
            $this->sync(Commune::query(), $data['communes'], ['daira_id', 'wilaya_id', 'legacy_wilaya_id', 'name', 'name_ar', 'postal_code']);
            $this->sync(PostalCode::query(), $data['postal_codes'], ['commune_id', 'name', 'name_ar'], 'code');

            // PHP turns keys such as "16000" into integers; compare as strings against the char column.
            PostalCode::query()->whereNotIn('code', array_map('strval', array_keys($data['postal_codes'])))->delete();

            Commune::query()->whereNotIn('id', array_keys($data['communes']))->delete();
            Daira::query()->whereNotIn('id', array_keys($data['dairas']))->delete();
            Wilaya::query()->whereNotIn('id', array_keys($data['wilayas']))->delete();
        });
    }

    protected function sync(Builder $query, array $rows, array $update, string $key = 'id'): void
    {
        foreach (array_chunk(array_values($rows), self::CHUNK) as $chunk) {
            (clone $query)->upsert($chunk, [$key], $update);
        }
    }
}

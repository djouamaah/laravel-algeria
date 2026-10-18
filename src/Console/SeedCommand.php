<?php

namespace Djouamaah\Algeria\Console;

use Djouamaah\Algeria\Algeria;
use Djouamaah\Algeria\Database\AlgeriaSeeder;
use Illuminate\Console\Command;

class SeedCommand extends Command
{
    protected $signature = 'algeria:seed {--division= : 58 or 69 (defaults to config algeria.division)}';

    protected $description = 'Insert or update all Algerian wilayas, dairas and communes';

    public function handle(Algeria $algeria): int
    {
        if ($division = $this->option('division')) {
            if (! in_array((int) $division, Algeria::DIVISIONS, true)) {
                $this->components->error('The division must be 58 or 69.');

                return self::FAILURE;
            }
            $algeria = $algeria->withDivision((int) $division);
        }

        (new AlgeriaSeeder($algeria))->run();

        $data = $algeria->data();
        $this->components->info(sprintf(
            'Seeded %d wilayas, %d dairas and %d communes (%d-wilaya division).',
            count($data['wilayas']), count($data['dairas']), count($data['communes']), $algeria->division(),
        ));

        return self::SUCCESS;
    }
}

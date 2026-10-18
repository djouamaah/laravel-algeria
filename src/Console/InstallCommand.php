<?php

namespace Djouamaah\Algeria\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'algeria:install
        {--division= : 58 or 69 (defaults to config algeria.division)}
        {--no-config : Do not publish config/algeria.php}';

    protected $description = 'Publish the config, run the migrations and seed the Algerian territorial data';

    public function handle(): int
    {
        if (! $this->option('no-config')) {
            $this->call('vendor:publish', ['--tag' => 'algeria-config']);
        }

        $this->call('migrate', ['--force' => true]);

        return $this->call('algeria:seed', array_filter(['--division' => $this->option('division')]));
    }
}

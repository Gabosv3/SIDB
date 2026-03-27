<?php

namespace App\Console\Commands;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Console\Command;

class GenerateShieldPermissions extends Command
{
    protected $signature = 'shield:generate-all';

    protected $description = 'Generate Shield permissions and policies for all resources';

    public function handle()
    {
        $this->info('Generating Shield permissions and policies...');

        try {
            // Generate policies and permissions for all resources
            $this->call('shield:generate', [
                '--all' => true,
                '--option' => 'policies_and_permissions',
                '--panel' => 'administrativo',
            ]);

            $this->info('Shield permissions and policies generated successfully!');
        } catch (\Exception $e) {
            $this->error('Error generating permissions: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

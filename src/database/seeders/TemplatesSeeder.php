<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $sqlFilePath = resource_path('database/templates.sql');
        
        if (!File::exists($sqlFilePath)) {
            throw new \Exception("SQL file not found at: {$sqlFilePath}");
        }

        try {

            $sql = File::get($sqlFilePath);
            DB::unprepared($sql);
            $this->command->info('Templates seeded successfully.');

        } catch (\Exception $e) {

            $this->command->error('Failed to seed templates: ' . $e->getMessage());
            throw $e;
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:company';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde company (legacy) hacia company (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de company...");

        User::chunk(500, function ($users) {
            foreach ($users as $u) {
                DB::table('company_user')->insert([
                    'company_id' => Company::first()->id,
                    'user_id' => $u->id,
                ]);
            }
        });
    }
}

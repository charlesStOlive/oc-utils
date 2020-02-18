<?php namespace Waka\Utils\Updates;

//use Excel;
use DB;
use Seeder;

// use Waka\Crsm\Classes\CountryImport;

class SeedTables extends Seeder
{
    public function run()
    {
        Db::table('backend_users')->truncate();
        $sql = plugins_path('waka/utils/updates/sql/backend_users.sql');
        DB::unprepared(file_get_contents($sql));

        //$this->call('Waka\Crsm\Updates\Seeders\SeedProjectsMissions');

    }
}

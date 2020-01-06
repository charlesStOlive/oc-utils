<?php namespace Waka\Publisher\Updates;

//use Excel;
use Seeder;
use DB;
// use Waka\Crsm\Classes\CountryImport;



class SeedTables extends Seeder
{
    public function run()
    {
        //Excel::import(new CountryImport, plugins_path('waka/crsm/updates/excels/country.xlsx'));
        // $sector = Sector::create([
        //     'name'                 => 'Défaut',
        //     'slug'                 => 'defaut'
        // ]);
        // $type = Type::create([
        //     'name'                 => 'Prospect',
        //     'slug'                 => 'prospet'
        // ]);
        // $type = Type::create([
        //     'name'                 => 'Client',
        //     'slug'                 => 'client'
        // ]);
        //
        $sql = plugins_path('waka\utils\updates\sql\waka_utils_data_sources.sql');
        DB::unprepared(file_get_contents($sql));
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CitiesSeeder extends Seeder
{
    public function run()
    {
        $csv = Reader::createFromPath(storage_path('app/cities1.csv'), 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $row) {
            DB::table('cities')->insert([
                'city_id' => $row['city_id'],
                'city_name' => $row['city_name'],
            ]);
        }
    }
}

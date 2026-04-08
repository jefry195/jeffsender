<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use PDO;
class WAServerTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sql = file_get_contents(database_path('whatsapp-server-db.sql'));
        

        try {
            $db = new PDO("mysql:host=".env('DB_HOST').";dbname=".env('DB_DATABASE')."",env('DB_USERNAME'), env('DB_PASSWORD'));
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();


           
        } catch (\Throwable $th) {
            Log::info('SQL Import Error: '.$th);
        }
       
    }
}

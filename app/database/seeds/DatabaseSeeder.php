<?php /** @copyright Pley (c) 2014, All Rights Reserved */

use \Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        
        //$this->call('CustomerLogComponentTableSeeder');

        // The dummy data seeders for testing
    }
}
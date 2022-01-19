<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\models\outuser;

class testUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
            DB::table('outusers')->insert([
            'user_id'=>'testuser',
            'connect_id'=>'033bd197-5a64-46af-9695-a3d8117daa0f',
            'email'=>'testuser@gmail.com',
        ]);
    }
}

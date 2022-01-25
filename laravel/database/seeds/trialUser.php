<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\models\outuser;

class trialUser extends Seeder
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
            'user_id'=>'trialuser',
            'connect_id'=>'d1261205-2835-45b9-ad9a-fb2a153c388a',
            'email'=>'trial@gmail.com',
        ]);
    }
}

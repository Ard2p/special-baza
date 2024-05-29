<?php

namespace Modules\RestApi\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Overrides\Model;

class RestApiDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
    }
}

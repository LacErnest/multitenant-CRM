<?php

use App\Enums\TablePreferenceType;
use App\Models\Company;
use App\Models\Resource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Resource_;
use Tenancy\Facades\Tenancy;

class InitializeAutoIncrementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $comapnies = Company::all();

        foreach ($comapnies as $company){
            Tenancy::setTenant($company);
            $resource = Resource::orderBy('number','DESC')->first(); 
            if(!empty($resource)){
                $company->increments()->create([
                    'entity'=> TablePreferenceType::resources()->getIndex(),
                    'number'=> $resource->number,
                ]);
            }
            
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

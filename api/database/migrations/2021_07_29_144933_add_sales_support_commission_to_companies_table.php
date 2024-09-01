<?php

use App\Enums\CommissionModel;
use App\Enums\UserRole;
use App\Models\SalesCommission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesSupportCommissionToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('sales_support_commission', 5, 2)->default(1)->after('gm_bonus');
            $table->dropColumn('xero_tenant_id', 'xero_access_token', 'xero_refresh_token',
                'xero_id_token', 'xero_oauth2_state', 'xero_expires');
        });

        $this->addAllSalesToSalesCommissionsTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('sales_support_commission');
            $table->uuid('xero_tenant_id')->nullable()->after('id');
            $table->string('xero_access_token',1500)->nullable()->after('xero_tenant_id');
            $table->string('xero_refresh_token',255)->nullable()->after('xero_access_token');
            $table->string('xero_id_token', 2056)->nullable()->after('xero_refresh_token');
            $table->string('xero_oauth2_state', 2056)->nullable()->after('xero_id_token');
            $table->timestamp('xero_expires')->nullable()->after('xero_oauth2_state');
        });
    }

    private function addAllSalesToSalesCommissionsTable()
    {
        $salesPersons = User::where([['role', UserRole::sales()->getIndex()], ['primary_account', true]])->get();

        foreach ($salesPersons as $salesPerson) {
            if ($salesPerson->salesCommissions->isEmpty()) {
                $salesPerson->salesCommissions()->create([
                    'commission' => SalesCommission::DEFAULT_COMMISSION,
                    'second_sale_commission' => 0,
                    'commission_model' => CommissionModel::default()->getIndex(),
                ]);
            }
        }
    }
}

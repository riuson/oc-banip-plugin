<?php
namespace Filipac\Banip\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class IpsTableAddColumnMask  extends Migration
{

    public function up()
    {
        Schema::table('filipac_banip_ips', function ($table) {
            // Add column `mask`
            $table->integer('mask')->after('address')->default(32);
        });
    }

    public function down()
    {
        Schema::table('filipac_banip_ips', function ($table) {
            $table->dropColumn('mask');
        });
    }
}

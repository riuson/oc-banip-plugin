<?php namespace Filipac\Banip\Updates;

use DB;
use Schema;
use October\Rain\Database\Updates\Migration;
use filipac\Banip\Models\Ip as IpModel;

class IpsTableAddColumnMask  extends Migration
{

    public function up()
    {
        Schema::table('filipac_banip_ips', function ($table) {
            // Add column `upper ip range`
            $table->bigInteger('upper_ip_range')->after('address')->default(0)->unsigned();
            // Add column `lower ip range`
            $table->bigInteger('lower_ip_range')->after('address')->default(0)->unsigned();
            // Add column `mask`
            $table->integer('mask')->after('address')->default(32);
            // Add column `mask`
            $table->string('address_end')->after('address');
        });

        IpModel::chunk(100, function ($items) {
            foreach ($items as $item) {
                IpModel::correctFields($item);
                $item->save();
            }
        });
    }

    public function down()
    {
        Schema::table('filipac_banip_ips', function ($table) {
            $table->dropColumn('address_end');
            $table->dropColumn('mask');
            $table->dropColumn('lower_ip_range');
            $table->dropColumn('upper_ip_range');
        });
    }
}

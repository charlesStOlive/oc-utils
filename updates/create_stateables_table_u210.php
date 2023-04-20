<?php namespace Waka\Cloudis\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateStateteablesTableU210 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_state_log', function (Blueprint $table) {
            $table->index('state_logeable_type', 'state_logeable_type_idx');
            $table->index('state_logeable_id', 'state_logeable_id_idx');
            $table->index('state', 'state_idx');
            $table->index('created_at', 'created_at_idx');
        });
    }

    public function down()
    {
        Schema::table('waka_utils_state_log', function (Blueprint $table) {
            $table->dropIndex('state_logeable_type_idx');
            $table->dropIndex('state_logeable_id_idx');
            $table->dropIndex('state_idx');
            $table->dropIndex('created_at_idx');
        });
    }
}

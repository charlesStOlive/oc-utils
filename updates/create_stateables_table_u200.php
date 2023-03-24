<?php namespace Waka\Cloudis\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateStateteablesTableU105 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_state_log', function (Blueprint $table) {
            $table->string('wf')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waka_utils_state_log', function (Blueprint $table) {
            $table->dropColumn('wf');
        });
    }
}

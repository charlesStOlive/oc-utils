<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateDataSourcesTableU102 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_data_sources', function (Blueprint $table) {
            $table->text('relations_array_list')->default(0);
        });
    }

    public function down()
    {
        Schema::table('waka_utils_data_sources', function (Blueprint $table) {
            $table->dropColumn('relations_array_list');
        });
    }
}

<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateDataSourcesTableU104 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_data_sources', function (Blueprint $table) {
            $table->text('function_class')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waka_utils_data_sources', function (Blueprint $table) {
            $table->dropColumn('function_class');
        });
    }
}
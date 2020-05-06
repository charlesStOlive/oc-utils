<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateDataSourcesTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_data_sources', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('author')->default('waka');
            $table->string('plugin');
            $table->string('model');
            $table->string('controller')->nullable();
            $table->string('test_id')->nullable();
            $table->text('relations_list')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_data_sources');
    }
}

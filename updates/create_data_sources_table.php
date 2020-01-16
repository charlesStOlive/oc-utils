<?php namespace Waka\Utils\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

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
            $table->string('sector_access');
            $table->string('controller')->nullable();
            $table->string('test_id')->nullable();
            $table->string('specific_list')->nullable();
            $table->string('specific_update')->nullable();
            $table->string('specific_create')->nullable();
            $table->text('media_files')->nullable();
            $table->text('relations_list')->nullable();
            $table->text('attributes_list')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_data_sources');
    }
}

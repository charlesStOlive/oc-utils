<?php namespace Waka\Utils\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateRuleFncsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_rule_fncs', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('fnceable_id')->unsigned()->nullable();
            $table->string('fnceable_type')->nullable();
            $table->string('class_name')->nullable();
            $table->string('data_source')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_rule_fncs');
    }
}

<?php namespace Waka\Utils\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateRuleCondsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_rules_contents', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('contenteable_id')->unsigned()->nullable();
            $table->string('contenteable_type')->nullable();
            $table->string('mode')->nullable();
            $table->string('class_name')->nullable();
            $table->string('data_source')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->timestamps();
        });

        Schema::create('waka_utils_rules_filters', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('filterable_id')->unsigned()->nullable();
            $table->string('filterable_type')->nullable();
            $table->string('mode')->nullable();
            $table->string('class_name')->nullable();
            $table->string('data_source')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->timestamps();
        });

        Schema::create('waka_utils_rules_conditions', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('conditioneable_id')->unsigned()->nullable();
            $table->string('conditioneable_type')->nullable();
            $table->string('mode')->nullable();
            $table->string('class_name')->nullable();
            $table->string('data_source')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_rules_contents');
        Schema::dropIfExists('waka_utils_rules_filters');
        Schema::dropIfExists('waka_utils_rules_conditions');
    }
}

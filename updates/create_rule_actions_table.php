<?php namespace Waka\Utils\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateRuleActionsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_rules_actions', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->nullable();
            $table->integer('actioneable_id')->unsigned()->nullable();
            $table->string('actioneable_type')->nullable();
            $table->string('class_name')->nullable();
            $table->string('data_source')->nullable();
            $table->json('config_data')->nullable();
            $table->boolean('is_share')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_rules_actions');
    }
}

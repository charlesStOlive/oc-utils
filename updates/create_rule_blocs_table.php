<?php namespace Waka\Utils\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateRuleBlocsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_rule_blocs', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->nullable();
            $table->boolean('is_share')->nullable();
            $table->integer('bloceable_id')->unsigned()->nullable();
            $table->string('bloceable_type')->nullable();
            $table->string('class_name')->nullable();
            $table->json('config_data')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_rule_blocs');
    }
}

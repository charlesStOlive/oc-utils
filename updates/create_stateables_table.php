<?php namespace Waka\Cloudis\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateStateteablesTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_state_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('name');
            $table->integer('state_logeable_id')->nullable();
            $table->string('state_logeable_type')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_state_log');
    }
}

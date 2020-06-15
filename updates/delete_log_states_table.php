<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class DeleteLogStatesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('waka_utils_log_states');
    }

    public function down()
    {

        Schema::create('waka_utils_log_states', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
}

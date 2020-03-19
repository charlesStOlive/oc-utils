<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateScopeFunctionsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_scope_functions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('fnc_name');
            $table->text('config')->nullable();
            $table->string('data_source_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_scope_functions');
    }
}
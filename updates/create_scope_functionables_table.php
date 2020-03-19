<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateScopeFunctionablesTable extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_scope_functionables', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('scope_function_id');
            $table->integer('scope_functionable_id');
            $table->string('scope_functionable_type');
            $table->text('options')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_scope_functionables');
    }
}
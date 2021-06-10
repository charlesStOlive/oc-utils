<?php namespace Waka\Utils\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class DeleteJobListsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('waka_utils_job_lists');
    }

    public function down()
    {
        Schema::create('waka_utils_job_lists', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('state')->nullable();
            $table->text('user_id')->nullable();
            $table->integer('attempts')->nullable();
            $table->text('payload')->nullable();
            $table->text('errors')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->timestamps();
        });
    }
}

<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class DeleteDatasourceLogsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('waka_utils_source_logs');
    }

    public function down()
    {
        Schema::create('waka_utils_source_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('log_state')->unsigned()->nullable();
            $table->integer('sendeable_id')->unsigned()->nullable();;
            $table->string('sendeable_type')->nullable();
            $table->integer('send_targeteable_id')->unsigned()->nullable();;
            $table->string('send_targeteable_type')->nullable();
            $table->text('events')->nullable();
            $table->text('datas')->nullable();
            $table->string('key')->nullable();
            $table->boolean('user_delete_key')->default(false);
            $table->boolean('end_key_at')->default(false);
            $table->timestamps();
        });

    }
}

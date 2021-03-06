<?php namespace Waka\Utils\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateUserCreatorTables extends Migration
{
    public function up()
    {
        Schema::create('waka_utils_user_creator', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->morphs('usereable');
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_utils_user_creator');
    }
}

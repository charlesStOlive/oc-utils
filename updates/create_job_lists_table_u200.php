<?php namespace Waka\Utils\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateJobListsTableU200 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_job_lists', function (Blueprint $table) {
            $table->text('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waka_utils_job_lists', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}

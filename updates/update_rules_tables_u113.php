<?php namespace Waka\Cloudis\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateStateteablesTableU113 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->integer('sort_order')->nullable();
            $table->json('datas')->nullable();
        });
        Schema::table('waka_utils_rule_fncs', function (Blueprint $table) {
            $table->integer('sort_order')->nullable();
            $table->json('datas')->nullable();
        });
        Schema::table('waka_utils_rules_contents', function (Blueprint $table) {
            $table->integer('sort_order')->nullable();
            $table->json('datas')->nullable();
        });
        // Schema::table('waka_utils_rules_filters', function (Blueprint $table) {
        //     $table->integer('sort_order')->nullable();
        //     $table->json('datas')->nullable();
        // });
        Schema::table('waka_utils_rules_conditions', function (Blueprint $table) {
            $table->integer('sort_order')->nullable();
            $table->json('datas')->nullable();
        });

        // $asks = \Waka\Utils\Models\RuleAsk::get();
        // $this->setIdAsSortOrder($asks);
        // $fncs = \Waka\Utils\Models\RuleFnc::get();
        // $this->setIdAsSortOrder($fncs);
        // $conditions = \Waka\Utils\Models\RuleCondition::get();
        // $this->setIdAsSortOrder($conditions);
        // $contents = \Waka\Utils\Models\RuleContent::get();
        // $this->setIdAsSortOrder($contents);
        // $filters = \Waka\Utils\Models\RuleFilter::get();
        // $this->setIdAsSortOrder($filters);
    }

    public function setIdAsSortOrder($rules) {
        if(!$rules) {
            return;
        }
        foreach($rules as $rule) {
            $rule->sort_order = $rule->id;
            $rule->save();
        }
    }

    public function down()
    {
        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->dropColumn('datas');
        });
        Schema::table('waka_utils_rule_fncs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->dropColumn('datas');
        });
        Schema::table('waka_utils_rules_contents', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->dropColumn('datas');
        });
        // Schema::table('waka_utils_rules_filters', function (Blueprint $table) {
        //     $table->dropColumn('sort_order');
        //     $table->dropColumn('datas');
        // });
        Schema::table('waka_utils_rules_conditions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->dropColumn('datas');
        });
    }
}

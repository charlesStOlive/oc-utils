<?php namespace Waka\Cloudis\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateStateteablesTableU120 extends Migration
{
    public function up()
    {
        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->json('config_data')->change();
        });
        Schema::table('waka_utils_rule_fncs', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->json('config_data')->change();
        });
        Schema::table('waka_utils_rules_contents', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->json('config_data')->change();
        });
        Schema::table('waka_utils_rules_filters', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->json('config_data')->change();
        });
        Schema::table('waka_utils_rules_conditions', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->json('config_data')->change();
        });

        $asks = \Waka\Utils\Models\RuleAsk::get();
        $this->getCode($asks);
        $fncs = \Waka\Utils\Models\RuleFnc::get();
        $this->getCode($fncs);
        $conditions = \Waka\Utils\Models\RuleCondition::get();
        $this->getCode($conditions);
        $contents = \Waka\Utils\Models\RuleContent::get();
        $this->getCode($contents);
        $filters = \Waka\Utils\Models\RuleFilter::get();
        $this->getCode($filters);
    }

    public function getCode($rules) {
        if(!$rules) {
            return;
        }
        foreach($rules as $rule) {
            $config = $rule->config_data;
            //trace_log($config);
            $code = $config['code'] ?? false;
            //trace_log($code);
            if($code) {
                $rule->code = $code;
                $rule->save();
            }
            
        }
    }

    public function down()
    {
        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->dropColumn('code');
        });
        Schema::table('waka_utils_rule_fncs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('waka_utils_rules_filters', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('waka_utils_rules_conditions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
}

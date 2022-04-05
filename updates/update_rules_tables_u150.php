<?php namespace Waka\Cloudis\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateStateteablesTableU150 extends Migration
{
    public function up()
    {

        //trace_log("asks-------------------------------------------");
        $this->transformData('waka_utils_rule_asks');
        //trace_log("fncs-------------------------------------------");
        $this->transformData('waka_utils_rule_fncs');
        //trace_log("asks-------------------------------------------");
        $this->transformData('waka_utils_rules_contents');
        //trace_log("fncs-------------------------------------------");
        //$this->transformData('waka_utils_rules_filters');
        //trace_log("fncs-------------------------------------------");
        $this->transformData('waka_utils_rules_conditions');

        Schema::table('waka_utils_rule_asks', function (Blueprint $table) {
            $table->dropColumn('datas');
            $table->boolean('is_share')->after('code')->nullable();
        });
        Schema::table('waka_utils_rule_fncs', function (Blueprint $table) {
            $table->dropColumn('datas');
            $table->boolean('is_share')->after('code')->nullable();
        });
        Schema::table('waka_utils_rules_contents', function (Blueprint $table) {
            $table->dropColumn('datas');
            $table->dropColumn('mode');
            $table->boolean('is_share')->after('code')->nullable();
        });
        // Schema::table('waka_utils_rules_filters', function (Blueprint $table) {
        //     $table->dropColumn('datas');
        //     $table->boolean('is_share')->after('code')->nullable();
        // });
        Schema::table('waka_utils_rules_conditions', function (Blueprint $table) {
            $table->dropColumn('datas');
            $table->boolean('is_share')->after('code')->nullable();
        });
    }

    public function transformData($table) {
        $rows = \DB::table($table)->get();
        if(!$rows) {
            //trace_log("Il n' y a rien à faire pour cette table : ".$table);
        }
        foreach($rows as $row) {
            //trace_log($row->code.' : '.$row->class_name.' :--------');
            $id = $row->id;
            $subForm = new $row->class_name;
            $decodedConfig = json_decode($row->config_data, true);
            $modif = false;
            if($jsons = $subForm->jsonable) {
                foreach($jsons as $jsonField) {
                    if($jsonField != 'datas') {
                        //trace_log("--analyse : ".$jsonField);
                        $field = $decodedConfig[$jsonField] ?? null;
                        if($field && is_string($field)) {
                            $modif = true;
                            $fieldToArray = explode(',',$field);
                            //trace_log($field." a modifier en --");
                            //trace_log($fieldToArray);
                            $decodedConfig[$jsonField] = $fieldToArray;
                            
                        }
                        
                    }
                    //
                    
                }
            }
            if($row->datas ?? false) {
                $modif = true;
                //trace_log('--A copier dans le config');
                //trace_log($row->datas);
                $decodedConfig['datas'] = json_decode($row->datas);
                //trace_log("fin copie--");
            }
            if($modif) {
                $encodedConfig = json_encode($decodedConfig, JSON_UNESCAPED_SLASHES);
                //trace_log("--------config after modif---------");
                //trace_log($decodedConfig);
                //trace_log('----------------encode---------------');
                //trace_log($encodedConfig);
                \DB::table($table)->where('id', $id)->update(['config_data' => $encodedConfig]);
            }
        }
    }

    public function down()
    {
        
    }
}

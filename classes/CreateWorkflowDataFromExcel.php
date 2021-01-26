<?php namespace Waka\Utils\Classes;

class CreateWorkflowDataFromExcel extends CreateBase
{

    public function prepareVars($data)
    {
        $plugin = $data['plugin'];
        $model = $data['model'];
        $author = $data['author'];
        $config = $data['config'];
        $places = $data['places'];
        $trans = $data['trans'];
        $putTrans = $data['putTrans'];

        $configs = $config->toArray();

        $rules = $config->where('type', '==', 'rules');
        $rules = $rules->map(function ($item, $key) {
            if ($item['type'] == 'rules') {
                $item['data'] = explode(',', $item['data']);
            }
            return $item;
        });

        $ruleSetArray = $config->where('type', '==', 'ruleset')->lists('data', 'key');
        $rulesSets = [];
        foreach ($ruleSetArray as $key => $message) {
            $set = $rules->filter(function ($item) use ($key) {
                return in_array($key, $item['data']);
            });
            $rulesSets[$key] = [
                'fields' => $set->lists('value', 'key'),
                'message' => $message,
            ];
        }
        //trace_log($rulesSets);
        $trads = $config->where('type', '==', 'lang')->lists('data', 'key');
        //
        //
        $trans = $trans->map(function ($item, $key) {
            $item['functions'] = [];

            $fncProd = $item['fnc_prod'] ?? false;
            //trace_log("fncProd : " . $fncProd);
            if (!empty($fncProd)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_prod'];
                $args = $item['fnc_prod_arg'] ?? false;
                if ($args) {
                    $args = explode(',', $args);
                }
                $vals = $item['fnc_prod_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                if (is_countable($args)) {
                    for ($i = 0; $i < count($args); $i++) {
                        $argval[$args[$i]] = $vals[$i] ?? null;

                    }
                }

                $obj = [
                    'fnc' => $fncName,
                    'type' => 'prod',
                    'arguments' => $argval,
                ];
                $item['functions'][$fncName] = $obj;

            }
            $fncTrait = $item['fnc_trait'] ?? false;
            //trace_log("fnctrait : " . $fncTrait);
            if (!empty($fncTrait)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_trait'];
                $args = $item['fnc_trait_arg'] ?? false;
                if ($args) {
                    $args = explode(',', $args);
                }
                $vals = $item['fnc_trait_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                if (is_countable($args)) {
                    for ($i = 0; $i < count($args); $i++) {
                        $argval[$args[$i]] = $vals[$i] ?? null;

                    }
                }

                $obj = [
                    'fnc' => $fncName,
                    'type' => 'trait',
                    'arguments' => $argval,
                ];
                $item['functions'][$fncName] = $obj;

            }
            $fncGard = $item['fnc_gard'] ?? false;
            //trace_log("fnctrait : " . $fncTrait);
            if (!empty($fncGard)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_gard'];
                $args = $item['fnc_gard_arg'] ?? false;
                if ($args) {
                    $args = explode(',', $args);
                }
                $vals = $item['fnc_gard_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                if (is_countable($args)) {
                    for ($i = 0; $i < count($args); $i++) {
                        $argval[$args[$i]] = $vals[$i] ?? null;

                    }
                }

                $obj = [
                    'fnc' => $fncName,
                    'type' => 'gard',
                    'arguments' => $argval,
                ];
                $item['functions'][$fncName] = $obj;

            }
            return $item;
        });

        $fncs = $config->where('type', '==', 'fnc')->lists('data', 'key');

        $tradPlaces = $places->where('lang', '<>', null)->lists('lang', 'name');
        $tradPlacesCom = $places->where('com', '<>', null)->lists('com', 'name');
        $tradTrans = $trans->where('lang', '<>', null)->lists('lang', 'name');
        $tradTransCom = $trans->where('com', '<>', null)->lists('com', 'name');

        $places = $places->toArray();
        $trans = $trans->toArray();

        //trace_log($trans);

        $all = [
            'name' => $model,
            'model' => $model,
            'author' => $author,
            'plugin' => $plugin,
            'configs' => $configs,
            'trads' => $trads,
            'tradPlaces' => $tradPlaces,
            'tradTrans' => $tradTrans,
            'tradPlacesCom' => $tradPlacesCom,
            'tradTransCom' => $tradTransCom,
            'places' => $places,
            'fncs' => $fncs,
            'trans' => $trans,
            'rulesSets' => $rulesSets,
            'putTrans' => $putTrans,
        ];

        //trace_log($all);

        return $this->processVars($all);
    }

}

<?php namespace Waka\Utils\Classes;

class CreateWorkflowDataFromExcel extends CreateBase
{

    public function prepareVars($data)
    {
        $plugin = $data['plugin'];
        $model = $data['model'];
        $author = $data['author'];
        $config = $data['config'];
        $rows = $data['rows'];
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
        $rows = $rows->map(function ($item, $key) {
            if ($item['type'] != 'trans') {
                return $item;
            }
            $varExiste = $îtem['var'] ?? false;
            if (empty($item['var'])) {
                $item['var'] = $item['from'] . '_to_' . $item['to'];
            }
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
            return $item;
        });

        $tradPlaces = $rows->where('lang', '<>', null)->where('type', '==', 'places')->lists('lang', 'var');
        $tradPlacesCom = $rows->where('com', '<>', null)->where('type', '==', 'places')->lists('com', 'var');
        $tradTrans = $rows->where('lang', '<>', null)->where('type', '==', 'trans')->lists('lang', 'var');
        $tradTransCom = $rows->where('com', '<>', null)->where('type', '==', 'trans')->lists('com', 'var');

        $places = $rows->where('type', '==', 'places')->toArray();
        $trans = $rows->where('type', '==', 'trans')->toArray();

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
            'trans' => $trans,
            'rulesSets' => $rulesSets,
            'putTrans' => $putTrans,
        ];

        return $this->processVars($all);
    }

}

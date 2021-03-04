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

        $ruleSetArray = $config->where('type', '==', 'ruleset')->lists('label', 'key');
        $rulesSets = [];
        foreach ($ruleSetArray as $key => $message) {
            //trace_log($rules);
            $set = $rules->filter(function ($item) use ($key) {
                return in_array($key, $item['data']);
            });
            //trace_log($set);
            $rulesSets[$key] = [
                'fields' => $set,
                'message' => $message,
            ];
            //trace_log('fin de boucle');
        }
        //trace_log("resultat");
        //trace_log($rulesSets);

        //
        //
        $trans = $trans->map(function ($item, $key) use ($config) {
            $item['functions'] = [];

            $fncProd = $item['fnc_prod'] ?? false;
            //trace_log("fncProd : " . $fncProd);
            if (!empty($fncProd)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_prod'];
                $args = $this->getArgs($fncName, $config);
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
                $args = $this->getArgs($fncName, $config);
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
                $args = $this->getArgs($fncName, $config);
                $vals = $item['fnc_gard_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                //trace_log($args);
                //trace_log($vals);
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

        $fncs = $config->where('type', '==', 'fnc')->lists('label', 'key');

        //Travail sur les langues
        $trads = $config->where('type', '==', 'lang')->lists('label', 'key');
        $tradFieldRules = $config->where('type', '==', 'rules')->toArray();
        //trace_log($tradFieldRules);
        $tradPlaces = $places->where('lang', '<>', null)->lists('lang', 'name');
        $tradPlacesAlertes = $places->where('alerte', '<>', null)->lists('alerte', 'name');
        $tradPlacesCom = $places->where('com', '<>', null)->lists('com', 'name');
        $tradTrans = $trans->where('lang', '<>', null)->lists('lang', 'name');
        $tradTransCom = $trans->where('com', '<>', null)->lists('com', 'name');

        $places = $places->toArray();
        //trace_log($places);
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
            'tradPlacesAlertes' => $tradPlacesAlertes,
            'tradTransCom' => $tradTransCom,
            'places' => $places,
            'fncs' => $fncs,
            'trans' => $trans,
            'rulesSets' => $rulesSets,
            'putTrans' => $putTrans,
            'tradFieldRules' => $tradFieldRules,
        ];

        //trace_log($all);

        return $this->processVars($all);
    }

    public function getArgs($fncName, $config)
    {
        $args = $config->where('key', $fncName)->first();
        $args = $args['data'] ?? false;
        //Les arguments sont un string séparé par une , dans la colonne data
        if ($args) {
            $args = explode(',', $args);
            return $args;
        }
        return null;
    }
}

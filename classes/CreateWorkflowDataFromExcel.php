<?php

namespace Waka\Utils\Classes;

class CreateWorkflowDataFromExcel
{
    public function prepareVars($data)
    {
        $plugin = $data['plugin'];
        $workflowName =  $data['name'];
        $model = $data['model'];
        $author = $data['author'];
        $config = $data['config'];
        $places = $data['places'];
        $trans = $data['trans'];
        $putTrans = $data['putTrans'];

        // Configuration
        $configs = $config->toArray();

        // Récupération des règles
        $rules = $config->where('type', '==', 'rules')->map(function ($item, $key) {
            if ($item['type'] == 'rules') {
                $item['data'] = explode(',', $item['data']);
            }
            return $item;
        });

        // Récupération des règles sets
        $ruleSetArray = $config->where('type', '==', 'ruleset')->lists('label', 'key');
        $rulesSets = [];
        foreach ($ruleSetArray as $key => $message) {
            $set = $rules->filter(function ($item) use ($key) {
                return in_array($key, $item['data']);
            });
            $rulesSets[$key] = [
                'fields' => $set,
                'message' => $message,
            ];
        }

        $scopes = $config->where('type', '==', 'scopes');
        //trace_log( $scopes);

        // Récupération des traductions des champs des workflows
        $trads = $config->where('type', '==', 'lang')->lists('label', 'key');

        // Récupération des règles des champs des workflows
        $tradFieldRules = $config->where('type', '==', 'rules')->toArray();

        // // Récupération des fonctions
        // $fncs = $config->where('type', '==', 'fnc')->lists('label', 'key');

        // Récupération des fonctions
        $newFncs =  [];
        $fncsString = $config->where('type', '==', 'fnc')->pluck('key')->toArray();
        foreach ($fncsString as $line) {
            if (trim($line) == '') continue;
            preg_match('/(\w+)\(([^)]*)\)/', $line, $matches);
            $name = null;
            try {
                $name = $matches[1];
            } catch (\Exception $ex) {
                //trace_log('Il manque des parentheses sur '.$line);
            }
            $args = $this->getArguments($line);
            $newFncs[$name] = ['args' => $args];
        }
        //trace_log('----newFncs----');
        //trace_log($newFncs);

        // Récupération des traductions des places
        $tradPlaces = $places->where('lang', '<>', null)->lists('lang', 'name');
        $tradPlacesAlertes = $places->where('alerte', '<>', null)->lists('alerte', 'name');
        $tradPlacesCom = $places->where('com', '<>', null)->lists('com', 'name');

        // Récupération des traductions des transitions
        $tradTrans = $trans->where('lang', '<>', null)->lists('lang', 'name');
        $tradTransCom = $trans->where('com', '<>', null)->lists('com', 'name');
        $tradButton = $trans->where('button', '<>', null)->lists('button', 'name');

        // Traitement des places
        $places = $places->toArray();

        // Traitement des transitions
        $trans = $trans->reject(function ($item, $key) {
            $checkField = $item['name'] ?? null;
            if (!$checkField) {
                return true;
            } else {
                return false;
            }
        })->map(function ($item, $key) use ($newFncs) {
            $item['froms'] = explode(',', $item['froms']);
            $item['functions'] = $this->ananlyseItemsFunctions($item, $newFncs);
            return $item;
        });

        //trace_log($trans->toArray());

        // Résultat final
        $all = [
            'name' => $workflowName,
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
            'tradButton' => $tradButton,
            'places' => $places,
            'fncs' => $newFncs,
            'trans' => $trans,
            'rulesSets' => $rulesSets,
            'putTrans' => $putTrans,
            'tradFieldRules' => $tradFieldRules,
            'scopes' => $scopes,
        ];
        return $all;
    }

    /**
     * Récupère les arguments d'une fonction à partir de la configuration
     *
     * @param string $fncName
     * @param array $config
     * @return array|null
     */
    public function getArgs($fncName, $config)
    {
        $args = $config->where('key', $fncName)->first();
        $args = $args['data'] ?? false;

        //Les arguments sont un string séparé par une , dans la colonne data
        if ($args) {
            return explode(',', $args);
        }
        return null;
    }





    function getArguments($str)
    {
        preg_match_all('/\(([^)]*)\)/', $str, $matches);
        //trace_log($matches);
        if (isset($matches[1][0]) && trim($matches[1][0]) != '') {
            //trace_log($matches[1][0]);
            return array_map('trim', explode(',', $matches[1][0]));
        }
        return [];
    }

    function ananlyseItemsFunctions($item, $allFncs)
    {

        $fncs = [];
        foreach ($item as $key => $value) {

            if (strpos($key, 'fnc_') === 0 && trim($value) != '') {
                $type = substr($key, 4);
                $parts = array_map('trim', explode('|', $value));

                $notEmptyParts = array_filter($parts, function ($part) {
                    return trim($part) !== '';
                });



                if (empty($notEmptyParts)) {
                    continue;
                }


                foreach ($notEmptyParts as $part) {
                    if ($part == '') continue;
                    //trace_log("part : " . $part);
                    preg_match('/(\w+)\(([^)]*)\)/', $part, $matches);
                    $name = $matches[1];
                    //trace_log('name : ' . $name);
                    $args = $this->getArguments($part);
                    //trace_log('args--');
                    //trace_log($args);
                    //trace_log(" vide ? ".count($args));
                    //trace_log($allFncs[$name]);
                    $fncData = $allFncs[$name];
                    $fncData = ['type' => $type];
                    if (!empty($args)) {
                        //trace_log($allFncs[$name]['args']);
                        //trace_log($args);
                        $fncData['args'] = array_combine($allFncs[$name]['args'], $args);
                    }

                    $fncs[$name] = $fncData;
                }
            }
        }
        //trace_log($fncs);
        return $fncs;
    }
}

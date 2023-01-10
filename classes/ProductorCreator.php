<?php namespace Waka\Utils\Classes;

// use Lang;
use ApplicationException;
use Config;
use Winter\Storm\Support\Collection;
use Waka\Utils\Classes\DataSource;
use Event;

class ProductorCreator extends \Winter\Storm\Extension\Extendable
{
    public static $productor;

    /**
     * 
     */
    public $productorDs;

    /**
     * 
     */
    public $productorDsData;

    /**
     * 
     */
    public $productorDsQuery;

    /**
     * 
     */
    public $userKey;
    public $modelId;
    public $modelValues;
    public $askResponse;
    public $askModifiers;
    public $fncs;
    public $resolveContext = 'twig';


    public function getProductor()
    {
        return self::$productor;
    }
    public function setProductorss($productor)
    {
        return self::$productor = $productor;
    }
    public static function getProductorClass()
    {
        return get_class(self::$productor);
    }
    /**
     *  setModelId permet d'instancier le modèle. 
     */
    public function setModelId($modelId)
    {
        $this->modelId = $modelId;
        $ws = $this->getProductor()->waka_session;
        $dataSourceCode = $ws?->data_source;
        if($dataSourceCode) {
            $this->userKey = \Waka\Session\Classes\ManageKey::createKey($ws, [], $modelId);
            $this->productorDs = \DataSources::find($dataSourceCode);
            $this->productorDsQuery = $this->productorDs->getQuery($modelId);
        } else {
            $this->userKey = null;
            $this->productorDs = null;
            $this->productorDsQuery = null;
        }
        
        return $this;
    }

    /**
     *  setModelTest permet d'instancier le modèle de test. 
     */
    public function setModelTest()
    {
        $modelId = $dataSourceCode = $this->getProductor()->waka_session?->ds_id_test;
        if(!$modelId) {
             throw new \ValidationException(['test_id' => \Lang::get('waka.pdfer::wakapdf.e.test_id')]);
        }
        
        return $this->setModelId($modelId);
    }
    /**
     * Permet de vierifer si les conditions sont réunis voir ruleCondition. 
     */
    public function checkConditions()//Ancienement checkScopes
    {
        $conditions = new \Waka\Utils\Classes\Conditions($this->getProductor(), $this->productorDsQuery);
        return $conditions->checkConditions();
    }



    public function dynamyseText($content,$modelId =null) {
        if($modelId) {
            $this->instanciateQuery($modelId);
        }
        if(!$this->query) {
            throw new \SystemException('dynamyseText impossible le modèle est pas instancié ! ');
        }
        return \Twig::parse($content, ['ds' => $this->query]);
    }

    /**
     * 
     */
    public function getProductorAsks()
    {
        $productor = $this->getProductor();
        if(!$productor->rule_asks()->count()) {
            return [];
        }
        $asksList = [];
        $asks = $productor->rule_asks;
        foreach ($asks as $ask) {
            if($ask->isEditable()) {
                $askCode = $ask->getCode();
                $askField = $ask->getEditableField();
                $asksList['_ask_'.$askCode] = $ask->getEditableConfig();
                $asksList['_ask_'.$askCode]['default'] = $ask->getConfig($askField);
            }
        }
        return $asksList;
    }
    /**
     * 
     * 
     * 
     */

    public function getAskResponse($datas = [])
    {
        $askArray = [];
        // if(!$this->productorDs) return $askArray;
        $asks = $this->getProductor()->rule_asks()->get();
        //trace_log($this->askModifiers);
        foreach($asks as $ask) {
            $key = $ask->getCode();
            $modifier = $this->askModifiers[$key] ?? null;
            if($modifier) $ask->setModifier($modifier);
            $askResolved = [];
            if($this->productorDs) {
                $askResolved = $ask->resolve($this->productorDsQuery, $this->resolveContext, $datas);
            } else {
                $askResolved = $ask->resolve([], $this->resolveContext, $datas);
            }
            
            $askArray[$key] = $askResolved;
        }
        //trace_log($askArray); // les $this->askResponse sont prioritaire
        return array_replace($askArray,$this->askResponse ?? []);
        
    }

    //BEBAVIOR AJOUTE LES REPOSES ??
    public function setAsksResponse($datas = [])
    {
        $askArray = [];
        if($datas) {
            foreach($datas as $key=>$data) {
                if(starts_with($key, '_ask_')) {
                    $finalKey = str_replace('_ask_', '', $key);
                    $askArray[$finalKey] = $data;
                }
            }
        } 
        $this->askModifiers = $askArray;
        return $this;
    }

    public function setRuleFncsResponse()
    {
        $fncArray = [];
        if(!$this->productorDs) return $fncArray;
        $fncs = $this->getProductor()->rule_fncs()->get();
        foreach($fncs as $fnc) {
            $key = $fnc->getCode();
            //trace_log('key of the function');
            $fncResolved = $fnc->resolve($this->productorDsQuery,$this->productorDs->code);
            $fncArray[$key] = $fncResolved;
        }
        //trace_log($fncArray);
        return $fncArray;
        
    }

    public function getSessionModelData() {
        return $this->productorDs->getFullQuery();
    }

    public function getAsksAndFncs() {
        $values = [];
        $model = [
            'ds' => $this->productorDsQuery,
            'userKey' => $this->userKey->toArray()
        ];
        if($this->getProductor()->rule_fncs()->count()) {
            $fncs = $this->setRuleFncsResponse($model);
            $model = array_merge($model, [ 'fncs' => $fncs]);
            $values = array_merge($values, [ 'fncs' => $fncs]);
        }
        //Nouveau bloc pour nouveaux asks
        if($this->getProductor()->rule_asks()->count()) {
           $this->askResponse = $this->getAskResponse($model);
           $values = array_merge($values, [ 'asks' => $this->askResponse]);
        }
        return $values;
    }

    

    public function getProductorVars()
    {
        $values = [];
        $userKey = $this->userKey ? $this->userKey->toArray() : [];
        
        $model = [
            'ds' => $this->productorDsQuery,
            'userKey' => $userKey
        ];

        //trace_log($model);
        //Nouveau bloc pour les new Fncs
        if($this->getProductor()->rule_fncs()->count()) {
            $fncs = $this->setRuleFncsResponse($model);
            $model = array_merge($model, [ 'fncs' => $fncs]);
        }
        //Nouveau bloc pour nouveaux asks
        if($this->getProductor()->rule_asks()->count()) {
           $this->askResponse = $this->getAskResponse($model);
        } 
        $model = array_merge($model, [ 'asks' => $this->askResponse]);
        //trace_log($model);
        return $model;
    }

    public function createTwigStrName($varName = 'output_name')
    {
        if (!$this->getProductor()->{$varName}) {
            return str_slug($this->getProductor()->name);
        }
        $vars = [
            'ds' => $this->productorDsQuery,
        ];
        //trace_log($this->getProductor()->{$varName});
        $nameConstruction = \Twig::parse($this->getProductor()->{$varName}, $vars);
        return str_slug($nameConstruction);
    }
}
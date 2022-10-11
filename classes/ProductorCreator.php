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
    public $productorDs;
    public $productorDsData;
    public $productorDsQuery;
    public $userKey;
    public $modelId;
    public $modelValues;
    public $askResponse;
    public $fncs;
    public $resolveContext = 'twig';


    public function getProductor()
    {
        //trace_log("class : ".get_class(self::$productor));
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

    /**
     * 
     */
    public function getProductorAsks($productorClass, $productorId, $modelId)
    {
        if(!$productorId) {
             throw new \SystemException('le productorId est null ! ');
        }
        $productor = $productorClass::find($productorId);
        if(!$productor->rule_asks()->count()) {
            return [];
        }
        $this->instanciateQuery($modelId);
        $asksList = [];
        $asks = $productor->rule_asks()->get();
        foreach ($asks as $ask) {
            if($ask->isEditable()) {
                $askCode = $ask->getCode();
                $askContent = $ask->resolve($this->model, 'twig', ['ds' =>$this->getValues()]);
                $askType = $ask->getEditableOption();
                $asksList['_ask_'.$askCode] = [
                    'label' => "Pré remplissage de  : ".$askCode,
                    'default' => $askContent,
                    'type' => $askType,
                    'size'=> $askType  == 'textarea' ? 'tiny' : 'small',
                    'toolbarButtons' => $askType  == 'richeditor' ? 'bold|italic' : null,
                ];

            }
        }
        return $asksList;
    }

    
    public function getAsksFromData($datas = [], $modelAsks = []) {
        $askArray = [];
        if($datas) {
            foreach($datas as $key=>$data) {
                if(starts_with($key, '_ask_')) {
                    $finalKey = str_replace('_ask_', '', $key);
                    $askArray[$finalKey] = $data;
                }
            }
        } 
        if($modelAsks) {
            foreach($modelAsks as $row) {
                $type = $row['_group'];
                $finalKey = $row['code'];
                $keyExiste = $askArray[$finalKey] ?? false;
                if($keyExiste) {
                    //model déjà instancié on ne le traite pas. 
                    continue;
                } else {
                    $content = \Twig::parse($row['content'], ['ds' => $this->getValues()]);
                    $askArray[$finalKey] = $content;
                }
            }
        }
        return $askArray;
    }

    

    /**
     * 
     * 
     * 
     */

    public function getAsksByCode() {
        return $this->getProductor()->rule_asks->keyBy('code');

    }

    public function setRuleAsksResponse($datas = [])
    {
        $askArray = [];
        if(!$this->productorDs) return $askArray;
        $asks = $this->getProductor()->rule_asks()->get();
        foreach($asks as $ask) {
            $key = $ask->getCode();
            //trace_log($key);
            $askResolved = $ask->resolve($this->productorDsQuery, $this->resolveContext, $datas);
            $askArray[$key] = $askResolved;
        }
        //trace_log($askArray); // les $this->askResponse sont prioritaire
        return array_replace($askArray,$this->askResponse ?? []);
        
    }

    //BEBAVIOR AJOUTE LES REPOSES ??
    public function setAsksResponse($datas = [])
    {
        $this->askResponse = $this->getAsksFromData($datas, $this->getProductor()->asks);
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

    

    public function getProductorVars()
    {
        $values = [];
        
        $model = [
            'ds' => $this->productorDsQuery,
            'userKey' => $this->userKey->toArray()
        ];
        //trace_log("getProductorVars");
        //trace_log($model['userKey']);
        //
        //Recupère des variables par des evenements exemple LP log dans la finction boot
        $dataModelFromEvent = Event::fire('waka.productor.subscribeData', [$this]);
        if ($dataModelFromEvent[0] ?? false) {
            foreach ($dataModelFromEvent as $dataEvent) {
                $model[key($dataEvent)] = $dataEvent[key($dataEvent)];
            }
        }

        //Nouveau bloc pour les new Fncs
        if($this->getProductor()->rule_fncs()->count()) {
            $fncs = $this->setRuleFncsResponse($model);
            $model = array_merge($model, [ 'fncs' => $fncs]);
        }
        //Nouveau bloc pour nouveaux asks
        if($this->getProductor()->rule_asks()->count()) {
           $this->askResponse = $this->setRuleAsksResponse($model);
        } else {
            //Injection des asks s'ils existent dans le model;
            if(!$this->askResponse) {
                $this->setAsksResponse($model);
            }
        }
        $model = array_merge($model, [ 'asks' => $this->askResponse]);
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
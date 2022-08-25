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
    public static $ds;
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
    public function getDs()
    {
        return self::$ds;
    }
    public function getDsName()
    {
        return $this->getDs()->code;
    }
    /**
     *  setModelId permet d'instancier le modèle. 
     */
    public function setModelId($modelId)
    {
        $this->modelId = $modelId;
        $dataSourceCode = $this->getProductor()->data_source;
        self::$ds = \DataSources::find($dataSourceCode);
        self::$ds->instanciateModel($modelId);
        return $this;
    }

    /**
     *  setModelTest permet d'instancier le modèle de test. 
     */
    public function setModelTest()
    {
        $this->modelId = $this->getProductor()->test_id;
        if(!$this->modelId) {
             throw new \ValidationException(['test_id' => \Lang::get('waka.pdfer::wakapdf.e.test_id')]);
        }
        $dataSourceCode = $this->getProductor()->data_source;
        self::$ds = \DataSources::find($dataSourceCode);
        self::$ds->instanciateModel($this->modelId);
        return $this;
    }
    /**
     * Permet de vierifer si les conditions sont réunis voir ruleCondition. 
     */
    public function checkConditions()//Ancienement checkScopes
    {
        $conditions = new \Waka\Utils\Classes\Conditions($this->getProductor(), self::$ds->model);
        return $conditions->checkConditions();
    }

    /**
     * 
     */
    

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
        $srcmodel = null;
        if($this->getDs()) {
            $srcmodel = $this->getDs()->getModel($this->modelId);
        } 
        $asks = $this->getProductor()->rule_asks()->get();
        foreach($asks as $ask) {
            $key = $ask->getCode();
            //trace_log($key);
            $askResolved = $ask->resolve($srcmodel, $this->resolveContext, $datas);
            $askArray[$key] = $askResolved;
        }
        //trace_log($askArray); // les $this->askResponse sont prioritaire
        return array_replace($askArray,$this->askResponse ?? []);
        
    }

    //BEBAVIOR AJOUTE LES REPOSES ??
    public function setAsksResponse($datas = [])
    {
        $this->askResponse = $this->getDs()->getAsksFromData($datas, $this->getProductor()->asks);
        return $this;
    }

    public function setRuleFncsResponse()
    {
        $fncArray = [];
        $srcmodel = $this->getDs()->getModel($this->modelId);
        $fncs = $this->getProductor()->rule_fncs()->get();
        foreach($fncs as $fnc) {
            $key = $fnc->getCode();
            //trace_log('key of the function');
            $fncResolved = $fnc->resolve($srcmodel,$this->getDs()->code);
            $fncArray[$key] = $fncResolved;
        }
        //trace_log($fncArray);
        return $fncArray;
        
    }

    

    public function getProductorVars()
    {
        $values = [];
        if($this->getDs()) {
            $values = $this->getDs()->getModelAndRelations($this->modelId);
        } 
        $model = [
            'ds' => $values,
        ];
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
            'ds' => $this->getDs()->getValues(),
        ];
        //trace_log($this->getProductor()->{$varName});
        $nameConstruction = \Twig::parse($this->getProductor()->{$varName}, $vars);
        return str_slug($nameConstruction);
    }
}
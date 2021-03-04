<?php namespace Waka\Utils\Classes\fields;

class ModelList extends Title
{
    public $partial  = 'model_list';
    public $relations;
    public $model;
    public $modelId;
    public $dataHandler;
    public $modelEscapedClass;

    public function __construct($model, $key, $config)
    {
        parent::__construct($model, $key, $config);
        $this->setRelations();
    }

    public function setRelations()
    {
        $this->modelEscapedClass = str_replace('\\', '\\\\', get_class($this->model));
        $this->modelId = $this->parseRelation($this->config['modelId']);
        $this->dataHandler = $this->config['dataHandler'];
        $this->relations = $this->parseQueryRelation();
    }
}

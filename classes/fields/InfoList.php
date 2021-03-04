<?php namespace Waka\Utils\Classes\fields;

use Waka\Utils\Classes\ParseFields;

class InfoList extends Title
{
    public $partial  = 'info_list';
    public $fields;

    public function __construct($model, $key, $config)
    {
        parent::__construct($model, $key, $config);
        $this->setParseFields();
    }
    public function setParseFields()
    {
        $returnFields = new ParseFields();
        $this->fields = $returnFields->parseFields($this->model, $this->config['fields']);
    }
}

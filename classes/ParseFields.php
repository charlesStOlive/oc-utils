<?php namespace Waka\Utils\Classes;

use Waka\Utils\Classes\Fields\LabelAttribute;
use Waka\Utils\Classes\Fields\Title;
use Waka\Utils\Classes\Fields\InfoList;
use Waka\Utils\Classes\Fields\ModelList;
use Waka\Utils\Classes\Fields\ButtonUrl;
use Waka\Utils\Classes\Fields\LabelCalcul;

class ParseFields
{
    protected $fieldsType;

    public function __construct()
    {
        $this->fieldsType = [
            'label_attribute',
            'title',
            'info_list',
            'model_list',
            'button_url',
            'label_calcul',
        ];
    }
    public function parseFields($model, $fields)
    {
        $parsedFields = [];
        foreach ($fields as $key => $config) {
            $type = $config['type'] ?? 'label_attribute';
            if (!in_array($type, $this->fieldsType)) {
                throw new ApplicationException("le type ".$type." n' existe pas");
            }
            switch ($type) {
                case 'label_attribute':
                    $field = new LabelAttribute($model, $key, $config);
                    break;
                case 'title':
                    $field = new Title($model, $key, $config);
                    break;
                case 'info_list':
                    $field = new InfoList($model, $key, $config);
                    break;
                case 'model_list':
                    $field = new ModelList($model, $key, $config);
                    break;
                case 'button_url':
                    $field = new ButtonUrl($model, $key, $config);
                    break;
                case 'label_calcul':
                    $field = new LabelCalcul($model, $key, $config);
                    break;
            }
            array_push($parsedFields, $field);
        }
        return $parsedFields;
    }
}

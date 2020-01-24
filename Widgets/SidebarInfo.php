<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Yaml;
use ApplicationException;

class SidebarInfo extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'info';

    public $config;
    public $model;
    public $fields;

    public function render()
    {
        $this->perepareVars();
        $this->vars['fields'] = $this->fields;
        return $this->makePartial('sidebar_info');
    }

    public function perepareVars() {
        $model = $this->controller->formGetModel();
        $returnFields = new ParseFields();
        $this->fields = $returnFields->parseFields($model, $this->config->fields);
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarinfo.css', 'Waka.Utils');
        //$this->addJs('js/labellist.js', 'Waka.Utils');
    }

    
}
class ParseFields {
    public function parseFields($model, $fields) {
        $parsedFields = [];
        foreach($fields as $key => $config) {
            $type = $config['type'] ?? 'label_attribute';
            if(!in_array($type, ['label_attribute', 'title', 'info_list', 'model_list', 'button_url'])) throw new ApplicationException("le type ".$type." n' existe pas");
            switch ($type) {
                case 'label_attribute':
                    $field = new labelAttribute($model, $key, $config);
                break;
                case 'title':
                    $field = new Title($model, $key, $config);
                    //$field->setLabelOrValue();
                break;
                case 'info_list':
                    $field = new InfoList($model, $key, $config);
                    //$field->setParseFields();
                    
                break;
                case 'model_list':
                    $field = new ModelList($model, $key, $config);
                    //$field->setRelations();
                break;
                case 'button_url':
                    $field = new ButtonUrl($model, $key, $config);
                    // $field->setLabelOrValue();
                    // $field->setUrl();
                    
                break;
            }
            array_push($parsedFields, $field);
        }
        return $parsedFields;
    }

}
class labelAttribute extends BaseField {
    public $partial  = 'label_attribute';
}
class Title extends BaseField  {
    public $partial = 'title';
    public $headingLevel = 'h2';

    public function __construct($model, $key, $config) {
        parent::__construct($model, $key, $config);
        $this->setHeadingLevel();
    }

    public function setHeadingLevel() {
        $headingLevel = $this->config['headingLevel'] ?? false;
        if($headingLevel) $this->headingLevel = $headingLevel;
     }
}
class ButtonUrl extends BaseField {
    public $partial = 'button_url';
    public $url;
    public function __construct($model, $key, $config) {
        parent::__construct($model, $key, $config);
        $this->setUrl();
    }
    public function setUrl() {
        $url = $this->config['url'];
        $id = $this->parseRelation($this->config['modelid']);
        $this->url = $url . $id;
    }

}



class ModelList extends Title {
    public $partial  = 'model_list';
    public $relations;
    public $model;
    public $modelId;
    public $dataHandler;
    public $modelEscapedClass;

    public function __construct($model, $key, $config) {
        parent::__construct($model, $key, $config);
        $this->setRelations();
    }

    public function setRelations() {
        $this->modelEscapedClass = str_replace('\\', '\\\\', get_class($this->model));
        $this->modelId = $this->parseRelation($this->config['modelId']);
        $this->dataHandler = $this->config['dataHandler'];
        $this->relations = $this->parseQueryRelation();
    }
}

class InfoList extends Title {
    public $partial  = 'info_list';
    public $fields;

    public function __construct($model, $key, $config) {
        parent::__construct($model, $key, $config);
        $this->setParseFields();
    }
    public function setParseFields()  {
        $returnFields = new ParseFields();
        $this->fields = $returnFields->parseFields($this->model, $this->config['fields']);
    }
    

}



class BaseField {
    public $label;
    public $config;
    public $key;
    public $model;
    public $class;
    public $incon;

    public function __construct($model, $key, $config) {
        $this->model = $model;
        $this->key = $key;
        $this->config = $config;
        $this->class = $config['class'] ?? null;
        $this->icon = $config['icon'] ?? false;
        $this->prepareBase(); 
    }
    
    public function prepareBase() {
        $this->label =  $this->setLabel();
        $this->value = $this->parseRelation();
    }

    public function setLabel() {
        if($this->config['labelFrom'] ?? false) {
            $labelFrom = $this->parseRelation($this->config['labelFrom']);
            return $labelFrom;
        } else {
            return $this->config['label'] ?? 'waka.utils::lang.global.unkown';
        }
       
    }

    public function parseRelation($valueFrom=null) {
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
        //trace_log($fieldKey);
        $parts = explode(".", $fieldKey);
        $nbParts = count($parts) ?? 1;
        if($nbParts > 1) {
            if($nbParts == 2 ) return  $this->model[$parts[0]][$parts[1]] ?? 'waka.utils::lang.global.unkown';
            if($nbParts == 3 ) return  $this->model[$parts[0]][$parts[1]][$parts[2]] ?? 'waka.utils::lang.global.unkown';
            if($nbParts == 4 ) return  $this->model[$parts[0]][$parts[1]][$parts[2]] ?? 'waka.utils::lang.global.unkown';
        } else {
            return $this->model[$fieldKey] ?? 'waka.utils::lang.global.unkown';
        }
    }
    public function parseQueryRelation($valueFrom=null) {
        //trace_log("pârse query relation");
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
        //trace_log($fieldKey);
        $parts = explode(".", $fieldKey);
        $nbParts = count($parts) ?? 1;
        if($nbParts > 1) {
            if($nbParts == 2 ) return  $this->model{$parts[0]}->{$parts[1]} ?? null;
            if($nbParts == 3 ) return  $this->model{$parts[0]}->{$parts[1]}->{$parts[2]} ?? null;
        } else {
            return $this->model{$fieldKey} ?? null;
        }
    }
}
<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Classes\WakaDate;
use System\Helpers\DateTime as DateTimeHelper;
use Lang;

/**
 * modelInfo Form Widget
 */
class ModelInfo extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_model_info';

    public $fields = [];
    public $label = "wcli.utils.formfields.modelInfo.label";
    public $ds;
    public $src;
    public $parsedFields;

    /**
     * @inheritDoc
     */
    public function init()
    {
        \Event::listen('wcli.utils.modelInfo.refresh', function($controler) {
            //trace_log('call wcli.utils.modelInfo.refresh');
        });

        $this->fillFromConfig([
            'label',
            'src',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        $this->vars['parsedFields'] = $this->parsedFields;
        $this->vars['label'] = $this->label;
        $this->vars['modelId'] = $this->model->id;
        return $this->makePartial('modelinfo');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $modelId = $this->model->id;
        $modelClass = get_class($this->model);
        //trace_log($modelClass);
        //trace_log(get_class($this->getController()));
        $this->ds = new DataSource($modelClass, 'class');
        if(!$this->ds) {
            throw new \SystemException('DS info pas trouvé');
        }
        $src = null;
        if(is_array($this->src)) {
            $src = $this->src;
        } else {
            $src = \Yaml::parseFile(plugins_path().$this->src);
        }
        $this->fields = $src['fields'];
        if(!$this->fields) {
            throw new \SystemException('la config  side bar info n a pas été trouvée');
        }
        $this->parsedFields = $this->setValues($modelId, $this->fields);
    }

    public function setValues($modelId, $fields)
    {
        $modelvalues = $this->ds->getValues($modelId);
        $dotedValues = array_dot($modelvalues);
        //trace_log($dotedValues);
        $parsedFields = [];
        foreach ($fields as $key=>$field) {
            $showIf =  $field['showIf'] ?? null;
            if($showIf) {
                $showField = $showIf['field'] ?? null;
                $fieldToTest = $dotedValues[$showField];
                $tests = $showIf['tests'] ?? null;
                $condition = $showIf['condition'] ?? null;
                if(!$showField or !is_array($tests)) {
                    throw new \SystemException('Configuration sidebarinfo erreur sur du showIF');
                }
                //trace_log($fieldToTest);
                //trace_log($tests);

                $fieldInArray = in_array($fieldToTest, $tests);
                //trace_log('fieldInArray : '.$fieldInArray);
                //trace_log('condition : '.$condition);
                if($fieldInArray != $condition) {
                    //trace_log("PAS OK");
                    continue;
                }
            }
            $type = $field['infoType'] ?? 'label';
            $icon = $field['icon'] ?? null;

            $label = $field['label'] ?? null;
            $labelFrom = $field['labelFrom'] ?? null;
            if ($labelFrom) {
                $label = $dotedValues[$labelFrom] ?? "inconnu";
            }

            $value = null;
            $fieldValue = $field['value'] ?? $key;
            if ($fieldValue) {
                $value = $dotedValues[$fieldValue] ?? "inconnu";
            }

            trace_log($key .' : '.$value);

            $cssInfoClass = $field['cssInfoClass'] ?? null;
            $link = null;
            $bkRacine = $field['bkRacine'] ?? null;
            $exRacine = $field['exRacine'] ?? null;
            $linkValue = $field['linkValue'] ?? null;
            if($linkValue) {
                $linkValue =$dotedValues[$linkValue] ?? null;
            } else {
                $linkValue =$value;
            }
            
            if ($bkRacine && $linkValue) {
                $link = \Backend::url($bkRacine . $linkValue);
            } 
            elseif ($exRacine && $linkValue) {
                $link = $exRacine . $linkValue;
            }
            elseif ($linkValue) {
                $link = $value;
            }



            if ($type == 'workflow') {
                $value = $dotedValues['wfPlaceLabel'];
            }

            if ($type == 'date') {
                $mode = $field['mode'] ?? 'date-short-time';
                $value = $dotedValues[$fieldValue] ?? "Erreur";
                if($value != 'Erreur') {
                    $date = new WakaDate();
                    $value = DateTimeHelper::makeCarbon($value, false);
                    $value =  $date->localeDate($value, $mode);
                }
               
            }

            if ($type == 'state_logs') {
                $value = [];
                $logs = $this->ds->getStateLogsValues($modelId);
                if ($logs) {
                    $src_trad = $field['src_trad'] ?? null;
                    foreach ($logs as $log) {
                        $obj = [
                            'label' => Lang::get($src_trad . $log['name'] ?? null),
                            'created_at' => $log['created_at'],
                        ];
                        array_push($value, $obj);
                    }
                }
            }
           
            if ($type == 'array') {
                $value = [];
                $fieldValues = $field['values'] ?? null;
                $options = $field['options'] ?? null;
                if($options) {
                    $this->ds->model->{$options};
                    $options = $this->ds->model->{$options}();
                }
                //Array on enregistre la valeur dans values
                $rows = [];
                if($fieldValues) {
                    $rows = $modelvalues[$fieldValues] ?? [];
                }
                if (count($rows)) {
                    foreach ($rows as $key=>$row) {
                        if($options) {
                            //trace_log($options);
                            //trace_log($row);
                            array_push($value, $options[$row] ?? '');
                        } else {
                            array_push($value, $row); 
                        }
                    }
                }
            }

            $field = [
                'infoType' => $type,
                'icon' => $icon,
                'label' => lang::get($label),
                'value' => $value,
                'cssInfoClass' => $cssInfoClass,
                'link' => $link,
            ];
            //trace_log($field);

            array_push($parsedFields, $field);
        }
        //trace_log($parsedFields);
        return $parsedFields;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/modelinfo.css', 'waka.utils');
        $this->addJs('js/modelinfo.js', 'waka.utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }
}

<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Lang;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Classes\WakaDate;
use System\Helpers\DateTime as DateTimeHelper;

class SidebarInfo extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'info';

    public $config;
    public $model;
    public $fields;
    private $ds;

    public function render()
    {
        $this->perepareVars();
        $this->vars['fields'] = $this->fields;
        return $this->makePartial('sidebar_list');
    }

    public function perepareVars()
    {
        $controllerModel = $this->controller->formGetModel();
        $modelId = $controllerModel->id;
        $this->ds = new DataSource($this->config->model, 'name');

        $this->fields = $this->setValues($modelId, $this->config->fields);
    }

    public function setValues($modelId, $fields)
    {
        $modelvalues = $this->ds->getValues($modelId);
        $dotedValues = array_dot($modelvalues);
        $parsedFields = [];
        foreach ($fields as $field) {
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
            $type = $field['type'] ?? 'label_value';
            $icon = $field['icon'] ?? null;

            $label = $field['label'] ?? null;
            $labelFrom = $field['labelFrom'] ?? null;
            if ($labelFrom) {
                $label = $dotedValues[$labelFrom] ?? "inconnu";
            }

            $value = null;
            $fieldValue = $field['value'] ?? null;
            if ($fieldValue) {
                $value = $dotedValues[$fieldValue] ?? "inconnu";
            }

            $cssClass = $field['cssClass'] ?? null;
            $link = null;
            $racine = $field['racine'] ?? null;
            if ($racine && $value) {
                $link = \Backend::url($field['racine'] . $value);
            } elseif ($value) {
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
                'type' => $type,
                'icon' => $icon,
                'label' => lang::get($label),
                'value' => $value,
                'cssClass' => $cssClass,
                'link' => $link,
            ];
            //trace_log($field);

            array_push($parsedFields, $field);
        }
        return $parsedFields;
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarinfo.css', 'Waka.Utils');
    }
}

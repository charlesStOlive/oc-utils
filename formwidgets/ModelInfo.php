<?php

namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
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
    public $editPermissions = null;

    /**
     * @inheritDoc
     */
    public function init()
    {
        \Event::listen('wcli.utils.modelInfo.refresh', function ($controler) {
            //trace_log('call wcli.utils.modelInfo.refresh');
        });

        $this->fillFromConfig([
            'label',
            'src',
            'editPermissions'
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
        $hasEditpermission = true;
        if ($this->editPermissions) {
            $hasEditpermission = \BackendAuth::getUser()->hasAccess($this->editPermissions);
        }
        $this->vars['hasEditpermission'] = $hasEditpermission;
        return $this->makePartial('modelinfo');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $src = null;
        if (is_array($this->src)) {
            $src = $this->src;
        } else {
            $src = \Yaml::parseFile(plugins_path() . '/' . $this->src);
        }
        $this->fields = $src['fields'];
        if (!$this->fields) {
            throw new \SystemException('la config  side bar info n a pas été trouvée');
        }
        $this->parsedFields = $this->setValues($this->fields);
    }

    public function setValues($fields)
    {
        $parsedFieldsData = [];
        foreach ($fields as $key => $field) {
            $showIf =  $field['showIf'] ?? null;
            if ($showIf) {
                $showField = $showIf['field'] ?? null;
                $fieldToTest = array_get($this->model, $showField);
                $tests = $showIf['tests'] ?? null;
                $condition = $showIf['condition'] ?? null;
                if (!$showField or !is_array($tests)) {
                    throw new \SystemException('Configuration sidebarinfo erreur sur du showIF');
                }

                $fieldInArray = in_array($fieldToTest, $tests);
                if ($fieldInArray != $condition) {
                    continue;
                }
            }
            $type = $field['infoType'] ?? 'label';
            $label = $field['label'] ?? null;
            $icon = $field['icon'] ?? null;
            $labelFrom = $field['labelFrom'] ?? null;
            if ($labelFrom) {
                $label = array_get($this->model, $labelFrom);
            }
            //
            // $value = null;
            // if ($modelValue = $field['modelValue'] ?? false) {
            //     //Si il y a une valeur modèle valeur on va chercher la valeur dans le modèle et non dans le dotedArray
            //     $value = $this->ds->getQuery()->{$modelValue};
            // } else {
            //     $fieldValue = $field['value'] ?? $key;
            //     if ($fieldValue) {
            //         $value = array_get($this->model, $fieldValue);
            //     }
            // }
            $value = null;
            //A conserver le temps du nettoyage de modelValue
            if ($modelValue = $field['modelValue'] ?? false) {
                $value = $modelValue;
            } else if ($fieldValue = $field['value'] ?? $key) {
                $value = array_get($this->model, $fieldValue);
            }


            $cssInfoClass = $field['cssInfoClass'] ?? null;
            $link = null;
            $bkRacine = $field['bkRacine'] ?? null;
            $exRacine = $field['exRacine'] ?? null;
            $racine = $field['racine'] ?? null;
            $linkValue = $field['linkValue'] ?? null;
            if ($linkValue) {
                $linkValue = array_get($this->model, $linkValue);
            } else {
                $linkValue = $value;
            }

            if ($bkRacine && $linkValue) {
                $link = \Backend::url($bkRacine . $linkValue);
            } elseif ($exRacine && $linkValue) {
                $link = $exRacine . $linkValue;
            } elseif ($racine && $linkValue) {
                $link = url($racine . $linkValue);
            }
            // } elseif ($linkValue) {
            //     $link = $value;
            // }



            if ($type == 'workflow') {
                $value = array_get($this->model, 'wfPlaceLabel');
            }

            if ($type == 'date') {
                $mode = $field['mode'] ?? 'date-time';
                $value = array_get($this->model, $fieldValue, 'Inconnu');
                if ($value != 'Inconnu' && $value) {
                    $date = new WakaDate();
                    $value = DateTimeHelper::makeCarbon($value, false);
                    $value =  $date->localeDate($value, $mode);
                }
            }


            if ($type == 'state_logs') {
                $value = [];
                $logs = $this->model->state_logs()->orderBy('created_at')->get();
                if ($logs) {
                    $src_trad = $field['src_trad'] ?? null;
                    foreach ($logs as $log) {
                        $log_label = $log->name;
                        if ($log->wf) {
                            $log_label = Lang::get($src_trad . '::' . $log->wf . '.trans.' . $log_label);
                        }
                        $logDate = new WakaDate();
                        $logValue = DateTimeHelper::makeCarbon($log['created_at'], false);
                        $logvalue =  $logDate->localeDate($logValue, 'date-time');
                        $obj = [
                            'label' => $log_label,
                            'user' => $log['user'] ?? 'inc',
                            'created_at' => $logvalue,
                        ];
                        array_push($value, $obj);
                    }
                }
            }
            $group = $field['group'] ?? null;
            //
            $data = [
                'icon' => $icon,
                'label' => lang::get($label),
                'value' => $value,
                'cssInfoClass' => $cssInfoClass,
                'link' => $link,
                'type' => $type,
                'group' => $group,
                // 'view' => $this->findView($type),
            ];
            // if ($group) {
            //     // Find existing group section with the same name or create a new one
            //     if(!$parsedFieldsData[$group] ?? false) {
            //         $parsedFieldsData[$group] = ['children' => []];
            //     }
            //     array_push($parsedFieldsData[$group]['children'], $data);
            // } else {
            //     array_push($parsedFieldsData, $data);
            // }
            $parsedFieldsData[$key] = $data;
            // array_push($parsedFields, $viewLi);
        }
        //trace_log($parsedFieldsData);
        $parsedFieldsData = $this->groupArray($parsedFieldsData);
        //trace_log($parsedFieldsData);
        return $this->groupArray($parsedFieldsData);
    }

    private function groupArray($array)
    {
        // Prépare un tableau pour stocker les résultats
        $result = [];

        // Parcours le tableau
        foreach ($array as $key => $value) {
            // Si l'élément a une clé 'group', le déplace sous l'élément correspondant
            if (isset($value['group']) && $value['group'] != '') {
                $group = $value['group'];
                if (!isset($result[$group]['children'])) {
                    $result[$group]['children'] = [];
                }
                $result[$group]['children'][$key] = $value;
            } else {
                // Sinon, ajoute simplement l'élément au résultat
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        // $this->addCss(); INUTILE EST GERE DANS LE WAKA.LESS de WCONFIG
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }

    public function getMIDate()
    {
    }
    public function getMIWorkflow()
    {
    }
    public function getMILink()
    {
    }
}

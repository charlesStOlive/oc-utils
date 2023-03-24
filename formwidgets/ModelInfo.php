<?php namespace Waka\Utils\FormWidgets;

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
        \Event::listen('wcli.utils.modelInfo.refresh', function($controler) {
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
        if($this->editPermissions) {
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
        $modelId = $this->model->id;
        $modelClass = get_class($this->model);
        //trace_log($modelClass);
        //trace_log($this->model->id);
        $this->ds = \DataSources::findByClass($modelClass);
        if(!$this->ds) {
            throw new \SystemException('DS info pas trouvé');
        }
        $src = null;
        if(is_array($this->src)) {
            $src = $this->src;
        } else {
            $src = \Yaml::parseFile(plugins_path().'/'.$this->src);
        }
        $this->fields = $src['fields'];
        if(!$this->fields) {
            throw new \SystemException('la config  side bar info n a pas été trouvée');
        }
        $this->parsedFields = $this->setValues($modelId, $this->fields);
    }

    public function setValues($modelId, $fields)
    {
        try {
            $modelvalues = $this->ds->getValues($modelId);
        } catch (\Exception $ex) {
            //trace_log($ex->getMessage());
            return [];

        }
        
        $parsedFields = [];
        foreach ($fields as $key=>$field) {
            $showIf =  $field['showIf'] ?? null;
            if($showIf) {
                $showField = $showIf['field'] ?? null;
                $fieldToTest = array_get($modelvalues, $showField);
                $tests = $showIf['tests'] ?? null;
                $condition = $showIf['condition'] ?? null;
                if(!$showField or !is_array($tests)) {
                    throw new \SystemException('Configuration sidebarinfo erreur sur du showIF');
                }

                $fieldInArray = in_array($fieldToTest, $tests);
                if($fieldInArray != $condition) {
                    continue;
                }
            }
            $type = $field['infoType'] ?? 'label';
            $label = $field['label'] ?? null;
            $icon = $field['icon'] ?? null;
            $labelFrom = $field['labelFrom'] ?? null;
            if ($labelFrom) {
                $label = array_get($modelvalues, $labelFrom);
            }
            //
            $value = null;
            if($modelValue = $field['modelValue'] ?? false) {
                //Si il y a une valeur modèle valeur on va chercher la valeur dans le modèle et non dans le dotedArray
                $value = $this->ds->getQuery()->{$modelValue};
            } else {
                $fieldValue = $field['value'] ?? $key;
                if ($fieldValue) {
                    $value = array_get($modelvalues, $fieldValue);
                }
            }
            

            //trace_log($key .' : '.$value);

            $cssInfoClass = $field['cssInfoClass'] ?? null;
            $link = null;
            $bkRacine = $field['bkRacine'] ?? null;
            $exRacine = $field['exRacine'] ?? null;
            $racine = $field['racine'] ?? null;
            $linkValue = $field['linkValue'] ?? null;
            if($linkValue) {
                $linkValue = array_get($modelvalues, $linkValue);
            } else {
                $linkValue =$value;
            }
            
            if ($bkRacine && $linkValue) {
                $link = \Backend::url($bkRacine . $linkValue);
            } 
            elseif ($exRacine && $linkValue) {
                $link = $exRacine . $linkValue;
            }
            elseif ($racine && $linkValue) {
                $link = url($racine .$linkValue);
            }
            elseif ($linkValue) {
                $link = $value;
            }



            if ($type == 'workflow') {
                $value = array_get($modelvalues, 'wfPlaceLabel');
            }

            if ($type == 'date') {
                $mode = $field['mode'] ?? 'date-time';
                $value = array_get($modelvalues, $fieldValue, 'Inconnu');
                if($value != 'Inconnu') {
                    $date = new WakaDate();
                    $value = DateTimeHelper::makeCarbon($value, false);
                    $value =  $date->localeDate($value, $mode);
                }
               
            }
            

            if ($type == 'state_logs') {
                //trace_log('state_logs');
                $value = [];
                $logs = $this->model->state_logs()->orderBy('created_at')->get();
                //trace_log($logs);
                if ($logs) {
                    $src_trad = $field['src_trad'] ?? null;
                    foreach ($logs as $log) {
                        //trace_log($log->toArray());
                        $label = $log->name;
                        if($log->wf) {
                             $label = Lang::get($src_trad .'::'.$log->wf.'.trans.'. $label);
                        }
                        $logDate = new WakaDate();
                        $logValue = DateTimeHelper::makeCarbon($log['created_at'], false);
                        $logvalue =  $logDate->localeDate($logValue, 'date-time');
                        $obj = [
                            'label' => $label,
                            'user' => $log['user'] ?? 'inc',
                            'created_at' => $logvalue,
                        ];
                        array_push($value, $obj);
                    }
                }
            }            
            $data = [
                'icon' => $icon,
                'label' => lang::get($label),
                'value' => $value,
                'cssInfoClass' => $cssInfoClass,
                'link' => $link,
            ];
            //trace_log($data);

            $view = $this->findView($type);
            //trace_log($view);
            
            $viewLi = [
                'contenu' => \View::make($view)->withData($data),
                'cssInfoClass' => $cssInfoClass,
            ];

            array_push($parsedFields, $viewLi);
        }
        //trace_log($parsedFields);
        return $parsedFields;
    }

    public function findView($infoType) {
        switch ($infoType) {
            case 'array':
                return "waka.utils::sidebar.array";
                break;
            case 'date':
                return "waka.utils::sidebar.label";
                break;
            case 'euro':
                return "waka.utils::sidebar.euro";
                break;
            case 'label_br_value':
                return "waka.utils::sidebar.label_br";
                break;
            case 'label':
                return "waka.utils::sidebar.label";
                break;
            case 'switch':
                return "waka.utils::sidebar.switch";
                break;
            case 'link':
                return "waka.utils::sidebar.link";
                break;
            case 'section':
                return "waka.utils::sidebar.section";
                break;
            case 'state_logs':
                return "waka.utils::sidebar.state_logs";
                break;
            case 'switch':
                return "waka.utils::sidebar.switch";
                break;
            case 'title':
                return "waka.utils::sidebar.title";
                break;
            case 'workflow':
                return "waka.utils::sidebar.workflow";
                break;
            default:
                return $infoType;
        }
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
}

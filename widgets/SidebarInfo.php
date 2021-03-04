<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Lang;
use Waka\Utils\Classes\DataSource;

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
        $dotedValues = $this->ds->getSimpleDotedValues($modelId);
        $parsedFields = [];
        foreach ($fields as $field) {
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

            if ($racine && $value) {
                $link = \Backend::url($field['racine'] . $value);
            }

            $field = [
                'type' => $type,
                'icon' => $icon,
                'label' => lang::get($label),
                'value' => $value,
                'cssClass' => $cssClass,
                'link' => $link,
            ];

            array_push($parsedFields, $field);
        }
        return $parsedFields;
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarinfo.css', 'Waka.Utils');
    }
}

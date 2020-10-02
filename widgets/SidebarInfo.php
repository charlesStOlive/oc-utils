<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
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

        //$model = get_class($controllerModel)::find($modelId);

        $ds = new DataSource($this->config->model, 'name');

        //$dataSource = \Waka\Utils\Models\DataSource::where('model', '=', $this->config->model)->first();
        $dotedValues = $ds->getSimpleDotedValues($modelId);
        trace_log($dotedValues);

        //$returnFields = new ParseFields();
        //$this->fields = $returnFields->parseFields($model, $this->config->fields);
        $this->fields = $this->setValues($dotedValues, $this->config->fields);
    }

    public function setValues($dotedValues, $fields)
    {
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

            $field = [
                'type' => $type,
                'icon' => $icon,
                'label' => $label,
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

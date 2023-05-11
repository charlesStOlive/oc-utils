<?php namespace Waka\Utils\Columns;

use Backend\Classes\ListColumn;
use Model;

class WorkflowColumn
{
    /**
     * Default field configuration
     * all these params can be overrided by column config
     * @var array
     */
    private static $defaultFieldConfig = [
        'showWicon' => true,
        'useWcolor' => null,
    ];

    private static $listConfig = [];

    /**
     * @param       $field
     * @param array $config
     *
     * @internal param $name
     */
    public static function storeFieldConfig($field, array $config)
    {
        self::$listConfig[$field] = array_merge(self::$defaultFieldConfig, $config, ['name' => $field]);
    }

    /**
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     *
     * @return string HTML
     */
    public static function render($value, ListColumn $column, Model $record)
    {
        $field = new self($value, $column, $record);
        $config = $field->getConfig();
        return $field->getLabel();
    }

    /**
     * ListSwitchField constructor.
     *
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     */
    public function __construct($value, ListColumn $column, Model $record)
    {
        $this->name = $column->columnName;
        $this->value = $value;
        $this->column = $column;
        $this->record = $record;
    }

    /**
     * @param $config
     *
     * @return mixed
     */
    private function getConfig($config = null)
    {
        //trace_log(self::$listConfig);

        if (is_null($config)) {
            return self::$listConfig[$this->name];
        }

        return self::$listConfig[$this->name][$config];
    }

    /**
     * Return data-request-data params for the switch button
     *
     * @return string
     */
    public function getLabel()
    {
        $place = $this->record->state;
        if (!$place) {
            $arrayPlaces = $this->record->getWakaWorkflow()->getMarking($this->record)->getPlaces();
            $place = array_key_first($arrayPlaces);
        }
        $placeMetadata = $this->record->getWakaWorkflow()->getMetadataStore()->getPlaceMetadata($place);
        $icon = null;
        $label = \Lang::get($placeMetadata['label'] ?? $place);
        $color = $placeMetadata['color'] ?? null;
        if($color) {
            $color = 'text-'.$color;
        }
        //trace_log($this->getConfig('showWicon'));
        //trace_log($color);
        if ($this->getConfig('showWicon')) {
            $icon = $placeMetadata['icon'] ?? null;
            $icon = "<i class='" . $icon . "'></i>";
            return "<div class='$color'>".$icon .' '. $label."</div>";
        } else {
            return $label; 
        }
    }
}

<?php namespace Waka\Utils\Columns;

use Backend\Classes\ListColumn;
use Lang;
use Model;

class BtnActions
{
    /**
     * Default field configuration
     * all these params can be overrided by column config
     * @var array
     */
    private static $defaultFieldConfig = [
        'icon' => 'icon-wrench',
        'request' => 'onLoadActionPopup',
    ];

    private static $listConfig = [];
    public static $config;

    /**
     * @param       $field
     * @param array $config
     *
     * @internal param $name
     */
    public static function storeFieldConfig($field, array $config)
    {
        trace_log(self::$defaultFieldConfig);
        self::$listConfig[$field] = array_merge(self::$defaultFieldConfig, $config, ['name' => $field]);
        trace_log(self::$listConfig[$field]);
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
        
            return '
<a href="javascript:;"
    data-control="popup"
    data-size="huge"
    data-handler="onLoadActionPopup"
    data-request-data="' . $field->getRequestData() . '"
    title="Actions">
    <i class="icon-wrench icon-lg"></i>
</a>
';   
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
     * Return data-request-data params for the switch button
     *
     * @return string
     */
    public function getRequestData()
    {
        $modelClass = str_replace('\\', '\\\\', get_class($this->record));

        $data = [
            "id: {$this->record->{$this->record->getKeyName()}}",
            "field: '$this->name'",
            "model: '$modelClass'"
        ];

        if (post('page')) {
            $data[] = "page: " . post('page');
        }

        return implode(', ', $data);
    }
}
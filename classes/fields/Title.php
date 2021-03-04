<?php namespace Waka\Utils\Classes\fields;

class Title extends BaseField
{
    public $partial = 'title';
    public $headingLevel = 'h2';

    public function __construct($model, $key, $config)
    {
        parent::__construct($model, $key, $config);
        $this->setHeadingLevel();
    }

    public function setHeadingLevel()
    {
        $headingLevel = $this->config['headingLevel'] ?? false;
        if ($headingLevel) {
            $this->headingLevel = $headingLevel;
        }
    }
}

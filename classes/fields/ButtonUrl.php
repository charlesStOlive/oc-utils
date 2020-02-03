<?php namespace Waka\Utils\Classes\fields;


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
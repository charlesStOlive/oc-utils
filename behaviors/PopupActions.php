<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class PopupActions extends ControllerBehavior
{
	public function __construct($controller)
    {
        parent::__construct($controller);
    }
     //ci dessous tous les calculs pour permettre l'import excel. 
    public function onLoadActionPopup()
    {
        $this->vars['model'] = post('model');
        $this->vars['id'] = post('id');
        return $this->makePartial('$/waka/utils/behaviors/popupactions/_popup.htm');
    }
}
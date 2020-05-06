<?php namespace Waka\Utils\Components;

use Cms\Classes\ComponentBase;

class GestionKey extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'gestionKey Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRemoveKey()
    {
        $key = $this->param('key');
        $source = \Waka\Utils\Models\SourceLog::where('key', $key)->first();
        $source->user_delete_key = true;
        $source->save();
        return \Redirect::to('/lp/deleted_cod');
    }
}

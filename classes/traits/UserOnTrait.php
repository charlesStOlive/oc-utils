<?php namespace Waka\Utils\Classes\Traits;

use \Waka\Informer\Models\Inform;

trait UserOnModel
{

    /*
     * Constructor
     */
    public static function bootWorkflowTrait()
    {
        static::extend(function ($model) {
            /*
             * Define relationships
             */
            $model->morphMany['user_on'] = [
                'Waka\Utils\Models\UserOn',
                'name' => 'usereable',
            ];

            $model->bindEvent('model.beforeCreate', function () use ($model) {
                $user = BackendAuth::getUser();
                if (!$model->user_creator) {
                    $model->user_creator = $user;
                }
            });
        });
    }
}

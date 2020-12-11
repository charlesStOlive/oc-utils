<?php namespace Waka\Utils\Classes\Traits;

use \Waka\Informer\Models\Inform;

trait UserCreatorTrait
{

    /*
     * Constructor
     */
    public static function bootUserCreatorTrait()
    {
        static::extend(function ($model) {
            /*
             * Define relationships
             */
            $model->morphOne['user_creator'] = [
                'Waka\Utils\Models\UserCreator',
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

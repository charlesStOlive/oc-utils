<?php namespace Waka\Utils\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'waka_utils_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

}

<?php

namespace Waka\Utils\Classes\Validators;

use Winter\Storm\Validation\Rule;

class DimensionsMin extends Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    private $params;
    public $error;

    public function passes($attribute, $value)
    {
        //trace_log('test', $attribute);
        if ($value === null) {
            return true; // Sortir de la validation sans erreur
        }
        [$minWidth, $minHeight] = $this->params;
        if (is_array($value)) {
            foreach ($value as $key => $image) {
                [$width, $height] = getimagesize($image->getRealPath());
                //trace_log([$width, $minWidth, $height, $minHeight]);
                if ($width < $minWidth || $height < $minHeight) {
                    $this->error = '#' . $key;
                    return false;
                }
            }
        } else {
            [$width, $height] = getimagesize($value->getRealPath());
            if ($width < $minWidth || $height < $minHeight) {
                $this->error = '#';
                return false;
            }
        }
        return true;
    }

    public function validate($attribute, $value, $params)
    {
        $this->params = $params;
        return $this->passes($attribute, $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Image to small';
    }
}

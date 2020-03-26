<?php namespace Waka\Utils\Classes\Traits;

trait ConvertPx
{
    public function convertStringToPx($value)
    {
        if (ends_with($value, 'mm')) {
            $value = rtrim($value, "mm");
            return $this->mmToPx($value);

        }
        if (ends_with($value, 'cm')) {
            $value = rtrim($value, "cm");
            return $this->cmToPx($value);

        }

    }
    public function mmToPx($mm, $dpi = 96)
    {
        //return intval($mm / 10 * 2.54 / 96);
        return intval($mm / 10 * $dpi / 2.54);

    }
    public function cmToPx($cm)
    {
        return intval($cm * $dpi / 96);
    }
}

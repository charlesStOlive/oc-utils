<?php namespace Waka\Utils\Classes;

use Carbon\Carbon;

class WakaDate
{

    public function addWorkingDays($date, $number)
    {

        for ($i = 1; $i <= $number; $i++) {
            $date->addDay();
            if ($date->isWeekend()) {
                $date->addDays(2);
            }
        }
        return $date;
    }
    public function countWorkingDays($date, $date2)
    {
        $number = $date->diffInDays($date2);

        for ($i = 1; $i <= $number; $i++) {
            $date->addDay();
            if ($date->isWeekend()) {
                $number--;
            }
        }
        return $number;
    }
    public function localeDate($twig, $format = null, $timeZone = null)
    {
        if (!$twig) {
            return "inc";
        }

        if (is_string($twig)) {
            $twig = Carbon::parse($twig);
        }

        $user = \BackendAuth::getUser();

        if (!$timeZone && $user) {
            $timeZone = \Backend\Models\Preference::get('timezone');
        }
        if (!$format) {
            $format = "date";
        }
        if ($format == 'date') {
            $format = "%A %e %B";
        }
        if ($format == 'date-medium') {
            $format = "%a %e %b";
        }
        if ($format == 'date-full') {
            $twig->setTimezone($timeZone);
            $format = "%A %e %B %Y";
        }
        if ($format == 'date-tiny') {
            $format = "%A %e %B %Y";
        }
        if ($format == 'date-time') {
            $twig->setTimezone($timeZone);
            $format = "%A %e %B %Y à %H:%M";
        }
        if ($format == 'date-short') {
            $format = "%d/%m/%Y";
        }
        if ($format == 'date-short-time') {
            $twig->setTimezone($timeZone);
            $format = "%d/%m/%Y à %H:%M";
        }

        return $twig->formatLocalized($format);
    }
}

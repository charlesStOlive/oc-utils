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

        $twig = $twig->locale('fr_FR');

        if (!$timeZone && $user) {
            $timeZone = \Backend\Models\Preference::get('timezone');
        }
        
        $isoFormat =  "DD MMM YYYY";
        if ($format == 'date-tiny') {
            $isoFormat = "D/M/YY";
        }
        if ($format == 'date-short') {
            $isoFormat = "DD/MM/YY";
        }
        if ($format == 'date-medium') {
            $isoFormat = "DD MMM YYYY";
        }
        if ($format == 'date') {
            //Deja fait
        }
        if ($format == 'date-full') {
            $isoFormat = "dddd DD MMMM YYYY";
        }
        if ($format == 'date-time') {
            $twig->setTimezone($timeZone);
            $isoFormat = "DD/MM/YY à HH:SS ";
        }
        if ($format == 'date-time-full') {
            $twig->setTimezone($timeZone);
            $isoFormat = "LLLL";
        }
        //trace_log('--------------');
        //trace_log('D/M/YY '.$twig->isoFormat('D/M/YY'));
        //trace_log($twig->isoFormat('DD/MM/YY'));
        //trace_log($twig->isoFormat('DD MMM YYYY'));
        //trace_log($twig->isoFormat('DD/MMMM/YYYY'));
        //trace_log($twig->isoFormat('dddd DD MMMM YYYY'));
        //trace_log($twig->isoFormat('DD/MM/YY à HH:SS'));
        //trace_log($twig->isoFormat('LLLL'));
        //trace_log('--------------FIN');
        //trace_log($isoFormat);

        return $twig->isoFormat($isoFormat);
    }
}

<?php namespace Waka\Utils\Classes\Traits;

use Carbon\Carbon;

trait ScopePeriodes
{
    public function scopeWakaPeriode($request, $periode, $column)
    {
        $year = Carbon::now()->year;

        // A FAIRE UN SYSTEME  un systh_me plus inteligeny 
        // $periodeArray = explode('_', $periode);
        // $periode = $periodeArray[0];
        // $timeToRemove = $periodeArray[1] ?? 0;

        if ($periode == 'all') {
            return $request;
        }
        if ($periode == 'd-30_to_now') {
            $date = Carbon::now();
            $start_at = $date->copy()->subDays(30);
            $end_at = $date;
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'd-365_to_now') {
            $date = Carbon::now();
            $start_at = $date->copy()->subDays(365);
            $end_at = $date;
            //trace_log($periode." = ".$start_at." => ".$end_at);
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'd-365&y-1_to_now&y-1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->subDays(365);
            $end_at = $date;
            //trace_log($periode." = ".$start_at." => ".$end_at);
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm-6_to_now') {
            $date = Carbon::now();
            $start_at = $date->copy()->subMonths(6);
            $end_at = $date;
            //trace_log( $start_at->format('d/m/Y'));
            //trace_log($end_at->format('d/m/Y'));
            //trace_log($periode." = ".$start_at." => ".$end_at);
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm-6&y-1_to_now&y-1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->subMonths(6);
            $end_at = $date;
            //trace_log( $start_at->format('d/m/Y'));
            //trace_log($end_at->format('d/m/Y'));
            //trace_log($periode." = ".$start_at." => ".$end_at);
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'y_to_now') {
            $date = Carbon::now();
            $start_at = $date->copy()->startOfYear();
            $end_at = $date;
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'y-1_to_now&y-1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->startOfYear();
            $end_at = $date;
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'y') {
            return $request->whereYear($column, $year);
        }
        if ($periode == 'y-1') {
            $year = Carbon::now()->subYear()->year;
            return $request->whereYear($column, $year);
        }
        if ($periode == 'q') {
            $date = Carbon::now();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'q-1') {
            $date = Carbon::now()->subQuarter();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'q&y-1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'q-1&y-1') {
            $date = Carbon::now()->subQuarter()->subYear();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm') {
            $date = Carbon::now();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm-1') {
            $date = Carbon::now()->subMonth();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm&y-1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm-1&y-1') {
            $date = Carbon::now()->subMonth()->subYear();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        \Log::error('ScopePeriode : filtre pas trouvé : '.$periode);
        return 0;

    }

    public function getPeriode()
    {
        return  [
            'all' => "Tout le temps",
            'd-30_to_now' => "Trentes derniers jours",
            'd-365_to_now' => "Les 365 derniers jours",
            'd-365&y-1_to_now&y-1' => "Les 365 derniers jours depuis N-1",
            'm-6_to_now' => "Les 6 derniers mois",
            'm-6&y-1_to_now&y-1' => "Les 6 derniers mois de l'année préscedente",
            'y_to_now' => "Debut année jusqu'à aujourd'hui",
            'y-1_to_now&y-1' => "Debut année dernière jusqu'à jour anné dernière",
            'y' => "Année N",
            'y-1' => "Année dernière",
            'q' => 'Trimestre T',
            'q-1' => "Trimestre T-1",
            'q&y-1' => "T N-1 ( trimestre  de l'année précédente)",
            'q-1&y-1' => "T-1 N-1 ( trimestre prescedent de l'année précédente)",
            'm' => 'Mois M',
            'm-1' => "M-1",
            'm&y-1' => "M-1 N-1 ( mois  de l'année précédente)",
            'm-1&y-1' => "M-1 N-1 ( mois prescedent de l'année précédente)",
        ];
    }

    public function listPeriode()
    {
        return  $this->getPeriode();
    }

    public function getPeriodeConfig($label = 'Choisssez une période', $span = "full")
    {
        return [
            'label' => 'Choisssez une période',
            'type' => 'dropdown',
            'span' => $span,
            'options' => $this->getPeriode(),
        ];
    }
}

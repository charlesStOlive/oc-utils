<?php namespace Waka\Utils\Classes\Traits;

use Carbon\Carbon;

trait ScopePeriodes
{
    public function scopeWakaPeriode($request, $periode, $column)
    {
        $year = Carbon::now()->year;

        if ($periode == 'all') {
            return $request;
        }
        if ($periode == 'd_30') {
            $date = Carbon::now();
            $start_at = $date->copy()->subDays(30);
            $end_at = $date;
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'y') {
            return $request->whereYear($column, $year);
        }
        if ($periode == 'y_1') {
            $year = Carbon::now()->subYear()->year;
            return $request->whereYear($column, $year);
        }
        if ($periode == 't') {
            $date = Carbon::now();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 't_1') {
            $date = Carbon::now()->subQuarter();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 't_n_1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->startOfQuarter();
            $end_at = $date->copy()->endOfQuarter();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 't_1_n_1') {
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
        if ($periode == 'm_1') {
            $date = Carbon::now()->subMonth();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm_n_1') {
            $date = Carbon::now()->subYear();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }
        if ($periode == 'm_1_n_1') {
            $date = Carbon::now()->subMonth()->subYear();
            $start_at = $date->copy()->startOfMonth();
            $end_at = $date->copy()->endOfMonth();
            return $request->whereBetween($column, [$start_at, $end_at]);
        }

    }

    public function getPeriode()
    {
        return  [
            'all' => "Tout le temps",
            'd_30' => "Trentes derniers jours",
            'y' => "Année N",
            'y_1' => "N-1",
            't' => 'Trimestre T',
            't_1' => "T-1",
            't_n_1' => "T N-1 ( trimestre  de l'année précédente)",
            't_1_n_1' => "T-1 N-1 ( trimestre prescedent de l'année précédente)",
            'm' => 'Mois M',
            'm_1' => "M-1",
            'm_n_1' => "M-1 N-1 ( mois  de l'année précédente)",
            'm_1_n_1' => "M-1 N-1 ( mois prescedent de l'année précédente)",
        ];
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

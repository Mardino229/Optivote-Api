<?php

namespace App\Http\Controllers\Utils;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UtilsController extends Controller
{

    public static function calculerDuree($date)
    {
        $today = Carbon::today();
        $date2 = Carbon::parse($date);

        $diff = $today->diff($date2);

        $resultat = "{$diff->d}:{$diff->h}:{$diff->i}:{$diff->s}";

        return $resultat;
    }

    public static function before ($date) {
        $today = Carbon::today();
        $date = Carbon::parse($date);
        return $today->lessThan($date);
    }
    public static function between ($start_date, $end_date) {
        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);
        $today = Carbon::today();
        return $today->between($startDate, $endDate);
    }

    public static function beforeus ($before ,$end_date) {
        $endDate = Carbon::parse($end_date);
        $before = Carbon::parse($before);
        return $before->lessThan($endDate);
    }

}

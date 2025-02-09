<?php

namespace App\Http\Controllers\Utils;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UtilsController extends Controller
{

    public static function calculerDuree($date)
    {
        $now = Carbon::now(); // Utiliser now() au lieu de today() pour avoir l'heure actuelle
        $date2 = Carbon::parse($date);

        $diff = $now->diff($date2);

        // Formater avec leading zeros si nécessaire
        return sprintf(
            "%d:%02d:%02d:%02d",
            $diff->d,
            $diff->h,
            $diff->i,
            $diff->s
        );
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

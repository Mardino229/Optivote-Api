<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidat;
use App\Models\Election;
use App\Models\Resultat;
use Illuminate\Http\Request;

class ResultatController extends Controller
{
    public static function add (int $election_id, int $candidat_id)
    {
        $resultat = Resultat::where('election_id', $election_id)->where('candidat_id', $candidat_id)->first();
        $resultats = Resultat::all()->where('election_id', $election_id);
        $resultat->nbr_vote += 1;
        $resultat->save();
        foreach ($resultats as $result) {
            $result->percentage = self::calculatePercentage($election_id, $result->candidat_id);
            $result->save();
        }
    }
    public static function remove (int $election_id, int $candidat_id)
    {
        $resultat = Resultat::where('election_id', $election_id)->where('candidat_id', $candidat_id)->first();
        $resultat->nbr_vote -= 1;
        $resultat->save();
        $resultats = Resultat::all()->where('election_id', $election_id);
        foreach ($resultats as $result) {
            $result->percentage = self::calculatePercentage($election_id, $result->candidat_id);
            $result->save();
        }
    }

    /**
     * Affiche les résultats d'une élection et met à jour son statut si un candidat dépasse 50 % des votes.
     *
     * Cette méthode récupère les résultats d'une élection en fonction de l'ID fourni.
     * Si un des candidats a un pourcentage supérieur à 50 %, le statut de l'élection est mis à jour à "true".
     *
     * @group Resultat
     *
     * @urlParam election_id integer required L'ID de l'élection dont on veut les résultats. Exemple : 1
     *
     * @response 200 {
     *    "success": true,
     *    "message": "",
     *    "data": [
     *        [
     *            {
     *                "id": 1,
     *                "election_id": 1,
     *                "candidat_id": 2,
     *                "votes": 100,
     *                "percentage": 60
     *            },
     *            {
     *                "id": 2,
     *                "election_id": 1,
     *                "candidat_id": 3,
     *                "votes": 40,
     *                "percentage": 40
     *            }
     *        ],
     *        true
     *    ]
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Election introuvable",
     *    "data": null
     * }
     *
     * @param int $election_id L'ID de l'élection.
     */
    public function index (int $election_id)
    {
        $election = Election::find($election_id);
        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }
        $resultats = Resultat::all()->where('election_id', $election_id);
        $election = Election::find($election_id);
        foreach ($resultats as $resultat) {
            if ($resultat->percentage > 50 ) {
                $election->status = true;
                $election->save();
                break;
            }
        }
        return ResponseApiController::apiResponse(true, "", [$resultats, $election->status]);
    }

    public static function calculatePercentage (int $election_id, int $candidat_id)
    {
        $resultats = Resultat::all()->where('election_id', $election_id);
        $resultatCandidat = Resultat::where('election_id', $election_id)->where('candidat_id', $candidat_id)->first();
        $total = 0;
        foreach ($resultats as $resultat) {
            $total += $resultat->nbr_vote;
        }
        return ($resultatCandidat->nbr_vote / $total) * 100;

    }
}

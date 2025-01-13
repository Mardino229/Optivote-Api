<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\UtilsController;
use App\Http\Requests\VoteRequest;
use App\Models\Candidat;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    /**
     * Affiche tous les votes pour une élection donnée.
     *
     * @group Votes
     *
     * @urlParam election_id integer required L'identifiant de l'élection. Example: 3
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "data": [
     *     {
     *       "id": 1,
     *       "election_id": 3,
     *       "candidat_id": 5,
     *       "voter_id": 2,
     *       "created_at": "2025-01-01T12:00:00",
     *       "updated_at": "2025-01-01T12:00:00"
     *     }
     *   ]
     * }
     */
    public function index(int $election_id)
    {
        $votes = Vote::where('election_id', $election_id)->get();
        return ResponseApiController::apiResponse(true, '', $votes);
    }

    /**
     * Crée un nouveau vote.
     *
     * @group Votes
     *
     * @bodyParam election_id integer required L'identifiant de l'élection. Example: 3
     * @bodyParam candidat_id integer required L'identifiant du candidat. Example: 5
     * @bodyParam voter_id integer required L'identifiant du votant. Example: 2
     * @response 201 {
     *   "success": true,
     *   "message": "Vote enregistré avec succès",
     *   "data": {
     *     "id": 1,
     *     "election_id": 3,
     *     "candidat_id": 5,
     *     "voter_id": 2,
     *     "created_at": "2025-01-01T12:00:00",
     *     "updated_at": "2025-01-01T12:00:00"
     *   }
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous n'êtes pas autorisé à voter en cette période",
     *   "data": null
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous avez déja voté",
     *   "data": []
     * }
     */
    public function store(VoteRequest $request)
    {
        $vote = Vote::where("user_id", $request->user_id)->where("election_id", $request->election_id)->first();
        if ($vote) {
            return ResponseApiController::apiResponse(false, "Vous avez déjà voté", [] , 406);
        }

        $election = Election::find($request->election_id);

        if (UtilsController::between($election->start_date, $election->end_date)) {
            $vote = Vote::create($request->validated());
            ResultatController::add($election->id, $request->candidat_id);
            return ResponseApiController::apiResponse(true, "Vote enregistré avec succès", [], 201);
        }

        return ResponseApiController::apiResponse(false, "Vous n'êtes pas autorisé à voter en cette période", [], 406);
    }

    /**
     * Vérifie si un utilisateur a déja voté.
     *
     * @group Votes
     *
     * @urlParam user_id integer required L'identifiant de l'utilisateur. Example: 1
     * @urlParam election_id integer required L'identifiant de l'éléction. Example: 1
     * @response 200 {
     *     "success": false,
     *     "message": "Vous avez déja voté",
     *     "data": []
     *  }
     * @response 200 {
     *     "success": true,
     *     "message": "Vous pouvez voté",
     *     "data": []
     *  }
     */
    public function verifyVote(int $user_id, int $election_id)
    {
        $vote = Vote::where("user_id", $user_id)->where("election_id", $election_id)->first();
        if ($vote ==  null) {
            return ResponseApiController::apiResponse(true, "Vous n'avez pas encore voté", [] );
        }
        return ResponseApiController::apiResponse(false, "Vous avez déja voté", [] );

    }

//    /**
//     * Met à jour un vote.
//     *
//     * @group Votes
//     *
//     * @urlParam id integer required L'identifiant du vote. Example: 1
//     * @bodyParam election_id integer required L'identifiant de l'élection. Example: 3
//     * @bodyParam candidat_id integer required L'identifiant du candidat. Example: 5
//     * @response 200 {
//     *   "success": true,
//     *   "message": "Vote mis à jour avec succès",
//     *   "data": {
//     *     "id": 1,
//     *     "election_id": 3,
//     *     "candidat_id": 5,
//     *     "voter_id": 2,
//     *     "created_at": "2025-01-01T12:00:00",
//     *     "updated_at": "2025-01-01T12:30:00"
//     *   }
//     * }
//     * @response 406 {
//     *   "success": false,
//     *   "message": "Vous ne pouvez plus modifier votre vote",
//     *   "data": null
//     * }
//     */
//    public function update(VoteRequest $request, int $id)
//    {
//        $election = Election::find($request->election_id);
//
////        if (UtilsController::between($election->start_date, $election->end_date)) {
//            $vote = Vote::findOrFail($id);
//            $vote->update($request->validated());
//
//            return ResponseApiController::apiResponse(true, "Vote mis à jour avec succès", $vote, 200);
////        }
//        //return ResponseApiController::apiResponse(false, "Vous ne pouvez plus modifier votre vote", null, 406);
//    }

    /**
     * Supprime un vote.
     *
     * @group Votes
     *
     * @urlParam id integer required L'identifiant du vote. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Vote retiré avec succès",
     *   "data": null
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous ne pouvez plus retirer votre vote",
     *   "data": null
     * }
     */
//    public function destroy(int $id)
//    {
//        $vote = Vote::findOrFail($id);
//
//        $election = Election::find($vote->election_id);
//
//        if (UtilsController::between($election->start_date, $election->end_date)) {
//            $vote->delete();
//            ResultatController::remove($election->id, $vote->candidat_id);
//            return ResponseApiController::apiResponse(true, "Vote retiré avec succès", null);
//        }
//
//        return ResponseApiController::apiResponse(false, "Vous ne pouvez plus retirer votre vote", null, 406);
//    }
}

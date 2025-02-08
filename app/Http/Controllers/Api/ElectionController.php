<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\UtilsController;
use App\Http\Requests\ElectionRequest;
use App\Http\Requests\ElectionSecondRequest;
use App\Models\Candidat;
use App\Models\Election;
use App\Models\Resultat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ElectionController extends Controller
{

    /**/
    public function dashboard(){
        $nbr = Election::count();
        return ResponseApiController::apiResponse(true, '', $nbr);
}

    /**
     * Retrieve all elections.
     *
     * @group Elections
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": [
     *     {
     *       "id": 1,
     *       "name": "Presidential Election",
     *       "start_date": "2024-01-01",
     *       "end_date": "2024-01-10",
     *       "status": true
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $elections = Election::orderBy('created_at', 'desc')->get();
        return ResponseApiController::apiResponse(true, '', $elections);
    }

    /**
     * @group Elections
     *
     * Retrieving election details This route allows you to retrieve the details of an election, including the leading candidates and the time remaining.
     *
     *
     * @urlParam id int Required. id of Election.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": {
     *     "delay": "02:15:30:10",
     *     "nbr_vote": 1500,
     *     "lead": [
     *       "Candidat 1",
     *       "Candidat 2"
     *     ]
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Election introuvable",
     *   "body": null
     * }
     */
    public function detail($id) {
        $election = Election::find($id);
        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }

        $resultats = Resultat::all()->where('election_id', $id);
        $total = 0;
        foreach ($resultats as $resultat) {
            $total += $resultat->nbr_vote;
        }

       if (UtilsController::before($election->start_date)){
           $candidats = Candidat::where('election_id', $id)
               ->orderBy('name', 'asc')
               ->take(2)
               ->pluck('name');
           $delay = UtilsController::calculerDuree($election->start_date);
           $details = new ElectionDetails($delay, $total, $candidats);
           return ResponseApiController::apiResponse(true, '', $details);
       }

        $candidats = Candidat::where('election_id', $id)
            ->orderBy('percentage', 'desc')
            ->take(2)
            ->pluck('name');

       if (UtilsController::between($election->start_date, $election->end_date)){
           $delay = UtilsController::calculerDuree($election->end_date);
           $details = new ElectionDetails($delay, $total, $candidats);
           return ResponseApiController::apiResponse(true, '', $details);
       }

        $delay = "00:00:00:00";
        $details = new ElectionDetails($delay, $total, $candidats);

        return ResponseApiController::apiResponse(true, '', $details);
    }

    /**
     * Retrieve elections currently in progress.
     *
     * @group Elections
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": [
     *     {
     *       "id": 2,
     *       "name": "Midterm Election",
     *       "start_date": "2024-12-25",
     *       "end_date": "2025-01-05",
     *       "status": true
     *     }
     *   ]
     * }
     */
    public function election_inprogress()
    {
        $today = Carbon::today();
        $currentElections = Election::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get()
            ->filter(function ($election) {
                return $election->candidates()->count() >= 2;
            });

        return ResponseApiController::apiResponse(true, '', $currentElections);
    }

    /**
     * Retrieve completed elections.
     *
     * @group Elections
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": [
     *     {
     *       "id": 1,
     *       "name": "Presidential Election",
     *       "start_date": "2024-01-01",
     *       "end_date": "2024-01-10",
     *       "status": false
     *     }
     *   ]
     * }
     */
    public function election_completed()
    {
        $today = Carbon::today();
        $ancientElections = Election::where('end_date', '<', $today)->get();
        return ResponseApiController::apiResponse(true, '', $ancientElections);
    }

    /**
     * Retrieve elections that have not started.
     *
     * @group Elections
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": [
     *     {
     *       "id": 3,
     *       "name": "Upcoming Election",
     *       "start_date": "2025-02-01",
     *       "end_date": "2025-02-10",
     *       "status": false
     *     }
     *   ]
     * }
     */
    public function election_notStarted()
    {
        $today = Carbon::today();
        $newElections = Election::where('start_date', '>', $today)->get();
        return ResponseApiController::apiResponse(true, '', $newElections);
    }

    /**
     * Create a new election.
     *
     * @group Elections
     * @bodyParam name string required The name of the election. Example: Presidential Election
     * @bodyParam start_date date required The start date of the election. Example: 2024-01-01
     * @bodyParam end_date date required The end date of the election. Example: 2024-01-10
     * @response 201 {
     *   "success": true,
     *   "message": "Election created successfully",
     *   "body": {
     *     "id": 1,
     *     "name": "Presidential Election",
     *     "start_date": "2025-01-01",
     *     "end_date": "2025-01-10",
     *     "status": true
     *   }
     * }
     * @response 406 {
     *   "success": true,
     *   "message": "Validation des données échouée. ",
     *   "body": [erreur de validation]
     * }
     */
    public function store(ElectionRequest $request)
    {
        $election = Election::create($request->all());
        return ResponseApiController::apiResponse(true, 'Election created successfully', $election, 201);
    }

    /**
     * Create a second round of an election.
     *
     * @group Elections
     * @urlParam id integer required The ID of the election. Example: 1
     * @bodyParam required The start date of the election. Example: 2024-01-01
     * @bodyParam required end_date date required The end date of the election. Example: 2024-01-10
     * @response 201 {
     *   "success": true,
     *   "message": "Election du deuxième tour crée avec succès",
     *   "body": {
     *      "id": 1,
     *      "name": "Presidential Election Second Tour",
     *      "start_date": "2025-01-01",
     *      "end_date": "2025-01-10",
     *      "status": true
     *    }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Vous ne pouvez pas créer de second tour pour cette élection",
     *   "body": []
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Opération échoué. L'élection n'est pas terminée",
     *   "body": []
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Opération échoué. Le second tour doit commencer à la fin du premier tour",
     *   "body": []
     * }
     * @response 404 {
     *     "success": false,
     *     "message": "Election introuvable",
     *     "body": null
     * }
     */
    public function second(int $election_id, ElectionSecondRequest $request)
    {
        $election = Election::find($election_id);

        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }

        if (UtilsController::before($election->end_date)) {
            return ResponseApiController::apiResponse(false, "Opération échoué. L'élection n'est pas terminée", [], 400);
        }

        if (UtilsController::beforeus($request->start_date, $election->end_date)) {
            return ResponseApiController::apiResponse(false, "Opération échoué. Le second tour doit commencer à la fin du premier tour", [], 400);
        }

        $resultats = Resultat::all()->where('election_id', $election_id);
        $second = true;
        foreach ($resultats as $resultat) {
            if ($resultat->percentage > 50) {
                $second = false;
                break;
            }
        }
        if ($second) {
            $seconde = Election::create([
                "name" => $election->name . " 2ème tour",
                "start_date" => $request->start_date,
                "end_date" => $request->end_date,
            ]);

            $resultats = Resultat::where('election_id', $election_id)
                ->orderBy('percentage', 'desc')
                ->take(2)
                ->get();

            foreach ($resultats as $resultat) {
                $candidate = Candidat::find($resultat->candidat_id);
                $second_candidate = Candidat::create([
                    'npi' => $candidate->npi,
                    'election_id' => $seconde->id,
                    'description' => $candidate->description,
                    'photo' => $candidate->photo,
                ]);
                Resultat::create([
                    'election_id' => $seconde->id,
                    'candidat_id' => $second_candidate->id,
                ]);
            }

            return ResponseApiController::apiResponse(true, "Election du deuxième tour crée avec succès", $seconde, 201);
        }
        return ResponseApiController::apiResponse(false, "Vous ne pouvez pas créer de second tour pour cette élection", [], 400);
    }

    /**
     * Retrieve a specific election.
     *
     * @group Elections
     * @urlParam id integer required The ID of the election. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": {
     *     "id": 1,
     *     "name": "Presidential Election",
     *     "start_date": "2024-01-01",
     *     "end_date": "2024-01-10",
     *     "status": true
     *   }
     * @response 404 {
     *   "success": false,
     *   "message": "Election introuvable",
     *   "body": null
     * }
     */
    public function show(int $id)
    {
        $election = Election::find($id);
        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }
        return ResponseApiController::apiResponse(true, '', $election);
    }

    /**
     * Update an election.
     *
     * @group Elections
     * @urlParam id integer required The ID of the election to update. Example: 1
     * @bodyParam name string The name of the election. Example: Updated Election
     * @bodyParam start_date date The updated start date of the election. Example: 2024-01-05
     * @bodyParam end_date date The updated end date of the election. Example: 2024-01-15
     * @response 200 {
     *   "success": true,
     *   "message": "Election mis à jour avec succès",
     *   "body": {
     *     "id": 1,
     *     "name": "Updated Election",
     *     "start_date": "2024-01-05",
     *     "end_date": "2024-01-15",
     *     "status": true
     *   }
     * }
     * @response 404 {
     *    "success": false,
     *    "message": "Election introuvable",
     *    "body": null
     *  }
     */
    public function update(ElectionRequest $request, int $id)
    {
        $election = Election::find($id);
        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }
        if (UtilsController::before($election->start_date)) {
            $election->update($request->validated());
            return ResponseApiController::apiResponse(true, 'Election mis à jour avec succès', $election);
        }
        return ResponseApiController::apiResponse(false, 'Vous ne pouvez pas modifier une élection après la date de lancement', '', 406);
    }

    /**
     * Delete an election.
     *
     * @group Elections
     * @urlParam id integer required The ID of the election to delete. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Election supprimé avec succès",
     *   "body": {}
     * }
     * @response 404 {
     *     "success": false,
     *     "message": "Election introuvable",
     *     "body": null
     *   }
     */
    public function destroy(int $id)
    {
        $election = Election::find($id);
        if ($election ==  null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", [] , 404);
        }
        $election->delete();
        return ResponseApiController::apiResponse(true, 'Election supprimé avec succès');
    }
}


class ElectionDetails
{
    public $delay;
    public $nbr_vote;
    public $lead;

    public function __construct($delay, $nbr_vote, $lead)
    {
        $this->delay = $delay;
        $this->nbr_vote = $nbr_vote;
        $this->lead = $lead;
    }
}

class Dashboard
{
    public $nbr_elections;
    public $nbr_votants;

    public function __construct($nbr_elections, $nbr_votants)
    {
        $this->nbr_elections = $nbr_elections;
        $this->nbr_votants = $nbr_votants;
    }
}


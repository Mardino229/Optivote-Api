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
        $elections = Election::all();
        return ResponseApiController::apiResponse(true, '', $elections);
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
            ->get();
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.after' => 'La date de début doit être postérieure à aujourd’hui.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
        ]);
        if ($validator->fails()) {
            return ResponseApiController::apiResponse(false,'Validation des données échouée.',
                $validator->errors(), 400);
        }
        $election = Election::create($request->all());
        return ResponseApiController::apiResponse(true, 'Election created successfully', $election, 201);
    }

    /**
     * Create a second round of an election.
     *
     * @group Elections
     * @urlParam election_id integer required The ID of the election. Example: 1
     * required The start date of the election. Example: 2024-01-01
     * @bodyParam end_date date required The end date of the election. Example: 2024-01-10
     * @response 201 {
     *   "success": true,
     *   "message": "Election du deuxième tour crée avec succès",
     *   "body": []
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
            $second = Election::create([
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
                    'election_id' => $second->id,
                    'description' => $candidate->description,
                    'photo' => $candidate->photo,
                ]);
                Resultat::create([
                    'election_id' => $second->id,
                    'candidat_id' => $second_candidate->id,
                ]);
            }

            return ResponseApiController::apiResponse(true, "Election du deuxième tour crée avec succès", [], 201);
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
            return ResponseApiController::apiResponse(false, "Election introuvable", $election , 404);
        }
        $election->delete();
        return ResponseApiController::apiResponse(true, 'Election supprimé avec succès', $election);
    }
}
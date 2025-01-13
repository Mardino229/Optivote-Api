<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\UtilsController;
use App\Http\Requests\CandidatRequest;
use App\Models\Candidat;
use App\Models\Election;
use App\Models\Person;
use App\Models\Resultat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidatController extends Controller
{
    /**
     * List all candidates for an election.
     *
     * This endpoint retrieves all candidates for a specific election.
     *
     * @group Candidates
     * @urlParam election_id integer required The ID of the election. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "npi": 123456789,
     *       "election_id": 1,
     *       "description": "Une description du candidat",
     *       "photo": "candidats/photos/johndoe.jpg"
     *     }
     *   ]
     * }
     *@response 200 {
     *   "success": false,
     *   "message": "Election introuvable",
     *   "body": null
     * }
     *
     */
    public function index(int $election_id)
    {
        $election = Election::find($election_id);
        if ($election == null) {
            return ResponseApiController::apiResponse(false, "Election introuvable", $election, 404);
        }

        $candidats = Candidat::all()->where('election_id', $election_id);

        $candidats = $candidats->map(function ($candidat) {
            $candidat->photo = $candidat->photo
                ? asset('storage/' . $candidat->photo)
                : null;
            return $candidat;
        });

        return ResponseApiController::apiResponse(true, "", $candidats);
    }

    /**
     * Add a new candidate to an election.
     *
     * This endpoint allows adding a new candidate to an election. Candidates cannot be added after the election's start date.
     *
     * @group Candidates
     * @bodyParam election_id integer required The ID of the election. Example: 1
     * @bodyParam npi string required The unique NPI identifier of the candidate. Example: 123456789
     * @bodyParam name string required The name of the candidate. Example: John Doe
     * @bodyParam description string required The description of the candidate. Example: Une petite description
     * @bodyParam photo file The photo of the candidate (optional).
     * @response 201 {
     *   "success": true,
     *   "message": "Candidat ajouté avec succès",
     *   "body": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "description": "Une bonne description",
     *     "npi": 123456789,
     *     "election_id": 1,
     *     "photo": "candidats/photos/johndoe.jpg"
     *   }
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Ce candidat est déjà inscrit à cette élection",
     *   "body": ""
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous ne pouvez plus ajouter de candidat après la date de lancement des élections",
     *   "body": ""
     * }
     */
    public function store(CandidatRequest $request)
    {

        $candidatData = $request->validated();
        $election = Election::find($request->election_id);
        if (UtilsController::before($election->start_date)) {
            $candidat = Candidat::where('npi', $request->npi)->where('election_id', $request->election_id)->first();
            if ($candidat != null) {
                return ResponseApiController::apiResponse(false, 'Ce candidat est déjà inscrit à cette élection', '', 406);
            }

            if ($request->hasFile('photo')) {
                $candidatData['photo'] = $request->file('photo')->store('candidats/photos', 'public');
            }

            $candidat = Candidat::create($candidatData);
            $name = Person::where('npi', $request->npi)->first()->name;
            $candidat->name = $name;
            $candidat->save();
            Resultat::create([
                'election_id' => $election->id,
                'candidat_id' => $candidat->id,
            ]);

            return ResponseApiController::apiResponse(true, "Candidat ajouté avec succès", $candidat, 201);
        }

        return ResponseApiController::apiResponse(false, 'Vous ne pouvez plus ajouter de candidat après la date de lancement des élections', '', 406);
    }

    /**
     * Retrieve a specific candidate.
     *
     * This endpoint retrieves the details of a specific candidate by ID.
     *
     * @group Candidates
     * @urlParam id integer required The ID of the candidate. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "",
     *   "body": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "npi": 123456789,
     *     "election_id": 1,
     *     "photo": "candidats/photos/johndoe.jpg"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Candidat introuvable",
     *   "body": null
     * }
     */
    public function show(int $id)
    {
        $candidat = Candidat::find($id);
        if ($candidat == null) {
            return ResponseApiController::apiResponse(false, "Candidat introuvable", $candidat, 404);
        }
        $candidat->photo = $candidat->photo
            ? asset('storage/' . $candidat->photo)
            : null;
        return ResponseApiController::apiResponse(true, "", $candidat);
    }

    /**
     * Update a candidate.
     *
     * This endpoint allows updating the details of a specific candidate. Changes cannot be made after the election's start date.
     *
     * @group Candidates
     * @urlParam id integer required The ID of the candidate to update. Example: 1
     * @bodyParam election_id integer required The ID of the election. Example: 1
     * @bodyParam npi string required The unique NPI identifier of the candidate. Example: 123456789
     * @bodyParam name string required The name of the candidate. Example: John Doe
     * @bodyParam description string required The name of the candidate. Example: Une bonne description
     * @bodyParam photo file The photo of the candidate (optional).
     * @response 200 {
     *   "success": true,
     *   "message": "Candidat mis à jour avec succès",
     *   "body": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "description": "Une bonne description",
     *     "npi": 123456789,
     *     "election_id": 1,
     *     "photo": "candidats/photos/johndoe_updated.jpg"
     *   }
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous ne pouvez pas modifié un candidat après la date de lancement des élections",
     *   "body": ""
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Candidat introuvable",
     *   "body": null
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Ce candidat ne candidate pas pour cette election",
     *   "body": null
     * }
     */
    public function update(CandidatRequest $request, int $id)
    {
        $candidat = Candidat::find($id);
        if ($candidat == null) {
            return ResponseApiController::apiResponse(false, "Candidat introuvable", $candidat, 404);
        }
        $request->validated();
        $election = Election::find($request->election_id);

        if (!($candidat->election_id == $election->id)) {
            return ResponseApiController::apiResponse(false, 'Ce candidat ne candidate pas pour cette election', 406);
        }

        if (UtilsController::before($election->start_date)) {
            $candidat->update($request->all());
            if ($request->hasFile('image')) {
                if ($candidat->photo) {
                    Storage::disk('public')->delete($candidat->photo);
                }
                $candidat->photo = $request->file('image')->store('candidats/photos', 'public');
                $candidat->save();
            }
            return ResponseApiController::apiResponse(true, 'Candidat mis à jour avec succès', $candidat);
        }

        return ResponseApiController::apiResponse(false, 'Vous ne pouvez pas modifié un candidat après la date de lancement des élections', '', 406);
    }

    /**
     * Delete a candidate.
     *
     * This endpoint deletes a candidate. Candidates cannot be deleted after the election's start date.
     *
     * @group Candidates
     * @urlParam id integer required The ID of the candidate to delete. Example: 1
     * @response 200 {
     *   "message": "Candidat retiré avec succès"
     * }
     * @response 406 {
     *   "success": false,
     *   "message": "Vous ne pouvez pas supprimer un candidat après la date de lancement des élections",
     *   "body": ""
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "",
     *   "body": "Candidat introuvable"
     * }
     */
    public function destroy(int $id)
    {
        $candidat = Candidat::find($id);
        if ($candidat == null) {
            return ResponseApiController::apiResponse(false, "Candidat introuvable", $candidat, 404);
        }
        $election = Election::find($candidat->election_id);

        if (UtilsController::before($election->start_date)) {

            if ($candidat->photo) {
                Storage::disk('public')->delete($candidat->photo);
            }

            $candidat->delete();
            return ResponseApiController::apiResponse(true, 'Candidat retiré avec succès', '');

        }

        return ResponseApiController::apiResponse(false, 'Vous ne pouvez pas supprimer un candidat après la date de lancement des élections', '', 406);
    }
}

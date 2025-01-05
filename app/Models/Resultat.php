<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultat extends Model
{
    protected $fillable = [
        'election_id',
        'candidat_id',
        'nbr_vote',
        'percentage',
    ];
}

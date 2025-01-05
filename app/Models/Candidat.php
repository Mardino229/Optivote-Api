<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidat extends Model
{
    protected $fillable = [
        'npi',
        'election_id',
        'description',
        'photo',
    ];
}

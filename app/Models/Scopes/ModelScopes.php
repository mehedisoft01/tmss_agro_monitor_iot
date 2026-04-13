<?php


namespace App\Models\Scopes;


trait ModelScopes
{
    public function scopeSchool($query, $school_id = false)
    {
        $school = $school_id ? $school_id : auth()->user()->school_id;

        $query->where('school_id', $school);
    }
}

<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesmanFile extends Model
{
    protected $table = 'salesman_files';

    use ModelScopes;
    use HasFactory;

    protected $fillable = ['salesman_id','photo', 'document', 'description'];

}

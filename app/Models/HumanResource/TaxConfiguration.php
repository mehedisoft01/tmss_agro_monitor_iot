<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxConfiguration extends Model
{
    protected $table = 'tax_configurations';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','tax_year', 'slab_min', 'slab_max', 'rate_percent', 'status'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'tax_year' => 'required',
            'slab_min' => 'required',
            'slab_max' => 'required',
            'rate_percent' => 'required',
        ]);

        return $validate;
    }
}

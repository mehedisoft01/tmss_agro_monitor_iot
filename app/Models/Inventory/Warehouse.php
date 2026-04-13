<?php

namespace App\Models\Inventory;

use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\ProductSerialGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Warehouse extends Model
{
    protected $fillable = ['user_id', 'office_type', 'dealer_id', 'warehouse_code', 'warehouse_name', 'contact_person', 'phone', 'email', 'division_id','district_id','upazila_id','area'];

    public function validate($input){

        $validate = Validator::make($input, [
            'warehouse_code' => 'required',
            'warehouse_name' => 'required',
            'contact_person' => 'required',
            'office_type'    => 'required',
            'phone'          => 'required',
            'email'          => 'required',
            'division_id'    => 'required',
            'district_id'    => 'required',
            'upazila_id'     => 'required',
            'area'           => 'required',
        ]);

        return $validate;
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'upazila_id');
    }

    public function serialGroups(){
        return $this->hasMany(ProductSerialGroup::class, 'warehouse_id', 'id');
    }
}

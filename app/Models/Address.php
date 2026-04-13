<?php

namespace App\Models;

use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Address extends Model
{
    protected $table = 'addresses';
    protected $fillable = ['dealer_id','customer_id','p_division_id','p_district_id','p_upazila_id','p_area','s_division_id','s_district_id','s_upazila_id','s_area','type','status'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
         //
        ]);

        return $validate;
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'p_division_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'p_district_id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'p_upazila_id');
    }


    public static function createIfNew($dealerId, $customerId = null, $s_division, $s_district, $s_upazila, $s_area)
    {
        $exists = Address::where('dealer_id', $dealerId)
            ->where('customer_id', $customerId)
            ->where('s_division_id', $s_division)
            ->where('s_district_id', $s_district)
            ->where('s_upazila_id', $s_upazila)
            ->where('s_area', $s_area)
            ->first();

        if ($exists) {
            return $exists;
        }

        return Address::create([
            'dealer_id'      => $dealerId,
            'customer_id'    => $customerId,
            'p_division_id'  => null,
            'p_district_id'  => null,
            'p_upazila_id'   => null,
            'p_area'         => null,
            's_division_id'  => $s_division,
            's_district_id'  => $s_district,
            's_upazila_id'   => $s_upazila,
            's_area'         => $s_area,
            'type'           => 2,
            'status'         => 1,
        ]);
    }

}

<?php

namespace App\Models\Dealer;

use App\Models\Address;
use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\Scopes\ModelScopes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Dealer extends Authenticatable
{
    protected $table = 'dealers';

    use ModelScopes;
    use Notifiable;
    use HasFactory;

    protected $fillable = ['name','user_id', 'status', 'phone', 'email', 'address', 'contact_person', 'city', 'country', 'gst_number', 'bank_account', 'dealer_code', 'approved_by', 'approved_date', 'approval_status', 'registration_date', 'attachments','password','company_name','dealer_reference'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'name' => 'required',
            'phone' => 'required',
            'address' => '',
        ]);

        return $validate;
    }

    public function approve()
    {
        return  $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'dealer_id');
    }


    protected $casts = [
        'attachments' => 'array',
    ];
}

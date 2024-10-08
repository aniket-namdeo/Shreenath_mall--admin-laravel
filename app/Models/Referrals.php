<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referrals extends Model
{
    use HasFactory;

    protected $table = 'referral';

    protected $fillable = [
        'referrer_id',
        'referr_type',
        'referred_id'
    ];
}

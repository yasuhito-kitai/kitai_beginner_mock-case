<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'date','clock_in', 'clock_out', 'break_time_total','time_worked'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}


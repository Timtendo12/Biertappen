<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WYR extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'wyr';

    public function pack() {
        return $this->belongsTo(Pack::class);
    }
}

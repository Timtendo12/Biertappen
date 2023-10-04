<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pack extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'packs';

    public function tasks(){
        return $this->hasMany(Task::class);
    }
}

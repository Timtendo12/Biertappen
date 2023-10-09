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

    public function wordList() {
        return $this->hasOne(WordList::class);
    }

    public function wyr() {
        return $this->hasMany(WYR::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WordList extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'words_list';

    public function pack() {
        return $this->belongsTo(Pack::class);
    }

    public function words() {
        return $this->hasMany(Word::class, 'list', 'id');
    }
}

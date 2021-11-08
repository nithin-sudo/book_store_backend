<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;
    protected $table="books";
    protected $fillable = ['title','description','author','logo','price','quantity'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

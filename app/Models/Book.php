<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'author', 'isbn', 'published_at', 'status', 'description'
    ];

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

}

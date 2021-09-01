<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'quiz_id', 'score'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function result_quizzes()
    {
        return $this->hasMany(ResultQuiz::class);
    }

    public function result_essays()
    {
        return $this->hasMany(ResultEssay::class);
    }
}

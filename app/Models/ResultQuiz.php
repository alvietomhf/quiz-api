<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultQuiz extends Model
{
    use HasFactory;

    protected $fillable = ['result_id', 'question_id', 'option_id', 'correct'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}

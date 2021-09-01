<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultEssay extends Model
{
    use HasFactory;

    protected $fillable = ['result_id', 'question_id', 'comment', 'file'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

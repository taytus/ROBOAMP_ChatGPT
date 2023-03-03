<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenAI extends Model
{
    use HasFactory;
	public $table = "open_ai";
	public $fillable = ['content'];
}

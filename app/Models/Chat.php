<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $guarded = [];

    public function visitor(){
        return $this->belongsTo(Visitor::class);
    }
    public function agent(){
        return $this->belongsTo(User::class,'agent_id');
    }
    public function messages(){
        return $this->hasMany(Message::class,'chat_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $guarded=[];

    public function user(){
        return belongsTo(User::class);
    }

    public function chats(){
        return $this->hasMany(Chat::class);
    }
}

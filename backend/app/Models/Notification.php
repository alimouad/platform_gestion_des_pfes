<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'titre', 'corps', 'lien', 'lue_le'];

    protected $casts = ['lue_le' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

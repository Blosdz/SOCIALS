<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(['password', 'api_token'])]
class Adviser extends Model
{
    protected $fillable = ['name', 'email', 'password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}

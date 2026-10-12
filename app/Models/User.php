<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AdminRoles;
use App\Support\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicId, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Un compte bloqué conserve tout (rôle, articles écrits, historique) mais ne peut plus
     * ni se connecter ni poursuivre une session déjà ouverte — cf. EnsureUserIsNotBlocked.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(AdminRoles::SUPER_ADMIN);
    }
}

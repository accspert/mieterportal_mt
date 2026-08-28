<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function renter(): HasOne
    {
        return $this->hasOne(Renter::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'from_user_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'to_user_id');
    }

    public function isRenter(): bool
    {
        return $this->hasRole('Mieter') || $this->renter !== null;
    }

    /**
     * Resolve the Renter profile for this user using the canonical 3-step fallback:
     * 1. HasOne relation (user_id FK)
     * 2. Lookup by user_id
     * 3. Lookup by matching email
     *
     * Use this instead of `$user->renter` in contexts where the relation may not
     * have been set via user_id (e.g., in Policies or non-controller code).
     */
    public function resolvedRenter(): ?Renter
    {
        return $this->renter
            ?? Renter::where('user_id', $this->id)->first()
            ?? Renter::where('email', $this->email)->first();
    }

    /**
     * Resolve the Owner profile for this user using the canonical 3-step fallback.
     *
     * @see resolvedRenter()
     */
    public function resolvedOwner(): ?Owner
    {
        return $this->owner
            ?? Owner::where('user_id', $this->id)->first()
            ?? Owner::where('email', $this->email)->first();
    }
}

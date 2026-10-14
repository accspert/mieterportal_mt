<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToCompanyTenant;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\HasApiTokens;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompanyTenant, HasApiTokens, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, InteractsWithEmailAuthentication, Notifiable;

    /**
     * Users with a second factor must pass a challenge before the login is completed.
     */
    public function hasMultiFactorAuthentication(): bool
    {
        return filled($this->getAppAuthenticationSecret()) || $this->hasEmailAuthentication();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'superadmin') {
            return $this->isSuperAdmin();
        }

        return $this->hasAnyRole(['Administrator', 'Sachbearbeiter']);
    }

    /**
     * Platform operator: manages all property managements and users in /superadmin.
     * Deliberately not mass assignable; only set via `php artisan superadmin:create` or by another super admin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
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
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Portal password reset; the Filament panel sends its own reset notification.
     */
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Sends an invitation link to set the first password.
     */
    public function sendInvitation(string $companyName): void
    {
        $token = Password::broker('invitations')->createToken($this);

        $this->notify(new UserInvitationNotification($token, $companyName));
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
     * Resolve the Renter profile linked to this user.
     *
     * Profiles are linked exclusively via the user_id foreign key. Matching by e-mail
     * is deliberately not supported: users can change their own e-mail address and
     * would otherwise gain access to a foreign renter profile.
     */
    public function resolvedRenter(): ?Renter
    {
        return $this->renter;
    }

    /**
     * Resolve the Owner profile linked to this user.
     *
     * @see resolvedRenter()
     */
    public function resolvedOwner(): ?Owner
    {
        return $this->owner;
    }
}

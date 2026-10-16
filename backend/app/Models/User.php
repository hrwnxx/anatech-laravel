<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Emails are stored in lowercase, so "Maria@Gmail.com" typed by staff
     * matches the "maria@gmail.com" that Google sends back.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value): string => Str::lower(trim($value)));
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Matches the keyword against name or email.
     */
    #[Scope]
    protected function search(Builder $query, ?string $keyword): void
    {
        if (blank($keyword)) {
            return;
        }

        $query->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%");
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * True once the customer has logged in with Google at least once.
     */
    public function hasGoogleLogin(): bool
    {
        return $this->google_id !== null;
    }

    /**
     * Customers with a Gmail address usually have no password; staff only
     * give a temporary password to customers without one.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * A customer may only review a gadget they have a recorded purchase for.
     * Archived purchases still count, since they are still real sales.
     */
    public function hasPurchased(int $gadgetId): bool
    {
        return $this->purchases()->where('gadget_id', $gadgetId)->exists();
    }

    /**
     * Public reviews show "Juan C." (first name + last initial) instead of "Juan Dela Cruz".
     */
    public function maskedName(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.Str::substr(end($parts), 0, 1).'.';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_picture',
        'two_fa_enabled',
        '2fa_code',
        '2fa_expires_at',
        '2fa_attempts',
        'remember_device_token',
        'remember_device_expires_at',
        'is_admin',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        '2fa_code',
        '2fa_expires_at',
        '2fa_attempts',
        'remember_device_token',
        'is_admin',
        'is_super_admin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        '2fa_expires_at' => 'datetime',
        'remember_device_expires_at' => 'datetime',
        'two_fa_enabled' => 'boolean',
        'is_admin' => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    /**
     * Get the user's saved credentials.
     */
    public function savedCredentials()
    {
        return $this->hasMany(SavedCredential::class);
    }

    /**
     * AES Encrypt the user's name when storing
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt the user's name when retrieving
     */
    public function getNameAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return as-is if decryption fails
        }
    }

    /**
     * AES Encrypt the user's email when storing
     * Note: We store a hashed version for lookups
     */
    public function setEmailAttribute($value)
    {
        // Store the user's email encrypted in the database for privacy.
        // Also store a hashed version for lookup and login comparisons.
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower($value));
    }

    /**
     * Decrypt the user's email when retrieving
     */
    public function getEmailAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return as-is if decryption fails
        }
    }

    /**
     * AES Encrypt the user's phone when storing
     */
    public function setPhoneAttribute($value)
    {
        // Encrypt phone numbers for secure storage, but allow null values.
        $this->attributes['phone'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the user's phone when retrieving
     */
    public function getPhoneAttribute($value)
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Check whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->getAttribute('is_admin') === true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->getAttribute('is_super_admin') === true;
    }
}

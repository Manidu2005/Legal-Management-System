<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nic',
        'phone',
        'email',
        'intake_date',
    ];

    protected function casts(): array
    {
        return [
            'intake_date' => 'date',
        ];
    }

    /**
     * Cases belonging to this client.
     */
    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    /**
     * Route notifications for the SMS channel.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }
}

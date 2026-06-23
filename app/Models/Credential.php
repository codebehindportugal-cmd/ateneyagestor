<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'label',
        'category',
        'url',
        'username',
        'password',
        'notes',
    ];

    protected $hidden = ['password'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public static function categoryOptions(): array
    {
        return [
            'ssh'       => 'SSH',
            'db'        => 'Base de dados',
            'api'       => 'API Key',
            'plesk'     => 'Plesk',
            'wordpress' => 'WordPress',
            'email'     => 'Email',
            'ftp'       => 'FTP',
            'other'     => 'Outro',
        ];
    }

    public static function categoryColors(): array
    {
        return [
            'ssh'       => 'warning',
            'db'        => 'blue',
            'api'       => 'violet',
            'plesk'     => 'orange',
            'wordpress' => 'info',
            'email'     => 'amber',
            'ftp'       => 'cyan',
            'other'     => 'gray',
        ];
    }
}

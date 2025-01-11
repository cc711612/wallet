<?php

namespace App\Models\Devices\Databases\Entities;

use App\Models\Users\Databases\Entities\UserEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'devices';

    protected $fillable = [
        'user_id',
        'wallet_user_id',
        'platform',
        'device_name',
        'device_type',
        'fcm_token',
        'expired_at',
    ];

    /**
     * Get the users associated with the device.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function users()
    {
        return $this->belongsTo(UserEntity::class);
    }

    /**
     * Get the wallet users associated with the device.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function walletUsers()
    {
        return $this->belongsTo(WalletUserEntity::class, 'wallet_user_id');
    }
}

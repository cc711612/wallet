<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 03:36
 */

namespace App\Models\Wallets\Databases\Entities;

use App\Models\Devices\Databases\Entities\DeviceEntity;
use App\Models\Users\Databases\Entities\UserEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletUserEntity extends Model
{
    use SoftDeletes;

    const Table = 'wallet_users';
    /**
     * @var string
     */
    protected $table = self::Table;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'name',
        'token',
        'agent',
        'ip',
        'is_admin',
        'notify_enable',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @Author: Roy
     * @DateTime: 2022/6/28 上午 06:02
     */
    public function wallets()
    {
        return $this->belongsTo(WalletEntity::class, 'wallet_id', 'id');
    }

     /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @Author: Roy
     * @DateTime: 2022/6/28 上午 06:02
     */
    public function users()
    {
        return $this->belongsTo(UserEntity::class, 'user_id', 'id');
    }

    /**
     * Get the device associated with the wallet user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function devices()
    {
        return $this->hasMany(DeviceEntity::class, 'wallet_user_id', 'id')
            ->where('expired_at', '>', now());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:55
     */
    public function wallet_details()
    {
        return $this->belongsToMany(
            WalletDetailEntity::class,
            'wallet_detail_wallet_user',
            'wallet_user_id',
            'wallet_detail_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:58
     */
    public function created_wallet_details()
    {
        return $this->hasMany(WalletDetailEntity::class, 'created_by', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:58
     */
    public function payment_wallet_details()
    {
        return $this->hasMany(WalletDetailEntity::class, 'payment_wallet_user_id', 'id');
    }
}

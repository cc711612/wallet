<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 03:17
 */

namespace App\Models\Wallets\Databases\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Users\Databases\Entities\UserEntity;

class WalletEntity extends Model
{
    use SoftDeletes;

    const Table = 'wallets';
    /**
     * @var string
     */
    protected $table = self::Table;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'unit',
        'code',
        'properties',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'properties' => 'json'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:40
     */
    public function wallet_details()
    {
        return $this->hasMany(WalletDetailEntity::class, 'wallet_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:40
     */
    public function wallet_users()
    {
        return $this->hasMany(WalletUserEntity::class, 'wallet_id', 'id');
    }

    public function wallet_user_created()
    {
        return $this->hasMany(WalletUserEntity::class, 'wallet_id', 'id')
            ->where('is_admin', 1);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:41
     */
    public function users()
    {
        return $this->belongsTo(UserEntity::class, 'user_id', 'id');
    }

    public function getPropertiesAttribute($value)
    {
        $properties = [];
        if ($value) {
            $properties = json_decode($value, true);
        }
        // 設定預設值
        if (!isset($properties['unitConfigurable'])) {
            $properties['unitConfigurable'] = false;
        }

        // 設定預設值
        if (!isset($properties['decimalPlaces'])) {
            $properties['decimalPlaces'] = 0;
        }

        return $properties;
    }
}

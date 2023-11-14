<?php
/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 03:19
 */

namespace App\Models\Wallets\Databases\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletDetailSplitEntity extends Model
{
    use SoftDeletes;

    const Table = 'wallet_detail_splits';
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
        'wallet_detail_id',
        'wallet_user_id',
        'unit',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:45
     */
    public function wallet_users()
    {
        return $this->belongsTo(WalletUserEntity::class, 'wallet_user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:46
     */
    public function wallet_details()
    {
        return $this->belongsTo(WalletDetailEntity::class, 'wallet_detail_id', 'id');
    }
}

<?php
/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 03:14
 */

namespace App\Models\ExchangeRates\Databases\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;

class ExchangeRateEntity extends Model
{
    const Table = 'exchange_rates';
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
        'from_currency',
        'to_currency',
        'rate',
        'date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @Author: Roy
     * @DateTime: 2022/6/19 下午 03:48
     */
    public function wallet_details()
    {
        return $this->belongsTo(WalletDetailEntity::class, 'to_currency', 'unit');
    }
}

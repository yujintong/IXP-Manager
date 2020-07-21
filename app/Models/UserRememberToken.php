<?php

namespace IXP\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use stdClass;

/**
 * IXP\Models\UserRememberToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $device
 * @property string $ip
 * @property string $created
 * @property string $expires
 * @property int $is_2fa_complete
 * @property-read \IXP\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereExpires($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereIs2faComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\UserRememberToken whereUserId($value)
 * @mixin \Eloquent
 */
class UserRememberToken extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating( function ( $model ) {
            $model->created = $model->freshTimestamp();
        });
    }

    /**
     * Get the user that own the remember token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Gets a listing of switches or a single one if an ID is provided
     *
     * @param stdClass $feParams
     * @param int $userid
     * @param int|null $id
     *
     * @return array
     */
    public static function getFeList( stdClass $feParams, int $userid, int $id = null ): array
    {
        return self::where( 'user_id', $userid )
            ->when( $id , function( Builder $q, $id ) {
                return $q->where('id', $id );
            } )->when( $feParams->listOrderBy , function( Builder $q, $orderby ) use ( $feParams )  {
                return $q->orderBy( $orderby, $feParams->listOrderByDir ?? 'ASC');
            })->get()->toArray();
    }
}

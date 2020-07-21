<?php

namespace IXP\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use stdClass;

/**
 * IXP\Models\ConsoleServerConnection
 *
 * @property int $id
 * @property int|null $custid
 * @property string|null $description
 * @property string|null $port
 * @property int|null $speed
 * @property int|null $parity
 * @property int|null $stopbits
 * @property int|null $flowcontrol
 * @property int|null $autobaud
 * @property string|null $notes
 * @property int|null $console_server_id
 * @property-read \IXP\Models\ConsoleServer|null $consoleServer
 * @property-read \IXP\Models\CustomerTag|null $customer
 * @property-read \IXP\Models\Switcher $switcher
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereAutobaud($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereConsoleServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereCustid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereFlowcontrol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereParity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\IXP\Models\ConsoleServerConnection whereStopbits($value)
 * @mixin \Eloquent
 */
class ConsoleServerConnection extends Model
{
    public static $SPEED = [
        300     => 300,
        600     => 600,
        1200    => 1200,
        2400    => 2400,
        4800    => 4800,
        9600    => 9600,
        14400   => 14400,
        19200   => 19200,
        28800   => 28800,
        38400   => 38400,
        57600   => 57600,
        115200  => 115200,
        230400  => 230400
    ];

    const PARITY_EVEN       = 1;
    const PARITY_ODD        = 2;
    const PARITY_NONE       = 3;

    public static $PARITY = [
        self::PARITY_EVEN   => "even",
        self::PARITY_ODD    => "odd",
        self::PARITY_NONE   => "none"
    ];

    const FLOW_CONTROL_NONE         = 1;
    const FLOW_CONTROL_RTS_CTS      = 2;
    const FLOW_CONTROL_XON_XOFF     = 3;

    public static $FLOW_CONTROL = [
        self::FLOW_CONTROL_NONE         => "none",
        self::FLOW_CONTROL_RTS_CTS      => "rts/cts",
        self::FLOW_CONTROL_XON_XOFF     => "xon/xoff"
    ];

    public static $STOP_BITS = [
        1 => 1,
        2 => 2,
    ];


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consoleserverconnection';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'custid',
        'description',
        'port',
        'speed',
        'parity',
        'stopbits',
        'flowcontrol',
        'autobaud',
        'notes',
        'console_server_id',
    ];

    /**
     * Get the customer that own the console server connection
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerTag::class, 'custid' );
    }

    /**
     * Get the customer that own the console server connection
     */
    public function switcher(): BelongsTo
    {
        return $this->belongsTo(Switcher::class, 'switchid' );
    }

    /**
     * Get the console server that own the console server connection
     */
    public function consoleServer(): BelongsTo
    {
        return $this->belongsTo(ConsoleServer::class, 'console_server_id' );
    }

    /**
     * Gets a listing of console server connections list or a single one if an ID is provided
     *
     * @param stdClass $feParams
     * @param int|null $id
     * @param int|null $port
     *
     * @return array
     */
    public static function getFeList( stdClass $feParams, int $id = null, int $port = null ): array
    {
        $query = self::select(
            [
                'csc.*',
                'c.name AS customer', 'c.id AS customerid',
                'cs.id  AS consoleserver_id', 'cs.name AS consoleserver_name'
            ]
        )
            ->from( 'consoleserverconnection AS csc' )
            ->leftJoin( 'console_server AS cs', 'cs.id', 'csc.console_server_id')
            ->leftJoin( 'cust AS c', 'c.id', 'csc.custid')
            ->when( $id , function( Builder $q, $id ) {
                return $q->where('csc.id', $id );
            } )
            ->when( $port , function( Builder $q, $port ) {
                return $q->where('csc.console_server_id', $port );
            } )
            ->when( $feParams->listOrderBy , function( Builder $q, $orderby ) use ( $feParams )  {
                foreach( $orderby as $order ) {
                    return $q->orderBy( $order, $feParams->listOrderByDir ?? 'ASC');
                }
            });

            return $query->get()->toArray();
    }
}
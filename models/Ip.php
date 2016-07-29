<?php namespace Filipac\Banip\Models;

use Model;

/**
 * Ip Model
 */
class Ip extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'filipac_banip_ips';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeSave()
    {
        $this->correctFields($this);
    }

    public static function correctFields($item) {
        // Current values
        $addressStart = $item->address;
        $addressEnd = $item->address_end;
        $mask = $item->mask;

        // Check what values are defined
        $addressStartIsDefined = filter_var($addressStart, FILTER_VALIDATE_IP) !== false;
        $addressEndIsDefined = filter_var($addressEnd, FILTER_VALIDATE_IP) !== false;
        $maskIsDefined = ($mask >= 1) && ($mask <= 32);

        if ($addressStartIsDefined == true) {
            // Numeric representation of start ip address
            $numStart = ip2long($addressStart);
            // Store in unsigned integer representation
            $item->lower_ip_range = sprintf("%u", $numStart);

            if ($addressEndIsDefined == true) {
                // Numeric representation of start ip address
                $numEnd = ip2long($addressEnd);
                // Store in unsigned integer representation
                $item->upper_ip_range = sprintf("%u", $numEnd);
                // Mask unused
                $item->mask = 0;
            } else if ($maskIsDefined == true) {
                // Mask for lsb
                $maskValueOr = 0xffffffff >> $mask;
                // Calculate end ip address
                $numEnd = $numStart | $maskValueOr;
                // Store in unsigned integer representation
                $item->upper_ip_range = sprintf("%u", $numEnd);
                // Address end unused
                $item->address_end = '';
            } else {
                // By default start = end
                $numEnd = $numStart;
                // Store in unsigned integer representation
                $item->upper_ip_range = sprintf("%u", $numEnd);
                // Store in dotted string representation
                $item->address_end = long2ip($numEnd);
                // Default mask /32
                $item->mask = 32;
            }
        }
    }
}

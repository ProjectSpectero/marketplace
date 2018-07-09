<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\ResponseType;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

class GeoIPManager
{
    public static function resolve (String $ip) : array
    {
        $path = base_path() . '/' . env('GEO_DB_DIR');

        $city = null;
        $isoCode = null;
        $asn = 0;

        try
        {
            $cityReader = new Reader($path . '/GeoLite2-City.mmdb');
            $cityRecord = $cityReader->city($ip);

            $city = $cityRecord->city->name;
            $isoCode = $cityRecord->country->isoCode;

            $asnReader = new Reader($path . '/GeoLite2-ASN.mmdb');
            $asnRecord = $asnReader->asn($ip);

            $asn = $asnRecord->autonomousSystemNumber;
        }
        catch (AddressNotFoundException $silenced)
        {
            // Couldn't find it in the DB, oh well.
        }
        catch (\Exception $exception)
        {
            \Log::error("Interaction with DB failed!", [ 'ctx' => $exception]);
        }

        return [
            'city' => $city,
            'cc' => $isoCode,
            'asn' => $asn
        ];
    }

}
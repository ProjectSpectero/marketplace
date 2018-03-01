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
        try
        {
            $cityReader = new Reader($path . '/GeoLite2-City.mmdb');
            $cityRecord = $cityReader->city($ip);

            $asnReader = new Reader($path . '/GeoLite2-ASN.mmdb');
            $asnRecord = $asnReader->asn($ip);
        }
        catch (AddressNotFoundException $exception)
        {
            Utility::generateResponse(null, [], Errors::IP_ADDRESS_NOT_FOUND, ResponseType::NOT_FOUND);
        }



        $city = $cityRecord->city->name;
        $isoCode = $cityRecord->country->isoCode;
        $asn = $asnRecord->autonomousSystemNumber;

        return [
            'city' => $city,
            'cc' => $isoCode,
            'asn' => $asn
        ];
    }

}
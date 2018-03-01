<?php


namespace App\Libraries;


class GeoIPManager
{
    public static function resolve (String $ip) : array
    {
        /*
         * TODO: use the 3 maxmind databases to resolve and populate the array below. If error, return "null" for that field instead (but the key MUST exist)
         * Those 3 DBs are:
         * GeoLite2-ASN.mmdb  GeoLite2-City.mmdb  GeoLite2-Country.mmdb
         * You can DL them off https://dev.maxmind.com/geoip/geoip2/geolite2/
         * These files should be in GEO_DB_DIR=resources/geoip (defined in your env file)
         * Extension to use is https://maxmind.github.io/GeoIP2-php/ (already added to project), locally install the MaxMind C extension too (
         */
        return [
            'city' => "Narnia",
            'cc' => "RU",
            'asn' => 12345
        ];
    }

}
# marketplace
Yet another unoriginal name for the cloud portal.

Stack
--
Target PHP version - 7.2

Target database - MySQL (mostly due to ease of clustering, since Laravel suffers from bad performance)

Target Framework - Lumen (Laravel)

ORM - Eloquent (Laravel default)

Vision
--
A C2C marketplace where users can sell their excess bandwidth by installing our "daemon" software on their system, and listing the instance.

Sellers will be able to set the pricing mode -> (Billed per GB (Spectero Managed) | Rent Entire Instance for the month), price itself, and the services that their instance is capable of providing (HTTP Proxy | OpenVPN | ShadowSOCKS | SSH tunnel).

Buyers will be able to filter the listed instances by criterions -> (Geographic Area/Country | ASN = (ISP) | Type -> (Residential | Hosted) | Reliability -> (Via the Telemetry Module | Via User Ratings) | Price | Verified Speed, and purchase capacity as they require.


Implementation Type
--
Lumen API on the backend, VueJS app on the frontend.

Unified Response Style
--

{
	"errors": <array ERROR_KEYS>,
	"result": <stdObject data>,
	"message": <string MESSAGE_KEY>,
	"version": "1.0"
}

Windows (XAMPP)
=============================================================================
* First, check phpinfo() for your build version. Example:
    Compiler: MSVC14 (Visual C++ 2015)
    Architecture: x64
    PHP Extension Build: API20160303,NTS,VC14
    Thread safety: disabled
* Based on the information in phpinfo(); choose right dll:
    Source: https://github.com/nono303/PHP7-memcache-dll
    Folder (based on example): vc14 / x64 / nts
* Rename the file to php_memcache.dll
* Copy php_memcache.dll to xampp\php\ext
* Enable php_memcache in php.ini. You most likely won't have it written so add:
    extension=php_memcache.dll
* Restart Apache
* Check if memcache support is set as enabled in phpinfo()
Set up the vhost
=============================================================================
* Add a new vhost to your httpd.conf:
    <VirtualHost homestead.marketplace:80>
        ServerName homestead.marketplace
        DocumentRoot "path/to/your/app/marketplace/public/"
    </VirtualHost>
* Open C:\Windows\System32\drivers\etc\hosts and bind the ServerName to 127.0.0.1:
    127.0.0.1  homestead.marketplace
* Restart Apache
Init the project
=============================================================================
* Clone the repository
* Set your .env file from .env.example (you'll need to create a new database)
* composer install
* php artisan migrate:refresh --seed
* php artisan key:generate
* php artisan passport:install
* Copy out the client secret for id=2 (pw grant client) and put it into your .env
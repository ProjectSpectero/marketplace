<?php


namespace App\Http\Controllers\V1;


use Illuminate\Http\Request;

class IntegrationsController extends V1Controller
{
    public function support (Request $request)
    {
        $key = env('SUPPORT_KEY');
        $baseUri = env('SUPPORT_BASE_URI', 'https://spectero.freshdesk.com/');

        $user = $request->user();

        $name = $user->name != null ? "Spectero User" : $user->name;
        $email = $user->email;
        $timestamp = time();

        $hashString = $name . $key . $email . $timestamp;
        $hash = hash_hmac('md5', $hashString, $key);
        $uri = $baseUri . "login/sso/?name=" . urlencode($name) . "&email=" . urlencode($email) . "&timestamp=" . $timestamp . "&hash=" . $hash;

        return $this->respond([
            'hash' => $hash,
            'redirect_uri' => $uri
          ]);
    }
}
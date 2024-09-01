<?php

namespace App\Repositories\V1\Company;

use Exception;
use Illuminate\Support\Facades\Http;


class MicrosoftOauthRepository
{
    public function fetchAuthAndRefreshToken($authCode)
    {
        $body = [
            'client_id' => config('callAi.MICROSOFT_CLIENT_ID'),
            'client_secret' => config('callAi.MICROSOFT_SECRET_ID'),
            'redirect_uri' => config('callAi.MICROSOFT_REDIRECT_URL'),
            'grant_type' => "authorization_code",
            'code' => $authCode
        ];
        try {
            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', $body);
        } catch (Exception $ex) {
            throw new Exception("Error occured while fetching microsoft auth token");
        }
        if ($response->ok()) {
            return $response->json();
        } else if ($response->failed()) {
            if ($response->clientError()) {
                throw new Exception("Error occured while fetching microsoft auth token");
            } elseif ($response->serverError()) {
                throw new Exception("Error occured while fetching microsoft auth token");
            } else {
                throw new Exception("Error occured while fetching microsoft auth token");
            }
        } else {
            throw new Exception("Error occured while fetching microsoft auth token");
        }
    }
}

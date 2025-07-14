<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Ilzrv\LaravelSteamAuth\SteamAuthenticator;
use Ilzrv\LaravelSteamAuth\SteamUserDto;
use Ilzrv\LaravelSteamAuth\Exceptions\Authentication\SteamResponseNotValidAuthenticationException;
use Ilzrv\LaravelSteamAuth\Exceptions\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request,
        Redirector $redirector,
        Client $client,
        HttpFactory $httpFactory,
        AuthManager $authManager): string
    {

        $steamAuthenticator = new SteamAuthenticator(
            new Uri($request->getUri()),
            $client,
            $httpFactory,
        );
        try {
            $steamAuthenticator->auth();
        } catch (ValidationException|SteamResponseNotValidAuthenticationException $e) {
            return $redirector->to(
                $steamAuthenticator->buildAuthUrl()
            );
        }

        $steamUser = $steamAuthenticator->getSteamUser();

        $authManager->login(
            $this->updateOrCreate($steamUser),
            true
        );

        return $redirector->to('/');
    }

    private function updateOrCreate(SteamUserDto $steamUser): User
    {
        return User::updateOrCreate([
            'steam_id' => $steamUser->getSteamId(),
        ], [
            'name' => $steamUser->getPersonaName(),
            'avatar' => $steamUser->getAvatarFull(),
        ]);
    }
}

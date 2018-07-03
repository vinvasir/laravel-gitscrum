<?php

namespace GitScrum\Http\Controllers\Web;

use Auth;
use GitScrum\Http\Requests\AuthRequest;
use GitScrum\Models\Sprint;
use GitScrum\Models\User;
use Session;
use Socialite;
use SocialiteProviders\Manager\Exception\InvalidArgumentException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login()
    {
        return view('auth.login');
    }

    public function logout()
    {
        Auth::logout();
        Session::flush();
        return redirect()->route('home');
    }

    public function redirectToProvider($provider)
    {
        switch ($provider) {
            case 'gitlab':
                return Socialite::with('gitlab')->redirect();
                break;
            case 'github':
                return Socialite::driver('github')->scopes(['repo', 'notifications', 'read:org'])->redirect();
                break;
            case 'bitbucket':
                return Socialite::driver('bitbucket')->redirect();
                break;

            default:
                throw new InvalidArgumentException(trans('gitscrum.provider-was-not-set'));
                break;
        }
    }

    public function handleProviderCallback($provider)
    {
        $providerUser = Socialite::driver($provider)->user();

        $data = app(ucfirst($provider))->tplUser($providerUser);

        $user = User::updateOrCreate(['provider_id' => $data['provider_id']], $data);

        Auth::loginUsingId($user->id);

        if ($user->sprint_id) {
            return redirect()->route('issues.index', ['slug' => Sprint::find($user->sprint_id)->slug]);
        }

        return redirect()->route('issues.index', ['slug' => 0]);
    }
}

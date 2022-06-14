<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    ## ------------ login part ------------- ##
    /**
     * The user has been authenticated on this app.
     * calls sendCrootonLoginRequest to login user on crooton too
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    public function authenticated(Request $request, $user)
    {
        return $this->sendCrootonLoginRequest($request, $user);
    }

    /**
     * Handle a login request to crooton application
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function sendCrootonLoginRequest(Request $request, $user) {

        $loginCallBackUrl = $request->root() . $this->redirectPath();
        $email = $user->email;

        $query = http_build_query([
            'loginCallBackUrl' => $loginCallBackUrl,
            'email' => $email
        ]);

        $portalRemoteLoginUrl = config('app.partner_url') . '/remoteLogin';
        $portalFullRemoteLoginUrl = $portalRemoteLoginUrl. '?' . $query;
        // dd($portalFullRemoteLoginUrl);

        return redirect($portalFullRemoteLoginUrl);
    }

    ## -------- logout part ---------- ##
    public function loggedOut(Request $request)
    {
        $logoutCallBackUrl = $request->root();
        $query = http_build_query([
            'logoutCallBackUrl' => $logoutCallBackUrl,
        ]);

        $portalRemoteLogoutUrl = config('app.partner_url') . '/remoteLogout';
        $portalFullRemoteLogoutUrl = $portalRemoteLogoutUrl. '?' . $query;

        return redirect($portalFullRemoteLogoutUrl);
    }

    ## --------- remote handlers --------- ##
    public function remoteLogin(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if(!auth()->check()) {
            auth()->login($user);
        }

        return redirect($request->loginCallBackUrl);
    }

    public function remoteLogout(Request $request)
    {
        if(auth()->check()) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect($request->logoutCallBackUrl);
    }
}

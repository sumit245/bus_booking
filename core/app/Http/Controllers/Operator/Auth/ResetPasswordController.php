<?php

namespace App\Http\Controllers\Operator\Auth;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\OperatorPasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    public $redirectTo = '/operator/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('operator.guest');
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showResetForm(Request $request, $token)
    {
        $pageTitle = "Account Recovery";
        $resetToken = OperatorPasswordReset::where('token', $token)->where('status', 0)->first();

        if (!$resetToken) {
            $notify[] = ['error', 'Token not found!'];
            return redirect()->route('operator.password.reset')->withNotify($notify);
        }
        $email = $resetToken->email;
        return view('operator.auth.passwords.reset', compact('pageTitle', 'email', 'token'));
    }

    /**
     * Reset the operator's password
     */
    public function reset(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        $reset = OperatorPasswordReset::where('token', $request->token)->orderBy('created_at', 'desc')->first();
        $operator = Operator::where('email', $reset->email)->first();

        if ($reset->status == 1) {
            $notify[] = ['error', 'Invalid code'];
            return redirect()->route('operator.login')->withNotify($notify);
        }

        $operator->password = bcrypt($request->password);
        $operator->save();
        $reset->status = 1;
        $reset->save();

        $operatorIpInfo = getIpInfo();
        $operatorBrowser = osBrowser();
        sendEmail($operator, 'PASS_RESET_DONE', [
            'operating_system' => $operatorBrowser['os_platform'],
            'browser' => $operatorBrowser['browser'],
            'ip' => $operatorIpInfo['ip'],
            'time' => $operatorIpInfo['time']
        ]);

        $notify[] = ['success', 'Password changed successfully'];
        return redirect()->route('operator.login')->withNotify($notify);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker('operators');
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('operator');
    }
}

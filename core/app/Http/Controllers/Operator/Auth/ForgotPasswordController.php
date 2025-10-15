<?php

namespace App\Http\Controllers\Operator\Auth;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\OperatorPasswordReset;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

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
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showLinkRequestForm()
    {
        $pageTitle = 'Account Recovery';
        return view('operator.auth.passwords.email', compact('pageTitle'));
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
     * Send reset code to operator's email
     */
    public function sendResetCodeEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $operator = Operator::where('email', $request->email)->first();
        if (!$operator) {
            $notify[] = ['error', 'Email address not found.'];
            return back()->withNotify($notify);
        }

        $code = verificationCode(6);
        $operatorPasswordReset = new OperatorPasswordReset();
        $operatorPasswordReset->email = $operator->email;
        $operatorPasswordReset->token = $code;
        $operatorPasswordReset->status = 0;
        $operatorPasswordReset->created_at = date("Y-m-d h:i:s");
        $operatorPasswordReset->save();

        $operatorIpInfo = getIpInfo();
        $operatorBrowser = osBrowser();
        sendEmail($operator, 'PASS_RESET_CODE', [
            'code' => $code,
            'operating_system' => $operatorBrowser['os_platform'],
            'browser' => $operatorBrowser['browser'],
            'ip' => $operatorIpInfo['ip'],
            'time' => $operatorIpInfo['time']
        ]);

        $notify[] = ['success', 'Password reset code sent to your email.'];
        return redirect()->route('operator.password.code.verify')->withNotify($notify);
    }

    /**
     * Show code verification form
     */
    public function codeVerify()
    {
        $pageTitle = 'Account Recovery';
        return view('operator.auth.passwords.code_verify', compact('pageTitle'));
    }

    /**
     * Verify reset code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required'
        ]);

        $notify[] = ['success', 'You can change your password.'];
        $code = str_replace(' ', '', $request->code);
        return redirect()->route('operator.password.reset.form', $code)->withNotify($notify);
    }
}

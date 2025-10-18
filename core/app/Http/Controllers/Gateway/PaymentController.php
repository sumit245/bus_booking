<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        return $this->activeTemplate = activeTemplate();
    }

    public function deposit()
    {
        $pnr_number = session()->get('pnr_number');
        $bookedTicket = BookedTicket::where('pnr_number', $pnr_number)->first();
        if(!$bookedTicket){
            $notify[] = 'Please Try again.';
            return redirect()->route('ticket')->withNotify($notify);
        }
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->with('method')->orderby('method_code')->get();

        $pageTitle = 'Payment Methods';
        $bookedTicket = BookedTicket::where('user_id', auth()->user()->id)->orderBy('id', 'desc')->first();
        return view($this->activeTemplate . 'user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'bookedTicket'));
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'method_code' => 'required',
            'currency' => 'required',
        ]);

        $pnr_number = session()->get('pnr_number');
        $bookedTicket = BookedTicket::where('pnr_number', $pnr_number)->first();

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $bookedTicket->sub_total || $gate->max_amount < $bookedTicket->sub_total) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($bookedTicket->sub_total * $gate->percent_charge / 100);
        $payable = $bookedTicket->sub_total + $charge;
        $final_amo = $payable * $gate->rate;
        $data = new Deposit();
        $data->user_id = $user->id;
        $data->booked_ticket_id = $bookedTicket->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $bookedTicket->sub_total;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amo = $final_amo;
        $data->btc_amo = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->try = 0;
        $data->status = 0;
        $data->save();
        session()->put('Track', $data->trx);
        return redirect()->route('user.deposit.preview');
    }


    public function depositPreview()
    {
        $track = session()->get('Track');
        $data = Deposit::where('trx', $track)->where('status',0)->orderBy('id', 'DESC')->firstOrFail();
        $pageTitle = 'Payment Preview';
        return view($this->activeTemplate . 'user.payment.preview', compact('data', 'pageTitle'));
    }


    public function depositConfirm()
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status',0)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            $this->userDataUpdate($deposit);
            $notify[] = ['success', 'Your payment request is queued for approval.'];
            return back()->withNotify($notify);
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return redirect()->route(gatewayRedirectUrl())->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if(@$data->session){
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view($this->activeTemplate . $data->view, compact('data', 'pageTitle', 'deposit'));
    }


    public static function userDataUpdate($trx)
    {
        $general = GeneralSetting::first();
        $data = Deposit::where('trx', $trx)->first();
        $bookedTicket = BookedTicket::where('id', $data->booked_ticket_id)->first();
        if ($data->status == 0) {
            $data->status = 1;
            $data->save();

            $user = $data->user;
            $bookedTicket->status = 1;
            $bookedTicket->save();

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'Payment successful via '.$data->gatewayCurrency()->name;
            $adminNotification->click_url = urlPath('admin.vehicle.ticket.booked');
            $adminNotification->save();

            notify($user, 'PAYMENT_COMPLETE', [
                'method_name' => $data->gatewayCurrency()->name,
                'method_currency' => $data->method_currency,
                'method_amount' => showAmount($data->final_amo),
                'amount' => showAmount($data->amount),
                'charge' => showAmount($data->charge),
                'currency' => $general->cur_text,
                'rate' => showAmount($data->rate),
                'trx' => $data->trx,
                'journey_date' => showDateTime($bookedTicket->date_of_journey , 'd m, Y'),
                'seats' => implode(',',$bookedTicket->seats),
                'total_seats' => sizeof($bookedTicket->seats),
                'source' => $bookedTicket->pickup->name,
                'destination' => $bookedTicket->drop->name
            ]);

        }
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();
        if (!$data) {
            return redirect()->route(gatewayRedirectUrl());
        }
        if ($data->method_code > 999) {

            $pageTitle = 'Deposit Confirm';
            $method = $data->gatewayCurrency();
            return view($this->activeTemplate . 'user.manual_payment.manual_confirm', compact('data', 'pageTitle', 'method'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();
        if (!$data) {
            return redirect()->route(gatewayRedirectUrl());
        }

        $params = json_decode($data->gatewayCurrency()->gateway_parameter);

        $rules = [];
        $inputField = [];
        $verifyImages = [];

        if ($params != null) {
            foreach ($params as $key => $custom) {
                $rules[$key] = [$custom->validation];
                if ($custom->type == 'file') {
                    array_push($rules[$key], 'image');
                    array_push($rules[$key], new FileTypeValidate(['jpg','jpeg','png']));
                    array_push($rules[$key], 'max:2048');

                    array_push($verifyImages, $key);
                }
                if ($custom->type == 'text') {
                    array_push($rules[$key], 'max:191');
                }
                if ($custom->type == 'textarea') {
                    array_push($rules[$key], 'max:300');
                }
                $inputField[] = $key;
            }
        }
        $this->validate($request, $rules);


        $directory = date("Y")."/".date("m")."/".date("d");
        $path = imagePath()['verify']['deposit']['path'].'/'.$directory;
        $collection = collect($request);
        $reqField = [];
        if ($params != null) {
            foreach ($collection as $k => $v) {
                foreach ($params as $inKey => $inVal) {
                    if ($k != $inKey) {
                        continue;
                    } else {
                        if ($inVal->type == 'file') {
                            if ($request->hasFile($inKey)) {
                                try {
                                    $reqField[$inKey] = [
                                        'field_name' => $directory.'/'.uploadImage($request[$inKey], $path),
                                        'type' => $inVal->type,
                                    ];
                                } catch (\Exception $exp) {
                                    $notify[] = ['error', 'Could not upload your ' . $inKey];
                                    return back()->withNotify($notify)->withInput();
                                }
                            }
                        } else {
                            $reqField[$inKey] = $v;
                            $reqField[$inKey] = [
                                'field_name' => $v,
                                'type' => $inVal->type,
                            ];
                        }
                    }
                }
            }
            $data->detail = $reqField;
        } else {
            $data->detail = null;
        }

        $data->status = 2; // pending
        $data->save();
        $bookedTicket = BookedTicket::where('id', $data->booked_ticket_id)->first();
        $bookedTicket->status = 2; //pending
        $bookedTicket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user->id;
        $adminNotification->title = 'Payment request from '.$data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details',$data->id);
        $adminNotification->save();

        $general = GeneralSetting::first();
        notify($data->user, 'PAYMENT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amo),
            'amount' => showAmount($data->amount),
            'charge' => showAmount($data->charge),
            'currency' => $general->cur_text,
            'rate' => showAmount($data->rate),
            'trx' => $data->trx,
            'journey_date' => showDateTime($bookedTicket->date_of_journey , 'd m, Y'),
            'seats' => implode(',',$bookedTicket->seats),
            'total_seats' => sizeof($bookedTicket->seats),
            'source' => $bookedTicket->pickup->name,
            'destination' => $bookedTicket->drop->name
        ]);

        $notify[] = ['success', 'Your payment request has been taken.'];
        return redirect()->route('user.ticket.history')->withNotify($notify);
    }

}

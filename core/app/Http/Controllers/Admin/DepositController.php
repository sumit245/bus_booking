<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DepositController extends Controller
{
    protected $depoRelations = ['user', 'gateway'];
    public function pending(){
        $pageTitle = 'Pending Payment';
        $emptyMessage = 'There is no pending payment';
        $deposits = Deposit::pending()->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits'));
    }
    public function successful(){
        $pageTitle = 'Successful Payment';
        $emptyMessage = 'There is no successful payment';
        $deposits = Deposit::Successful()->with($this->depoRelations)->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits'));
    }

    public function rejected(){
        $pageTitle = 'Rejected Payment';
        $emptyMessage = 'There is no rejected payment';
        $deposits = Deposit::rejected()->with($this->depoRelations)->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits'));
    }

    public function all(){
        $pageTitle = 'All Payment';
        $emptyMessage = 'There is no payment';
        $deposits = Deposit::where('status','!=',0)->with($this->depoRelations)->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits'));
    }

    public function depositViaMethod($method,$type = null){
        $method = Gateway::where('alias',$method)->firstOrFail();
        if ($type == 'approved') {
            $pageTitle = 'Approved Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('method_code',$method->code)->where('status', 1)->orderBy('id','desc')->with($this->depoRelations);
        }elseif($type == 'rejected'){
            $pageTitle = 'Rejected Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('method_code',$method->code)->where('status', 3)->orderBy('id','desc')->with($this->depoRelations);

        }elseif($type == 'successful'){
            $pageTitle = 'Successful Payment Via '.$method->name;
            $deposits = Deposit::where('status', 1)->where('method_code',$method->code)->orderBy('id','desc')->with($this->depoRelations);
        }elseif($type == 'pending'){
            $pageTitle = 'Pending Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('method_code',$method->code)->where('status', 2)->orderBy('id','desc')->with($this->depoRelations);
        }else{
            $pageTitle = 'Payment Via '.$method->name;
            $deposits = Deposit::where('status','!=',0)->where('method_code',$method->code)->orderBy('id','desc')->with($this->depoRelations);
        }
        $deposits = $deposits->paginate(getPaginate());
        $successful = $deposits->where('status',1)->sum('amount');
        $pending = $deposits->where('status',2)->sum('amount');
        $rejected = $deposits->where('status',3)->sum('amount');
        $methodAlias = $method->alias;
        $emptyMessage = 'No Payments Found';
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits','methodAlias','successful','pending','rejected'));
    }

    public function dateSearch(Request $request,$scope = null){
        $search = $request->date;
        if (!$search) {
            return back();
        }
        $date = explode('-',$search);
        $start = @$date[0];
        $end = @$date[1];
        // date validation
        $pattern = "/\d{2}\/\d{2}\/\d{4}/";
        if ($start && !preg_match($pattern,$start)) {
            $notify[] = ['error','Invalid date format'];
            return redirect()->route('admin.deposit.list')->withNotify($notify);
        }
        if ($end && !preg_match($pattern,$end)) {
            $notify[] = ['error','Invalid date format'];
            return redirect()->route('admin.deposit.list')->withNotify($notify);
        }


        if ($start) {
            $deposits = Deposit::where('status','!=',0)->whereDate('created_at',Carbon::parse($start));
        }
        if($end){
            $deposits = Deposit::where('status','!=',0)->whereDate('created_at','>=',Carbon::parse($start))->whereDate('created_at','<=',Carbon::parse($end));
        }
        if ($request->method) {
            $method = Gateway::where('alias',$request->method)->firstOrFail();
            $deposits = $deposits->where('method_code',$method->code);
        }
        if ($scope == 'pending') {
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 2);
        }elseif($scope == 'approved'){
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 1);
        }elseif($scope == 'rejected'){
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 3);
        }
        $deposits = $deposits->with($this->depoRelations)->orderBy('id','desc')->paginate(getPaginate());
        $pageTitle = ' Payments Log';
        $emptyMessage = 'No Payments Found';
        $dateSearch = $search;
        return view('admin.deposit.log', compact('pageTitle', 'emptyMessage', 'deposits','dateSearch','scope'));
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $emptyMessage = 'No search result was found.';
        $deposits = Deposit::with($this->depoRelations)->where('status','!=',0)->where(function ($q) use ($search) {
            $q->where('trx', 'like', "%$search%")->orWhereHas('user', function ($user) use ($search) {
                $user->where('username', 'like', "%$search%");
            });
        });
        if ($scope == 'pending') {
            $pageTitle = 'Pending Payments Search';
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 2);
        }elseif($scope == 'approved'){
            $pageTitle = 'Approved Payments Search';
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 1);
        }elseif($scope == 'rejected'){
            $pageTitle = 'Rejected Payments Search';
            $deposits = $deposits->where('method_code', '>=', 1000)->where('status', 3);
        }else{
            $pageTitle = 'Payments History Search';
        }

        $deposits = $deposits->paginate(getPaginate());
        $pageTitle .= '-' . $search;

        return view('admin.deposit.log', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'deposits'));
    }

    public function details($id)
    {
        $general = GeneralSetting::first();
        $deposit = Deposit::where('id', $id)->with($this->depoRelations)->firstOrFail();
        $pageTitle = $deposit->user->username.' requested ' . showAmount($deposit->amount) . ' '.$general->cur_text;
        $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
        return view('admin.deposit.detail', compact('pageTitle', 'deposit','details'));
    }

    public function approve(Request $request)
    {

        $request->validate(['id' => 'required|integer']);
        $deposit = Deposit::where('id',$request->id)->where('status',2)->firstOrFail();
        $deposit->status = 1;
        $deposit->save();

        $user = $deposit->user;
        $bookedTicket = BookedTicket::find($deposit->booked_ticket_id);
        $bookedTicket->status = 1;
        $bookedTicket->save();

        $general = GeneralSetting::first();
        notify($user, 'PAYMENT_APPROVE', [
            'method_name' => $deposit->gatewayCurrency()->name,
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amo),
            'amount' => showAmount($deposit->amount),
            'charge' => showAmount($deposit->charge),
            'currency' => $general->cur_text,
            'rate' => showAmount($deposit->rate),
            'trx' => $deposit->trx,
            'journey_date' => showDateTime($bookedTicket->date_of_journey , 'd m, Y'),
            'seats' => implode(',',$bookedTicket->seats),
            'total_seats' => sizeof($bookedTicket->seats),
            'source' => $bookedTicket->pickup->name,
            'destination' => $bookedTicket->drop->name
        ]);
        $notify[] = ['success', 'Payment request has been approved.'];

        return redirect()->route('admin.deposit.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'message' => 'required|max:250'
        ]);
        $deposit = Deposit::where('id',$request->id)->where('status',2)->firstOrFail();

        $deposit->admin_feedback = $request->message;
        $deposit->status = 3;
        $deposit->save();

        $bookedTicket = BookedTicket::find($deposit->booked_ticket_id);
        $bookedTicket->status = 0;
        $bookedTicket->save();

        $general = GeneralSetting::first();
        notify($deposit->user, 'PAYMENT_REJECT', [
            'method_name' => $deposit->gatewayCurrency()->name,
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amo),
            'amount' => showAmount($deposit->amount),
            'charge' => showAmount($deposit->charge),
            'currency' => $general->cur_text,
            'rate' => showAmount($deposit->rate),
            'trx' => $deposit->trx,
            'rejection_message' => $request->message,
            'journey_date' => showDateTime($bookedTicket->date_of_journey , 'd m, Y'),
            'seats' => implode(',',$bookedTicket->seats),
            'total_seats' => sizeof($bookedTicket->seats),
            'source' => $bookedTicket->pickup->name,
            'destination' => $bookedTicket->drop->name
        ]);

        $notify[] = ['success', 'Payment request has been rejected.'];
        return  redirect()->route('admin.deposit.pending')->withNotify($notify);

    }
}

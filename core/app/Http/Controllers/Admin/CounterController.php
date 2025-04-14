<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Counter;

class CounterController extends Controller
{
    public function counters(){
        $pageTitle = 'All Counter';
        $emptyMessage = 'No counter found';
        $counters = Counter::paginate(getPaginate());
        return view('admin.counter.list', compact('pageTitle','emptyMessage','counters'));
    }

    public function counterStore(Request $request){
        $request->validate([
            'name' => 'required|unique:counters',
            'city' => 'required',
            'mobile' => 'required|numeric|unique:counters'
        ]);

        $counter = new Counter();
        $counter->name      =  $request->name;
        $counter->city      =  $request->city;
        $counter->location  =  $request->location;
        $counter->mobile    =  $request->mobile;
        $counter->save();

        $notify[] = ['success', 'Counter save successfully.'];
        return back()->withNotify($notify);
    }

    public function counterUpdate(Request $request, $id){
        $request->validate([
            'name' => 'required|unique:counters,name,'.$id,
            'city' => 'required',
            'mobile' => 'required|numeric|unique:counters,mobile,'.$id
        ]);

        $counter = Counter::find($id);
        $counter->name      =  $request->name;
        $counter->city      =  $request->city;
        $counter->location  =  $request->location;
        $counter->mobile    =  $request->mobile;
        $counter->save();

        $notify[] = ['success', 'Counter update successfully.'];
        return back()->withNotify($notify);
    }

    public function counterActiveDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);

        $counter = Counter::find($request->id);
        $counter->status = $counter->status == 1 ? 0 : 1;
        $counter->save();
        
        if($counter->status == 1){
            $notify[] = ['success', 'Counter active successfully.'];
        }else{
            $notify[] = ['success', 'Counter disabled successfully.'];
        }

        return back()->withNotify($notify);
    }
}

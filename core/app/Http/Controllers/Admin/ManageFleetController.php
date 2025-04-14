<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeatLayout;
use App\Models\FleetType;
use App\Models\Vehicle;

class ManageFleetController extends Controller
{
    public function seatLayouts(){
        $pageTitle = 'Seat Layouts';
        $emptyMessage = 'No layouts found';
        $layouts = SeatLayout::orderBy('id','desc')->paginate(getPaginate());
        return view('admin.fleet.seat_layouts', compact('pageTitle', 'emptyMessage', 'layouts'));
    }

    public function seatLayoutStore(Request $request){
        $request->validate([
            'layout' => 'required|unique:seat_layouts'
        ]);

        $seatLayout = new SeatLayout();
        $seatLayout->layout = $request->layout;
        $seatLayout->save();
        $notify[] = ['success', 'Seat layout saved successfully.'];
        return back()->withNotify($notify);
    }

    public function seatLayoutUpdate(Request $request, $id){
        $request->validate([
            'layout' => 'required|unique:seat_layouts,layout,'.$id
        ]);
        
        $seat = SeatLayout::find($request->id);
        $seat->layout = $request->layout;
        $seat->save();
        $notify[] = ['success', 'Seat layout updated successfully.'];
        return back()->withNotify($notify);
    }

    public function seatLayoutDelete(Request $request){
        $request->validate(['id' => 'required|integer']);
        SeatLayout::find($request->id)->delete();
        $notify[] = ['success', 'Seat layout deleted successfully.'];
        return back()->withNotify($notify);
    }


    public function fleetLists(){
        $pageTitle = 'Fleet Type';
        $emptyMessage = 'No fleet type found';
        $seatLayouts = SeatLayout::all();
        $fleetType = FleetType::orderBy('id','desc')->paginate(getPaginate());
        $facilities = getContent('amenities.element');
        return view('admin.fleet.type', compact('pageTitle', 'emptyMessage', 'fleetType', 'seatLayouts', 'facilities'));
    }

    public function fleetTypeStore(Request $request){
        $request->validate([
            'name'        => 'required|unique:fleet_types',
            'seat_layout' => 'required',
            'deck'        => 'required|numeric|gt:0',
            'deck_seats'  => 'required|array|min:1',
            'deck_seats.*'=> 'required|numeric|gt:0',
            'facilities.*'=> 'string'
        ],[
            'deck_seats.*.required'  => 'Seat number for all deck is required',
            'deck_seats.*.numeric'   => 'Seat number for all deck is must be a number',
            'deck_seats.*.gt:0'      => 'Seat number for all deck is must be greater than 0',
        ]);
        $fleetType = new FleetType();
        $fleetType->name = $request->name;
        $fleetType->seat_layout = $request->seat_layout;
        $fleetType->deck = $request->deck;
        $fleetType->deck_seats = $request->deck_seats;
        $fleetType->has_ac = $request->has_ac ? $request->has_ac : 0;
        $fleetType->facilities = $request->facilities ?? null;
        $fleetType->status = 1;
        $fleetType->save();

        $notify[] = ['success','Fleet type saved successfully'];
        return back()->withNotify($notify);
    }

    public function fleetTypeUpdate(Request $request, $id){
        $request->validate([
            'name'        => 'required|unique:fleet_types,name,'.$id,
            'seat_layout' => 'required',
            'deck'        => 'required|numeric|gt:0',
            'deck_seats'  => 'required|array|min:1',
            'deck_seats.*'=> 'required|numeric|gt:0',
            'facilities.*'=> 'string'
        ],[
            'deck_seats.*.required'  => 'Seat number for all deck is required',
            'deck_seats.*.numeric'   => 'Seat number for all deck is must be a number',
            'deck_seats.*.gt:0'      => 'Seat number for all deck is must be greater than 0',
        ]);
        // return $request;
        $fleetType = FleetType::find($id);
        $fleetType->name = $request->name;
        $fleetType->seat_layout = $request->seat_layout;
        $fleetType->deck = $request->deck;
        $fleetType->deck_seats = $request->deck_seats;
        $fleetType->has_ac = $request->has_ac ? 1 : 0;
        $fleetType->facilities = $request->facilities ?? null;
        $fleetType->save();
        $notify[] = ['success','Fleet type updated successfully'];
        return back()->withNotify($notify);
    }

    public function fleetEnableDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);
        $fleetType = FleetType::find($request->id);
        $fleetType->status = $fleetType->status == 1 ? 0 : 1;
        $fleetType->save();
        if($fleetType->status == 1){
            $notify[] = ['success', 'Fleet type active successfully.'];
        }else{
            $notify[] = ['success', 'Fleet type disabled successfully.'];
        }
        return back()->withNotify($notify);
    }

    public function vehicles(){
        $pageTitle = 'All Vehicles';
        $emptyMessage = 'No vehicles found';
        $fleetType = FleetType::where('status', 1)->orderBy('id','desc')->get();
        $vehicles = Vehicle::with('fleetType')->orderBy('id','desc')->paginate(getPaginate());
        return view('admin.fleet.vehicles', compact('pageTitle', 'emptyMessage', 'vehicles', 'fleetType'));
    }

    public function vehicleSearch(Request $request){
        $search = $request->search;
        $pageTitle = 'Vehicles - '. $search;
        $emptyMessage = 'No vehicles found';
        $fleetType = FleetType::where('status', 1)->orderBy('id','desc')->get();
        $vehicles = Vehicle::with('fleetType')->where('register_no', $search)->orderBy('id','desc')->paginate(getPaginate());
        return view('admin.fleet.vehicles', compact('pageTitle', 'emptyMessage', 'vehicles', 'fleetType', 'search'));
    }

    public function vehiclesStore(Request $request){
        $this->validate($request,[
            'nick_name'         => 'required|string',
            'fleet_type'        => 'required|numeric',
            'register_no'       => 'required|string|unique:vehicles',
            'engine_no'         => 'required|string|unique:vehicles',
            'model_no'          => 'required|string',
            'chasis_no'         => 'required|string|unique:vehicles',
        ]);

        $vehicle = new Vehicle();
        $vehicle->nick_name = $request->nick_name;
        $vehicle->fleet_type_id = $request->fleet_type;
        $vehicle->register_no = $request->register_no;
        $vehicle->engine_no = $request->engine_no;
        $vehicle->chasis_no = $request->chasis_no;
        $vehicle->model_no = $request->model_no;
        $vehicle->save();

        $notify[] = ['success', 'Vehicle save successfully.'];
        return back()->withNotify($notify);
    }

    public function vehiclesUpdate(Request $request,$id){
        $this->validate($request,[
            'nick_name'         => 'required|string',
            'fleet_type'        => 'required|numeric',
            'register_no'       => 'required|string|unique:vehicles,register_no,'.$id,
            'engine_no'         => 'required|string|unique:vehicles,engine_no,'.$id,
            'model_no'          => 'required|string',
            'chasis_no'         => 'required|string|unique:vehicles,chasis_no,'.$id,
        ]);

        $vehicle = Vehicle::find($id);
        $vehicle->nick_name = $request->nick_name;
        $vehicle->fleet_type_id = $request->fleet_type;
        $vehicle->register_no = $request->register_no;
        $vehicle->engine_no = $request->engine_no;
        $vehicle->chasis_no = $request->chasis_no;
        $vehicle->model_no = $request->model_no;
        $vehicle->save();

        $notify[] = ['success', 'Vehicle update successfully.'];
        return back()->withNotify($notify);
    }

    public function vehiclesActiveDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);

        $vehicle = Vehicle::find($request->id);
        $vehicle->status = $vehicle->status == 1 ? 0 : 1;
        $vehicle->save();
        if($vehicle->status == 1){
            $notify[] = ['success', 'Vehicle active successfully.'];
        }else{
            $notify[] = ['success', 'Vehicle disabled successfully.'];
        }
        return back()->withNotify($notify);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignedVehicle;
use Illuminate\Http\Request;
use App\Models\VehicleRoute;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\Trip;
use Carbon\Carbon;

class ManageTripController extends Controller
{
    public function routeList(){
        $pageTitle = 'All Routes';
        $emptyMessage = 'No route found';
        $routes = VehicleRoute::with(['startFrom','endTo'])->orderBy('id', 'desc')->paginate(getPaginate());
        $stoppages = Counter::active()->get();
        return view('admin.trip.route.list', compact('pageTitle', 'routes', 'emptyMessage', 'stoppages'));
    }

    public function routeCreate(){
        $pageTitle = 'Create Route';
        $stoppages = Counter::active()->get();
        return view('admin.trip.route.create', compact('pageTitle', 'stoppages'));
    }

    public function routeStore(Request $request){
        $request->validate([
            'name' => 'required',
            'start_from' => 'required|integer|gt:0',
            'end_to' => 'required|integer|gt:0',
            'distance' => 'required',
            'time' => 'required',
            'stoppages' => 'nullable|array|min:1',
            'stoppages.*' => 'nullable|integer|gt:0',
        ],[
            'stoppages.*.integer' => 'Invalid Stoppage Field'
        ]);

        if($request->start_from == $request->end_to){
            $notify[] = ['error', 'Starting point and ending point can\'t be same'];
            return back()->withNotify($notify);
        }

        $stoppages = $request->stoppages ? array_filter($request->stoppages):[];

        if (!in_array($request->start_from, $stoppages)) {
            array_unshift($stoppages, $request->start_from);
        }

        if (!in_array($request->end_to, $stoppages)) {
            array_push($stoppages, $request->end_to);
        }

        $route = new VehicleRoute();
        $route->name = $request->name;
        $route->start_from = $request->start_from;
        $route->end_to = $request->end_to;
        $route->stoppages  = array_unique($stoppages);
        $route->distance = $request->distance;
        $route->time = $request->time;
        $route->save();

        $notify[] = ['success', 'Route save successfully'];
        return back()->withNotify($notify);
    }

    public function routeEdit($id){
        $route = VehicleRoute::findOrFail($id);
        $pageTitle = 'Update Route - ' . $route->name;
        $allStoppages = Counter::active()->get();

        $stoppagesArray = $route->stoppages;
        $pos = array_search($route->start_from, $stoppagesArray);
        unset($stoppagesArray[$pos]);
        $pos = array_search($route->end_to, $stoppagesArray);
        unset($stoppagesArray[$pos]);

        if(!empty($stoppagesArray)){

            $stoppages = Counter::active()->whereIn('id', $stoppagesArray)
                ->orderByRaw("field(id,".implode(',',$stoppagesArray).")")
                ->get();
        }else{
            $stoppages = [];
        }
        return view('admin.trip.route.edit', compact('pageTitle', 'stoppages', 'route', 'allStoppages'));
    }

    public function routeUpdate(Request $request, $id){
        $request->validate([
            'name' => 'required',
            'start_from' => 'required|integer|gt:0',
            'end_to' => 'required|integer|gt:0',
            'distance' => 'required',
            'time' => 'required',
            'stoppages' => 'nullable|array|min:1',
            'stoppages.*' => 'nullable|integer|gt:0',
        ],[
            'stoppages.*.integer' => 'Invalid Stoppage Field'
        ]);

        if($request->start_from == $request->end_to){
            $notify[] = ['error', 'Starting point and ending point can\'t be same'];
            return back()->withNotify($notify);
        }

        $stoppages = $request->stoppages ? array_filter($request->stoppages):[];

        if (!in_array($request->start_from, $stoppages)) {
            array_unshift($stoppages, $request->start_from);
        }

        if (!in_array($request->end_to, $stoppages)) {
            array_push($stoppages, $request->end_to);
        }

        $route = VehicleRoute::findOrFail($id);
        $route->name = $request->name;
        $route->start_from = $request->start_from;
        $route->end_to = $request->end_to;
        $route->stoppages  = array_unique($stoppages);
        $route->distance = $request->distance;
        $route->time = $request->time;
        $route->save();

        $notify[] = ['success', 'Route update successfully'];
        return back()->withNotify($notify);
    }

    public function routeActiveDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);

        $route = VehicleRoute::find($request->id);
        $route->status = $route->status == 1 ? 0 : 1;
        $route->save();

        if($route->status == 1){
            $notify[] = ['success', 'Route active successfully'];
        }else{
            $notify[] = ['success', 'Route disabled successfully'];
        }

        return back()->withNotify($notify);
    }

    public function schedules(){
        $pageTitle = 'All Schedules';
        $emptyMessage = 'No schedule found';
        $schedules = Schedule::orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.trip.schedule', compact('pageTitle','emptyMessage', 'schedules'));
    }

    public function schduleStore(Request $request){
        $request->validate([
            'start_from'   => 'required|date_format:H:i',
            'end_at'       => 'required|date_format:H:i',
        ]);

        $check = Schedule::where('start_from', Carbon::parse($request->start_from)->format('H:i:s'))->where('end_at', Carbon::parse($request->end_at)->format('H:i:s'))->first();
        if($check){
            $notify[] = ['error', 'This schedule has already added'];
            return redirect()->back()->withNotify($notify);
        }

        Schedule::create([
            'start_from' => $request->start_from,
            'end_at'     => $request->end_at
        ]);

        $notify[] = ['success', 'Schedule save successfully'];
        return back()->withNotify($notify);
    }

    public function schduleUpdate(Request $request, $id){
        $request->validate([
            'start_from'   => 'required|date_format:H:i',
            'end_at'       => 'required|date_format:H:i',
        ]);

        $check = Schedule::where('start_from', Carbon::parse($request->start_from)->format('H:i:s'))->where('end_at', Carbon::parse($request->end_at)->format('H:i:s'))->first();

        if($check && $check->id != $id){
            $notify[] = ['error', 'This schedule has already added'];
            return back()->withNotify($notify);
        }

        $schdule = Schedule::find($id);
        $schdule->start_from = $request->start_from;
        $schdule->end_at = $request->end_at;
        $schdule->save();

        $notify[] = ['success', 'Schedule update successfully'];
            return back()->withNotify($notify);
    }

    public function schduleActiveDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);

        $schdule = Schedule::find($request->id);
        $schdule->status = $schdule->status == 1 ? 0 : 1;
        $schdule->save();

        if($schdule->status == 1){
            $notify[] = ['success', 'Schedule active successfully'];
        }else{
            $notify[] = ['success', 'Schedule disabled successfully'];
        }

        return back()->withNotify($notify);
    }

    public function trips(){
        $pageTitle = "All Trip";
        $emptyMessage = "No trip found";
        $fleetTypes = FleetType::where('status', 1)->get();
        $routes = VehicleRoute::where('status', 1)->get();
        $schedules = Schedule::where('status', 1)->get();
        $stoppages = Counter::where('status', 1)->get();

        $trips = Trip::with(['fleetType', 'route', 'schedule'])->orderBy('id', 'desc')->paginate(getPaginate());

        return view('admin.trip.trip', compact('pageTitle', 'emptyMessage', 'trips' ,'fleetTypes', 'routes', 'schedules', 'stoppages'));
    }

    public function tripStore(Request $request){
        $request->validate([
            'title'      => 'required',
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'schedule'   => 'required|integer|gt:0',
            'start_from' => 'required|integer|gt:0',
            'end_to'     => 'required|integer|gt:0',
            'day_off'    => 'nullable|array|min:1'
        ]);

        $trip = new Trip();
        $trip->title = $request->title;
        $trip->fleet_type_id = $request->fleet_type;
        $trip->vehicle_route_id = $request->route;
        $trip->schedule_id = $request->schedule;
        $trip->start_from = $request->start_from;
        $trip->end_to = $request->end_to;
        $trip->day_off = $request->day_off ?? [];
        $trip->save();

        $notify[] = ['success', 'Trip save successfully'];
        return back()->withNotify($notify);
    }

    public function tripUpdate(Request $request, $id){
        $request->validate([
            'title'      => 'required',
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'schedule'   => 'required|integer|gt:0',
            'start_from' => 'required|integer|gt:0',
            'end_to'     => 'required|integer|gt:0',
            'day_off'    => 'nullable|array|min:1',
            'booking_time' => 'required|integer|gt:0'
        ]);

        $trip = Trip::find($id);
        $trip->title = $request->title;
        $trip->fleet_type_id = $request->fleet_type;
        $trip->vehicle_route_id = $request->route;
        $trip->schedule_id = $request->schedule;
        $trip->start_from = $request->start_from;
        $trip->end_to = $request->end_to;
        $trip->day_off = $request->day_off ?? [];
        $trip->save();

        $notify[] = ['success', 'Trip update successfully'];
        return back()->withNotify($notify);
    }

    public function tripActiveDisable(Request $request){
        $request->validate(['id' => 'required|integer']);

        $trip = Trip::find($request->id);
        $trip->status = $trip->status == 1 ? 0 : 1;
        $trip->save();

        if($trip->status == 1){
            $notify[] = ['success', 'Trip active successfully'];
        }else{
            $notify[] = ['success', 'Trip disabled successfully'];
        }

        return back()->withNotify($notify);
    }

    public function assignedVehicleLists(){
        $pageTitle = "All Assigned Vehicles";
        $emptyMessage = "No assigned vehicle found";
        $trips = Trip::with('fleetType.activeVehicles')->where('status', 1)->get();
        $assignedVehicles = AssignedVehicle::with(['trip', 'vehicle'])->orderBy('id', 'desc')->paginate(getPaginate());

        return view('admin.trip.assigned_vehicle', compact('pageTitle', 'emptyMessage', 'trips', 'assignedVehicles'));
    }

    public function assignVehicle(Request $request){
        $request->validate([
            'trip'      => 'required|integer|gt:0',
            'vehicle' => 'required|integer|gt:0'
        ]);

        //Check if the trip has already a assigned vehicle;
        $trip_check = AssignedVehicle::where('trip_id', $request->trip)->first();

        if($trip_check){
            $notify[]=['error','A vehicle had already been assinged to this trip'];
            return back()->withNotify($notify);
        }

        $trip = Trip::where('id', $request->trip)->with('schedule')->firstOrFail();

        $start_time = Carbon::parse($trip->schedule->start_from)->format('H:i:s');
        $end_time   = Carbon::parse($trip->schedule->end_at)->format('H:i:s');

        //Check if the vehicle assgined to another vehicle on this time
        $vehicle_check = AssignedVehicle::where(function($q) use($start_time,$end_time, $request){
                        $q->where('start_from','>=',$start_time)
                            ->where('start_from','<=',$end_time)
                            ->where('vehicle_id', $request->vehicle);
                        })
                    ->orWhere(function($q) use($start_time,$end_time, $request){
                            $q->where('end_at','>=',$start_time)
                            ->where('end_at','<=',$end_time)
                            ->where('vehicle_id', $request->vehicle);
                        })
                    ->first();


        if($vehicle_check){
            $notify[]=['error','This vehicle had already been assinged to another trip on this time'];
            return back()->withNotify($notify);
        }

        $assignedVehicle = new AssignedVehicle();
        $assignedVehicle->trip_id = $request->trip;
        $assignedVehicle->vehicle_id = $request->vehicle;
        $assignedVehicle->start_from = $trip->schedule->start_from;
        $assignedVehicle->end_at = $trip->schedule->end_at;
        $assignedVehicle->save();

        $notify[] = ['success', 'Vehicle assigned successfully.'];
        return back()->withNotify($notify);
    }

    public function assignedVehicleUpdate(Request $request, $id){
        $request->validate([
            'trip'      => 'required|integer|gt:0',
            'vehicle' => 'required|integer|gt:0'
        ]);

        //Check if the trip has already a assigned vehicle;
        $trip_check = AssignedVehicle::where('trip_id', $request->trip)->where('id', '!=', $id)->first();

        if($trip_check){
            $notify[]=['error','A vehicle had already been assinged to this trip'];
            return back()->withNotify($notify);
        }

        $trip = Trip::where('id', $request->trip)->with('schedule')->firstOrFail();

        $start_time = Carbon::parse($trip->schedule->start_from)->format('H:i:s');
        $end_time   = Carbon::parse($trip->schedule->end_at)->format('H:i:s');

        //Check if the vehicle assgined to another vehicle on this time
        $vehicle_check = AssignedVehicle::where(function($q) use($start_time,$end_time,$id,$request){
                        $q->where('start_from','>=',$start_time)
                            ->where('start_from','<=',$end_time)
                            ->where('id', '!=', $id)
                            ->where('vehicle_id', $request->vehicle);
                        })
                    ->orWhere(function($q) use($start_time,$end_time,$id,$request){
                            $q->where('end_at','>=',$start_time)
                            ->where('end_at','<=',$end_time)
                            ->where('id', '!=', $id)
                            ->where('vehicle_id', $request->vehicle);
                        })
                    ->first();


        if($vehicle_check){
            $notify[]=['error','This vehicle had already been assinged to another trip on this time'];
            return back()->withNotify($notify);
        }

        $assignedVehicle = AssignedVehicle::find($id);
        $assignedVehicle->trip_id = $request->trip;
        $assignedVehicle->vehicle_id = $request->vehicle;
        $assignedVehicle->start_from = $trip->schedule->start_from;
        $assignedVehicle->end_at = $trip->schedule->end_at;
        $assignedVehicle->save();
        $notify[] = ['success', 'Assigned vehicle update successfully.'];
        return back()->withNotify($notify);
    }

    public function assignedVehicleActiveDisabled(Request $request){
        $request->validate(['id' => 'required|integer']);

        $assignedVehicle = AssignedVehicle::find($request->id);
        $assignedVehicle->status = $assignedVehicle->status == 1 ? 0 : 1;
        $assignedVehicle->save();

        if($assignedVehicle->status == 1){
            $notify[] = ['success', 'Assigned Vehicle active successfully'];
        }else{
            $notify[] = ['success', 'Assigned Vehicle disabled successfully'];
        }
        return back()->withNotify($notify);
    }
}

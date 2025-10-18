<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminCrashResolveToTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix date_of_journey column
        DB::statement('UPDATE booked_tickets SET date_of_journey = CURDATE() WHERE date_of_journey = "0000-00-00" OR date_of_journey IS NULL');
        
        // Fix trip_id if missing
        $tickets = DB::table('booked_tickets')
            ->whereNull('trip_id')
            ->orWhere('trip_id', 0)
            ->get();
        
        foreach ($tickets as $ticket) {
            // Find a trip with matching source/destination
            if ($ticket->source_destination) {
                $sourceDestination = json_decode($ticket->source_destination);
                
                if (is_array($sourceDestination) && count($sourceDestination) >= 2) {
                    $trip = DB::table('trips')
                        ->where('start_from', $sourceDestination[0])
                        ->where('end_to', $sourceDestination[1])
                        ->first();
                    
                    if ($trip) {
                        DB::table('booked_tickets')
                            ->where('id', $ticket->id)
                            ->update(['trip_id' => $trip->id]);
                        continue;
                    }
                }
            }
            
            // If no matching trip found, create one
            $tripId = DB::table('trips')->insertGetId([
                'title' => 'Bus Trip',
                'start_from' => 1, // Default value
                'end_to' => 2, // Default value
                'schedule_id' => 1, // Default schedule
                'start_time' => date('H:i:s'),
                'end_time' => date('H:i:s', strtotime('+4 hours')),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('booked_tickets')
                ->where('id', $ticket->id)
                ->update(['trip_id' => $tripId]);
        }
        
        // Fix pickup and dropping points
        $tickets = DB::table('booked_tickets')->get();
        
        foreach ($tickets as $ticket) {
            // Check if pickup point exists
            $pickupExists = DB::table('counters')->where('id', $ticket->pickup_point)->exists();
            
            if (!$pickupExists) {
                DB::table('counters')->insert([
                    'id' => $ticket->pickup_point,
                    'name' => 'Pickup Point ' . $ticket->pickup_point,
                    'city' => 1, // Default city
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Check if dropping point exists
            $droppingExists = DB::table('counters')->where('id', $ticket->dropping_point)->exists();
            
            if (!$droppingExists) {
                DB::table('counters')->insert([
                    'id' => $ticket->dropping_point,
                    'name' => 'Dropping Point ' . $ticket->dropping_point,
                    'city' => 2, // Default city
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            //
        });
    }
}

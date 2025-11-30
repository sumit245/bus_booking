<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookedTicket;
use App\Services\SeatAvailabilityService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpirePendingTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:expire-pending 
                            {--minutes=15 : Minutes after which pending tickets expire (default: 15)}
                            {--dry-run : Show what would be expired without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending tickets (status 0) that have not been paid within the specified time period. Changes status to 4 (Expired/Abandoned) and releases seats for other users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        $this->info("🕒 Expiring pending tickets older than {$minutes} minutes...");
        $this->line('');

        // Calculate the cutoff time
        $cutoffTime = Carbon::now()->subMinutes($minutes);

        // Find all pending tickets (status 0) created before the cutoff time
        $pendingTickets = BookedTicket::where('status', 0) // Pending status
            ->where('created_at', '<', $cutoffTime)
            ->get();

        if ($pendingTickets->isEmpty()) {
            $this->info("✅ No pending tickets to expire (all tickets are within {$minutes} minutes or already processed).");
            return 0;
        }

        $this->info("Found {$pendingTickets->count()} pending ticket(s) to expire:");
        $this->line('');

        $expiredCount = 0;
        $seatAvailabilityService = new SeatAvailabilityService();

        foreach ($pendingTickets as $ticket) {
            $ageInMinutes = $ticket->created_at->diffInMinutes(Carbon::now());
            
            $this->line("  - Ticket #{$ticket->id} (PNR: {$ticket->pnr_number})");
            $this->line("    Created: {$ticket->created_at->format('Y-m-d H:i:s')} ({$ageInMinutes} minutes ago)");
            $this->line("    Seats: " . (is_array($ticket->seats) ? implode(', ', $ticket->seats) : $ticket->seats));
            
            if ($ticket->bus_id && $ticket->schedule_id && $ticket->date_of_journey) {
                $this->line("    Bus ID: {$ticket->bus_id}, Schedule ID: {$ticket->schedule_id}, Date: {$ticket->date_of_journey}");
            }

            if (!$dryRun) {
                // Update ticket status to 4 (Expired/Abandoned)
                $ticket->update([
                    'status' => 4, // Expired/Abandoned status
                    'expired_at' => Carbon::now(),
                ]);

                // Invalidate seat availability cache so seats are immediately available
                if ($ticket->bus_id && $ticket->schedule_id && $ticket->date_of_journey) {
                    $seatAvailabilityService->invalidateCache(
                        $ticket->bus_id,
                        $ticket->schedule_id,
                        $ticket->date_of_journey
                    );
                    
                    $this->line("    ✅ Status updated to 4 (Expired) and seat cache invalidated");
                } else {
                    $this->line("    ✅ Status updated to 4 (Expired) - no seat cache to invalidate");
                }

                $expiredCount++;
                
                Log::info('Pending ticket expired automatically', [
                    'ticket_id' => $ticket->id,
                    'pnr_number' => $ticket->pnr_number,
                    'created_at' => $ticket->created_at,
                    'expired_at' => $ticket->expired_at,
                    'age_minutes' => $ageInMinutes,
                    'bus_id' => $ticket->bus_id,
                    'schedule_id' => $ticket->schedule_id,
                    'date_of_journey' => $ticket->date_of_journey,
                ]);
            } else {
                $this->line("    [DRY RUN] Would expire this ticket");
            }
            
            $this->line('');
        }

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE: No tickets were actually expired.");
            $this->info("Run without --dry-run to actually expire these tickets.");
        } else {
            $this->info("✅ Successfully expired {$expiredCount} ticket(s).");
            $this->info("Seats have been released and are now available for booking.");
        }

        return 0;
    }
}

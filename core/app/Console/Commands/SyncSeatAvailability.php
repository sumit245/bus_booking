<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookedTicket;
use App\Services\SeatAvailabilityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncSeatAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seat-availability:sync 
                            {--bus-id= : Sync specific bus ID only}
                            {--schedule-id= : Sync specific schedule ID only}
                            {--date= : Sync specific date (Y-m-d format)}
                            {--clear-all : Clear all seat availability cache}
                            {--from-date= : Sync bookings from this date onwards (Y-m-d format)}
                            {--to-date= : Sync bookings until this date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync seat availability cache for existing bookings. Invalidates cache for all operator bus bookings so seat layouts reflect current booking state.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('🔄 Starting seat availability cache sync...');
        $this->line('');

        try {
            // Clear all cache if requested
            if ($this->option('clear-all')) {
                return $this->clearAllCache();
            }

            // Get unique combinations of bus_id, schedule_id, and date_of_journey from bookings
            $query = BookedTicket::whereNotNull('bus_id')
                ->whereNotNull('schedule_id')
                ->whereNotNull('date_of_journey')
                ->whereIn('status', [0, 1]); // pending or confirmed

            // Apply filters
            if ($busId = $this->option('bus-id')) {
                $query->where('bus_id', $busId);
            }

            if ($scheduleId = $this->option('schedule-id')) {
                $query->where('schedule_id', $scheduleId);
            }

            if ($date = $this->option('date')) {
                try {
                    $parsedDate = Carbon::parse($date)->format('Y-m-d');
                    $query->whereDate('date_of_journey', $parsedDate);
                } catch (\Exception $e) {
                    $this->error("Invalid date format: {$date}. Use Y-m-d format (e.g., 2025-11-27)");
                    return 1;
                }
            }

            if ($fromDate = $this->option('from-date')) {
                try {
                    $parsedDate = Carbon::parse($fromDate)->format('Y-m-d');
                    $query->whereDate('date_of_journey', '>=', $parsedDate);
                } catch (\Exception $e) {
                    $this->error("Invalid from-date format: {$fromDate}. Use Y-m-d format");
                    return 1;
                }
            }

            if ($toDate = $this->option('to-date')) {
                try {
                    $parsedDate = Carbon::parse($toDate)->format('Y-m-d');
                    $query->whereDate('date_of_journey', '<=', $parsedDate);
                } catch (\Exception $e) {
                    $this->error("Invalid to-date format: {$toDate}. Use Y-m-d format");
                    return 1;
                }
            }

            // Get unique combinations
            $uniqueCombinations = $query->select('bus_id', 'schedule_id', 'date_of_journey')
                ->distinct()
                ->get();

            if ($uniqueCombinations->isEmpty()) {
                $this->warn('⚠️  No bookings found matching the criteria.');
                return 0;
            }

            $this->info("📊 Found {$uniqueCombinations->count()} unique bus/schedule/date combinations to sync");
            $this->line('');

            // Show progress bar
            $bar = $this->output->createProgressBar($uniqueCombinations->count());
            $bar->start();

            $synced = 0;
            $errors = 0;
            $availabilityService = new SeatAvailabilityService();

            foreach ($uniqueCombinations as $combination) {
                try {
                    // Normalize date format
                    $dateOfJourney = $combination->date_of_journey;
                    if ($dateOfJourney instanceof Carbon) {
                        $dateOfJourney = $dateOfJourney->format('Y-m-d');
                    } elseif (is_string($dateOfJourney)) {
                        // Handle m/d/Y format from session
                        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                            $dateOfJourney = Carbon::createFromFormat('m/d/Y', $dateOfJourney)->format('Y-m-d');
                        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
                            $dateOfJourney = Carbon::parse($dateOfJourney)->format('Y-m-d');
                        }
                    }

                    // Invalidate cache for this combination
                    $availabilityService->invalidateCache(
                        $combination->bus_id,
                        $combination->schedule_id,
                        $dateOfJourney
                    );

                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('SyncSeatAvailability: Error invalidating cache', [
                        'bus_id' => $combination->bus_id,
                        'schedule_id' => $combination->schedule_id,
                        'date_of_journey' => $combination->date_of_journey,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->line('');
            $this->line('');

            // Display results
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $this->info('📈 Sync Results:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['✅ Cache Entries Invalidated', $synced],
                    ['❌ Errors', $errors],
                    ['⏱️  Execution Time', $executionTime . 'ms']
                ]
            );

            if ($errors === 0) {
                $this->info('🎉 Seat availability cache sync completed successfully!');
                $this->line('');
                $this->comment('💡 Next time you view seat layouts, they will show all currently booked seats.');
            } else {
                $this->warn('⚠️  Sync completed with some errors. Check logs for details.');
            }

            return $errors > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Critical error: ' . $e->getMessage());
            Log::error('SyncSeatAvailability command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Clear all seat availability cache
     */
    private function clearAllCache(): int
    {
        $this->warn('⚠️  This will clear ALL seat availability cache entries.');
        
        if (!$this->confirm('Are you sure you want to clear all cache?', false)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('🗑️  Clearing all seat availability cache...');
        
        // Since Laravel cache doesn't support wildcard deletion, we'll need to clear
        // the cache entries we know about. For a complete clear, you might need to
        // use cache driver-specific methods or clear the entire cache.
        
        // For now, we'll invalidate cache for all existing bookings
        $this->info('📊 Finding all unique bus/schedule/date combinations...');
        
        $uniqueCombinations = BookedTicket::whereNotNull('bus_id')
            ->whereNotNull('schedule_id')
            ->whereNotNull('date_of_journey')
            ->whereIn('status', [0, 1])
            ->select('bus_id', 'schedule_id', 'date_of_journey')
            ->distinct()
            ->get();

        $availabilityService = new SeatAvailabilityService();
        $bar = $this->output->createProgressBar($uniqueCombinations->count());
        $bar->start();

        $cleared = 0;
        foreach ($uniqueCombinations as $combination) {
            try {
                $dateOfJourney = $combination->date_of_journey;
                if ($dateOfJourney instanceof Carbon) {
                    $dateOfJourney = $dateOfJourney->format('Y-m-d');
                } elseif (is_string($dateOfJourney)) {
                    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                        $dateOfJourney = Carbon::createFromFormat('m/d/Y', $dateOfJourney)->format('Y-m-d');
                    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
                        $dateOfJourney = Carbon::parse($dateOfJourney)->format('Y-m-d');
                    }
                }

                $availabilityService->invalidateCache(
                    $combination->bus_id,
                    $combination->schedule_id,
                    $dateOfJourney
                );
                $cleared++;
            } catch (\Exception $e) {
                // Continue on error
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info("✅ Cleared cache for {$cleared} combinations.");
        $this->comment('💡 You may also want to run: php artisan cache:clear (if using file cache)');

        return 0;
    }
}


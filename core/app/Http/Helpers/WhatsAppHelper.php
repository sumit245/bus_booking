<?php

namespace App\Http\Helpers;

use App\Models\Staff;
use App\Models\OperatorBus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    /**
     * Send WhatsApp notification to staff about booking
     */
    public static function sendBookingNotification($staffId, $bookingDetails)
    {
        try {
            $staff = Staff::find($staffId);

            if (!$staff || !$staff->canReceiveWhatsAppNotifications()) {
                Log::info("Staff {$staffId} cannot receive WhatsApp notifications");
                return false;
            }

            // Use the same template structure as sendTicketDetailsWhatsApp
            $templateParams = self::formatBookingTemplateParams($bookingDetails, $staff);

            return self::sendMessage($staff->whatsapp_number, '', $templateParams);

        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp notification to staff {$staffId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp notification to crew members of a bus
     */
    public static function sendCrewBookingNotification($operatorBusId, $bookingDetails)
    {
        try {
            $bus = OperatorBus::with(['crewAssignments.staff'])->find($operatorBusId);

            if (!$bus) {
                try {
                    Log::error("Bus {$operatorBusId} not found for crew notification");
                } catch (\Exception $logException) {
                    // Ignore logging errors
                }
                return false;
            }

            $results = [];
            // Get all active crew assignments for this bus (no date filtering - crew are assigned permanently until changed)
            $activeAssignments = $bus->crewAssignments()
                ->where('status', 'active')
                ->with('staff')
                ->get();

            foreach ($activeAssignments as $assignment) {
                if ($assignment->staff && $assignment->staff->canReceiveWhatsAppNotifications()) {
                    $templateParams = self::formatCrewBookingTemplateParams($bookingDetails, $assignment->staff, $assignment->role);
                    $results[] = [
                        'staff_id' => $assignment->staff->id,
                        'staff_name' => $assignment->staff->full_name,
                        'role' => $assignment->role,
                        'success' => self::sendMessage($assignment->staff->whatsapp_number, '', $templateParams)
                    ];
                }
            }


            return $results;

        } catch (\Exception $e) {
            try {
                Log::error("Failed to send crew WhatsApp notifications for bus {$operatorBusId}: " . $e->getMessage());
            } catch (\Exception $logException) {
                // Ignore logging errors
            }
            return false;
        }
    }

    /**
     * Send general WhatsApp message using the same API structure as sendTicketDetailsWhatsApp
     */
    public static function sendMessage($phoneNumber, $message, $templateParams = [])
    {
        try {
            // Remove any non-numeric characters from phone number
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Add country code if not present (assuming India +91)
            if (!str_starts_with($phoneNumber, '91') && strlen($phoneNumber) === 10) {
                $phoneNumber = '91' . $phoneNumber;
            }

            $apiUrl = env('WHATSAPP_API_URL');
            $apiKey = env('WHATSAPP_API_KEY');

            if (!$apiUrl || !$apiKey) {
                Log::error("WhatsApp API configuration missing", [
                    'api_url' => $apiUrl,
                    'api_key' => $apiKey ? 'present' : 'missing'
                ]);
                return false;
            }

            // Use the same payload structure as sendTicketDetailsWhatsApp
            $payload = [
                'apiKey' => $apiKey,
                'campaignName' => 'booking-notification',
                'destination' => $phoneNumber,
                'userName' => 'Staff Member',
                'templateParams' => $templateParams,
                'source' => 'bus-booking-system',
                'media' => [],
                'buttons' => [],
                'carouselCards' => [],
                'location' => [],
                'paramsFallbackValue' => [
                    'FirstName' => 'Staff Member',
                ],
            ];

            try {
                Log::info("Sending WhatsApp notification", [
                    'phone' => $phoneNumber,
                    'api_url' => $apiUrl,
                    'payload' => $payload
                ]);
            } catch (\Exception $logException) {
                // Ignore logging errors - they shouldn't break the WhatsApp functionality
            }

            $response = Http::post($apiUrl, $payload);

            if ($response->successful()) {
                try {
                    Log::info("WhatsApp message sent successfully", [
                        'phone' => $phoneNumber,
                        'response' => $response->json()
                    ]);
                } catch (\Exception $logException) {
                    // Ignore logging errors
                }
                return true;
            } else {
                try {
                    Log::error("WhatsApp API error", [
                        'phone' => $phoneNumber,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                } catch (\Exception $logException) {
                    // Ignore logging errors
                }
                return false;
            }

        } catch (\Exception $e) {
            try {
                Log::error("Failed to send WhatsApp message", [
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Exception $logException) {
                // Ignore logging errors
            }
            return false;
        }
    }

    /**
     * Format booking template parameters for staff notification
     */
    private static function formatBookingTemplateParams($bookingDetails, $staff)
    {
        // Use the same structure as sendTicketDetailsWhatsApp
        return [
            $bookingDetails['source_name'] ?? 'N/A',
            $bookingDetails['destination_name'] ?? 'N/A',
            $bookingDetails['date_of_journey'] ?? 'N/A',
            $bookingDetails['pnr'] ?? 'N/A',
            $bookingDetails['seats'] ?? 'N/A',
            $bookingDetails['boarding_details'] ?? 'N/A',
            $bookingDetails['drop_off_details'] ?? 'N/A',
        ];
    }

    /**
     * Format crew booking template parameters
     */
    private static function formatCrewBookingTemplateParams($bookingDetails, $staff, $role)
    {
        // Use the same structure as sendTicketDetailsWhatsApp but with role-specific info
        return [
            $bookingDetails['source_name'] ?? 'N/A',
            $bookingDetails['destination_name'] ?? 'N/A',
            $bookingDetails['date_of_journey'] ?? 'N/A',
            $bookingDetails['boarding_details'] ?? 'N/A',
            $bookingDetails['drop_off_details'] ?? 'N/A',
            $bookingDetails['seats'] ?? 'N/A',
            // $bookingDetails['pnr'] ?? 'N/A',

        ];
    }

    /**
     * Send attendance reminder
     */
    public static function sendAttendanceReminder($staffId, $date)
    {
        try {
            $staff = Staff::find($staffId);

            if (!$staff || !$staff->canReceiveWhatsAppNotifications()) {
                return false;
            }

            $message = "⏰ *ATTENDANCE REMINDER*\n\n";
            $message .= "Hello {$staff->first_name},\n\n";
            $message .= "This is a reminder to mark your attendance for:\n";
            $message .= "📅 *Date:* {$date}\n\n";
            $message .= "Please check in when you arrive at work.\n\n";
            $message .= "Thank you!";

            return self::sendMessage($staff->whatsapp_number, $message);

        } catch (\Exception $e) {
            Log::error("Failed to send attendance reminder to staff {$staffId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send salary notification
     */
    public static function sendSalaryNotification($staffId, $salaryDetails)
    {
        try {
            $staff = Staff::find($staffId);

            if (!$staff || !$staff->canReceiveWhatsAppNotifications()) {
                return false;
            }

            $message = "💰 *SALARY NOTIFICATION*\n\n";
            $message .= "Hello {$staff->first_name},\n\n";
            $message .= "Your salary for {$salaryDetails['period']} has been processed:\n\n";
            $message .= "💵 *Net Salary:* ₹{$salaryDetails['net_salary']}\n";
            $message .= "📅 *Payment Date:* {$salaryDetails['payment_date']}\n";
            $message .= "🏦 *Payment Method:* {$salaryDetails['payment_method']}\n";

            if ($salaryDetails['payment_reference']) {
                $message .= "🔗 *Reference:* {$salaryDetails['payment_reference']}\n";
            }

            $message .= "\nThank you for your hard work!\n\n";
            $message .= "Bus Booking System";

            return self::sendMessage($staff->whatsapp_number, $message);

        } catch (\Exception $e) {
            Log::error("Failed to send salary notification to staff {$staffId}: " . $e->getMessage());
            return false;
        }
    }
}

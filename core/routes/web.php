<?php

use App\Http\Controllers\Admin\OperatorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ManageTripController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\API\UserController;

Route::get("/clear", function () {
    \Illuminate\Support\Facades\Artisan::call("optimize:clear");
});

// Serve PWA manifest and service worker for Agent Panel
// These are named so blade templates can reference route('agent.manifest') and route('agent.sw')
Route::get('/agent-manifest.json', function () {
    $path = public_path('agent-manifest.json');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'application/manifest+json']);
})->name('agent.manifest');

Route::get('/agent-sw.js', function () {
    $path = public_path('agent-sw.js');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'application/javascript']);
})->name('agent.sw');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::
        namespace("Gateway")
    ->prefix("ipn")
    ->name("ipn.")
    ->group(function () {
        Route::post("razorpay", "Razorpay\ProcessController@ipn")->name(
            "Razorpay",
        );
        // Deleted unnecessary payment gateway
    });

// User Support Ticket
Route::prefix("ticket")->group(function () {
    Route::get("/", "TicketController@supportTicket")->name("support_ticket");
    Route::get("/new", "TicketController@openSupportTicket")
        ->name("ticket.open")
        ->middleware("auth");
    Route::post("/create", "TicketController@storeSupportTicket")
        ->name("ticket.store")
        ->middleware("auth");
    Route::get("/view/{ticket}", "TicketController@viewTicket")
        ->name("ticket.view")
        ->middleware("auth");
    Route::post("/reply/{ticket}", "TicketController@replyTicket")
        ->name("ticket.reply")
        ->middleware("auth");
    Route::get("/download/{ticket}", "TicketController@ticketDownload")
        ->name("ticket.download")
        ->middleware("auth");
});

// Admin Ticket Routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'admin']], function () {
    Route::get('ticket/details', 'Admin\VehicleTicketController@ticketDetails')->name('admin.ticket.details');
    Route::post('ticket/cancel', 'Admin\VehicleTicketController@cancelTicket')->name('admin.ticket.cancel');
    Route::post('ticket/refund', 'Admin\VehicleTicketController@refundTicket')->name('admin.ticket.refund');
});

/*
|--------------------------------------------------------------------------
| Start Admin Area
|--------------------------------------------------------------------------
*/

Route::
        namespace("Admin")
    ->prefix("admin")
    ->name("admin.")
    ->group(function () {
        Route::namespace("Auth")->group(function () {
            Route::get("/", "LoginController@showLoginForm")->name("login");
            Route::post("/", "LoginController@login");
            Route::get("logout", "LoginController@logout")->name("logout");
            // Admin Password Reset
            Route::get(
                "password/reset",
                "ForgotPasswordController@showLinkRequestForm",
            )->name("password.reset");
            Route::post(
                "password/reset",
                "ForgotPasswordController@sendResetCodeEmail",
            );
            Route::post(
                "password/verify-code",
                "ForgotPasswordController@verifyCode",
            )->name("password.verify.code");
            Route::get(
                "password/reset/{token}",
                "ResetPasswordController@showResetForm",
            )->name("password.reset.form");
            Route::post(
                "password/reset/change",
                "ResetPasswordController@reset",
            )->name("password.change");
        });

        Route::middleware("admin")->group(function () {
            Route::get("dashboard", "AdminController@dashboard")->name(
                "dashboard",
            );
            Route::get("profile", "AdminController@profile")->name("profile");
            Route::post("profile", "AdminController@profileUpdate")->name(
                "profile.update",
            );
            Route::get("password", "AdminController@password")->name(
                "password",
            );
            Route::post("password", "AdminController@passwordUpdate")->name(
                "password.update",
            );

            //Notification
            Route::get("notifications", "AdminController@notifications")->name(
                "notifications",
            );
            Route::get(
                "notification/read/{id}",
                "AdminController@notificationRead",
            )->name("notification.read");
            Route::get(
                "notifications/read-all",
                "AdminController@readAll",
            )->name("notifications.readAll");

            //Report Bugs
            Route::get("request-report", "AdminController@requestReport")->name(
                "request.report",
            );
            Route::post("request-report", "AdminController@reportSubmit");

            Route::get("system-info", "AdminController@systemInfo")->name(
                "system.info",
            );

            // Users Manager
            Route::get("users", "ManageUsersController@allUsers")->name(
                "users.all",
            );
            Route::get(
                "users/active",
                "ManageUsersController@activeUsers",
            )->name("users.active");
            Route::get(
                "users/banned",
                "ManageUsersController@bannedUsers",
            )->name("users.banned");
            Route::get(
                "users/email-verified",
                "ManageUsersController@emailVerifiedUsers",
            )->name("users.email.verified");
            Route::get(
                "users/email-unverified",
                "ManageUsersController@emailUnverifiedUsers",
            )->name("users.email.unverified");
            Route::get(
                "users/sms-unverified",
                "ManageUsersController@smsUnverifiedUsers",
            )->name("users.sms.unverified");
            Route::get(
                "users/sms-verified",
                "ManageUsersController@smsVerifiedUsers",
            )->name("users.sms.verified");

            Route::get(
                "users/{scope}/search",
                "ManageUsersController@search",
            )->name("users.search");
            Route::get(
                "user/detail/{id}",
                "ManageUsersController@detail",
            )->name("users.detail");
            Route::post(
                "user/update/{id}",
                "ManageUsersController@update",
            )->name("users.update");
            Route::post(
                "user/add-sub-balance/{id}",
                "ManageUsersController@addSubBalance",
            )->name("users.add.sub.balance");
            Route::get(
                "user/send-email/{id}",
                "ManageUsersController@showEmailSingleForm",
            )->name("users.email.single");
            Route::post(
                "user/send-email/{id}",
                "ManageUsersController@sendEmailSingle",
            )->name("users.email.send");
            Route::get("user/login/{id}", "ManageUsersController@login")->name(
                "users.login",
            );
            Route::get(
                "user/transactions/{id}",
                "ManageUsersController@transactions",
            )->name("users.transactions");
            Route::get(
                "user/deposits/{id}",
                "ManageUsersController@deposits",
            )->name("users.deposits");
            Route::get(
                "user/deposits/via/{method}/{type?}/{userId}",
                "ManageUsersController@depositViaMethod",
            )->name("users.deposits.method");
            Route::get(
                "user/withdrawals/{id}",
                "ManageUsersController@withdrawals",
            )->name("users.withdrawals");
            Route::get(
                "user/withdrawals/via/{method}/{type?}/{userId}",
                "ManageUsersController@withdrawalsViaMethod",
            )->name("users.withdrawals.method");
            // Login History
            Route::get(
                "users/login/history/{id}",
                "ManageUsersController@userLoginHistory",
            )->name("users.login.history.single");

            Route::get(
                "users/send-email",
                "ManageUsersController@showEmailAllForm",
            )->name("users.email.all");
            Route::post(
                "users/send-email",
                "ManageUsersController@sendEmailAll",
            )->name("users.email.send");
            Route::get(
                "users/email-log/{id}",
                "ManageUsersController@emailLog",
            )->name("users.email.log");
            Route::get(
                "users/email-details/{id}",
                "ManageUsersController@emailDetails",
            )->name("users.email.details");

            /*
                |--------------------------------------------------------------------------
                | Transport Manage portion
                |--------------------------------------------------------------------------
                */

            //manage counter
            Route::name("manage.")
                ->prefix("manage")
                ->group(function () {
                Route::get("counter", "CounterController@counters")->name(
                    "counter",
                );
                Route::post(
                    "counter",
                    "CounterController@counterStore",
                )->name("counter.store");
                Route::post(
                    "counter/update/{id}",
                    "CounterController@counterUpdate",
                )->name("counter.update");
                Route::post(
                    "counter/active-disable",
                    "CounterController@counterActiveDisabled",
                )->name("counter.active.disable");
            });

            // Fleet & Trip manage
            Route::name("fleet.")
                ->prefix("manage")
                ->group(function () {
                //seat layouts
                Route::get(
                    "seat_layouts",
                    "ManageFleetController@seatLayouts",
                )->name("seat.layouts");
                Route::post(
                    "seat_layouts",
                    "ManageFleetController@seatLayoutStore",
                )->name("seat.layouts.store");
                Route::post(
                    "seat_layouts/remove",
                    "ManageFleetController@seatLayoutDelete",
                )->name("seat.layouts.delete");
                Route::post(
                    "seat_layouts/{id}",
                    "ManageFleetController@seatLayoutUpdate",
                )->name("seat.layouts.update");

                //fleet type
                Route::get(
                    "fleet-type",
                    "ManageFleetController@fleetLists",
                )->name("type");
                Route::post(
                    "fleet-type",
                    "ManageFleetController@fleetTypeStore",
                )->name("type.store");
                Route::post(
                    "fleet-type/update/{id}",
                    "ManageFleetController@fleetTypeUpdate",
                )->name("type.update");
                Route::post(
                    "fleet-type/active-disable",
                    "ManageFleetController@fleetEnableDisabled",
                )->name("type.active.disable");

                //vechiles
                Route::get(
                    "vehicles",
                    "ManageFleetController@vehicles",
                )->name("vehicles");
                Route::post(
                    "vehicles",
                    "ManageFleetController@vehiclesStore",
                )->name("vehicles.store");
                Route::post(
                    "vehicles/update/{id}",
                    "ManageFleetController@vehiclesUpdate",
                )->name("vehicles.update");
                Route::post(
                    "vehicles/active-disable",
                    "ManageFleetController@vehiclesActiveDisabled",
                )->name("vehicles.active.disable");
                Route::get(
                    "vehicles/search",
                    "ManageFleetController@vehicleSearch",
                )->name("vehicles.search");
            });

            // Operator Management Routes
            Route::resource("manage/operators", "OperatorController")->names([
                "index" => "fleet.operators.index",
                "create" => "fleet.operators.create",
                "store" => "fleet.operators.store",
                "show" => "fleet.operators.show",
                "edit" => "fleet.operators.edit",
                "update" => "fleet.operators.update",
                "destroy" => "fleet.operators.destroy",
            ]);

            Route::get("manage/buses", function () {
                return view("admin.fleet.bus", [
                    "pageTitle" => "Add New Bus",
                ]);
            })->name("fleet.buses");
            Route::get("/add", function () {
                return view("admin.fleet.addbus", [
                    "pageTitle" => "Add New Bus",
                ]);
            })->name("fleet.add");
            Route::get("/edit", function () {
                return view("admin.fleet.editbus", [
                    "pageTitle" => "Edit Bus",
                ]);
            })->name("fleet.edit");

            //manage trip
            Route::name("trip.")
                ->prefix("manage")
                ->group(function () {
                //route
                Route::get("route", "ManageTripController@routeList")->name(
                    "route",
                );
                Route::get(
                    "route/create",
                    "ManageTripController@routeCreate",
                )->name("route.create");
                Route::get(
                    "route/edit/{id}",
                    "ManageTripController@routeEdit",
                )->name("route.edit");
                Route::post(
                    "route",
                    "ManageTripController@routeStore",
                )->name("route.store");
                Route::post(
                    "route/update/{id}",
                    "ManageTripController@routeUpdate",
                )->name("route.update");
                Route::post(
                    "route/active-disable",
                    "ManageTripController@routeActiveDisabled",
                )->name("route.active.disable");

                //schedule
                Route::get(
                    "schedule",
                    "ManageTripController@schedules",
                )->name("schedule");
                Route::post(
                    "schedule",
                    "ManageTripController@schduleStore",
                )->name("schedule.store");
                Route::post(
                    "schedule/update/{id}",
                    "ManageTripController@schduleUpdate",
                )->name("schedule.update");
                Route::post(
                    "schedule/active-disable",
                    "ManageTripController@schduleActiveDisabled",
                )->name("schedule.active.disable");

                //ticket price
                Route::get(
                    "ticket-price",
                    "VehicleTicketController@ticketPriceList",
                )->name("ticket.price");
                Route::get(
                    "ticket-price/create",
                    "VehicleTicketController@ticketPriceCreate",
                )->name("ticket.price.create");
                Route::post(
                    "ticket-price",
                    "VehicleTicketController@ticketPriceStore",
                )->name("ticket.price.store");
                Route::get(
                    "route-data",
                    "VehicleTicketController@getRouteData",
                )->name("ticket.get_route_data");
                Route::get(
                    "ticket-price/check_price",
                    "VehicleTicketController@checkTicketPrice",
                )->name("ticket.check_price");
                Route::get(
                    "ticket-price/edit/{id}",
                    "VehicleTicketController@ticketPriceEdit",
                )->name("ticket.price.edit");
                Route::post(
                    "ticket-price/update/{id}",
                    "VehicleTicketController@ticketPriceUpdate",
                )->name("ticket.price.update");
                Route::post(
                    "ticket-price/delete",
                    "VehicleTicketController@ticketPriceDelete",
                )->name("ticket.price.delete");

                //trip
                Route::get("trip", "ManageTripController@trips")->name(
                    "list",
                );
                Route::post("trip", "ManageTripController@tripStore")->name(
                    "store",
                );
                Route::post(
                    "trip/update/{id}",
                    "ManageTripController@tripUpdate",
                )->name("update");
                Route::post(
                    "trip/active-disable",
                    "ManageTripController@tripActiveDisable",
                )->name("active.disable");
                Route::get(
                    "assigned-vehicle",
                    "ManageTripController@assignedVehicleLists",
                )->name("vehicle.assign");
                Route::post(
                    "assigned-vehicle",
                    "ManageTripController@assignVehicle",
                )->name("vehicle.assign");
                Route::post(
                    "assigned-vehicle/update/{id}",
                    "ManageTripController@assignedVehicleUpdate",
                )->name("assigned.vehicle.update");
                Route::post(
                    "assigned-vehicle/active-disable",
                    "ManageTripController@assignedVehicleActiveDisabled",
                )->name("assigned.vehicle.active.disable");
                Route::get("markup", "ManageTripController@markup")->name(
                    "markup",
                );
            });

            // Coupon Management
            Route::name("coupon.")
                ->prefix("coupon")
                ->group(function () {
                Route::get("/", "CouponController@index")->name("index");
                Route::post("store", "CouponController@store")->name(
                    "store",
                );
                Route::post(
                    "activate/{id}",
                    "CouponController@activate",
                )->name("activate");
                Route::post(
                    "deactivate/{id}",
                    "CouponController@deactivate",
                )->name("deactivate");
                Route::post("delete/{id}", "CouponController@delete")->name(
                    "delete",
                );
            });

            // DEPOSIT SYSTEM
            Route::name("deposit.")
                ->prefix("payment")
                ->group(function () {
                Route::get("pending", "DepositController@pending")->name(
                    "pending",
                );
                Route::get(
                    "successful",
                    "DepositController@successful",
                )->name("successful");
                Route::get("rejected", "DepositController@rejected")->name(
                    "rejected",
                );
                Route::get("all", "DepositController@all")->name("all");
                Route::get(
                    "details/{id}",
                    "DepositController@details",
                )->name("details");

                Route::post("reject", "DepositController@reject")->name(
                    "reject",
                );
                Route::post("approve", "DepositController@approve")->name(
                    "approve",
                );
                Route::get(
                    "via/{method}/{type?}",
                    "DepositController@depositViaMethod",
                )->name("method");
                Route::get(
                    "/{scope}/search",
                    "DepositController@search",
                )->name("search");
                Route::get(
                    "date-search/{scope}",
                    "DepositController@dateSearch",
                )->name("dateSearch");
            });

            // Deposit Gateway
            Route::name("gateway.")
                ->prefix("gateway")
                ->group(function () {
                // Automatic Gateway
                Route::get("automatic", "GatewayController@index")->name(
                    "automatic.index",
                );
                Route::get(
                    "automatic/edit/{alias}",
                    "GatewayController@edit",
                )->name("automatic.edit");
                Route::post(
                    "automatic/update/{code}",
                    "GatewayController@update",
                )->name("automatic.update");
                Route::post(
                    "automatic/remove/{code}",
                    "GatewayController@remove",
                )->name("automatic.remove");
                Route::post(
                    "automatic/activate",
                    "GatewayController@activate",
                )->name("automatic.activate");
                Route::post(
                    "automatic/deactivate",
                    "GatewayController@deactivate",
                )->name("automatic.deactivate");

                // Manual Methods
                Route::get("manual", "ManualGatewayController@index")->name(
                    "manual.index",
                );
                Route::get(
                    "manual/new",
                    "ManualGatewayController@create",
                )->name("manual.create");
                Route::post(
                    "manual/new",
                    "ManualGatewayController@store",
                )->name("manual.store");
                Route::get(
                    "manual/edit/{alias}",
                    "ManualGatewayController@edit",
                )->name("manual.edit");
                Route::post(
                    "manual/update/{id}",
                    "ManualGatewayController@update",
                )->name("manual.update");
                Route::post(
                    "manual/activate",
                    "ManualGatewayController@activate",
                )->name("manual.activate");
                Route::post(
                    "manual/deactivate",
                    "ManualGatewayController@deactivate",
                )->name("manual.deactivate");
            });

            // Admin Booking Routes
            Route::name("booking.")
                ->prefix("booking")
                ->group(function () {
                Route::get("/search", "BookingController@search")->name("search");
                Route::get("/results", "BookingController@results")->name("results");
                // Reuse SiteController methods for seat selection and booking
                Route::get("/seats/{id}/{slug}", "\App\Http\Controllers\SiteController@selectSeat")->name("seats");
                Route::post("/block-seat", "\App\Http\Controllers\SiteController@blockSeat")->name("block");
                Route::post("/book", "\App\Http\Controllers\SiteController@bookTicketApi")->name("book");
            });

            // ticket booking history
            Route::name("vehicle.ticket.")
                ->prefix("ticket")
                ->group(function () {
                // New unified route with filter support
                Route::get("/", "VehicleTicketController@index")->name("index");

                // Backward compatibility: Old routes redirect to new filter-based approach
                Route::get(
                    "booked",
                    "VehicleTicketController@booked",
                )->name("booked");
                Route::get(
                    "pending",
                    "VehicleTicketController@pending",
                )->name("pending");
                Route::get(
                    "rejected",
                    "VehicleTicketController@rejected",
                )->name("rejected");
                Route::get("list", "VehicleTicketController@list")->name(
                    "list",
                );
                Route::get(
                    "pending/details/{id}",
                    "VehicleTicketController@pendingDetails",
                )->name("pending.details");
                Route::get(
                    "{scope}/search",
                    "VehicleTicketController@search",
                )->name("search");
            });

            // Report
            Route::get(
                "report/login/history",
                "ReportController@loginHistory",
            )->name("report.login.history");
            Route::get(
                "report/login/ipHistory/{ip}",
                "ReportController@loginIpHistory",
            )->name("report.login.ipHistory");
            Route::get(
                "report/email/history",
                "ReportController@emailHistory",
            )->name("report.email.history");

            // Referral Management
            Route::name("referral.")
                ->prefix("referral")
                ->group(function () {
                Route::get("/settings", "ReferralController@settings")->name("settings");
                Route::post("/settings", "ReferralController@updateSettings")->name("settings.update");
                Route::get("/analytics", "ReferralController@analytics")->name("analytics");
                Route::get("/codes", "ReferralController@codes")->name("codes");
                Route::get("/codes/{id}", "ReferralController@codeDetails")->name("codes.details");
                Route::post("/codes/{id}/toggle", "ReferralController@toggleCodeStatus")->name("codes.toggle");
                Route::get("/rewards", "ReferralController@rewards")->name("rewards");
                Route::post("/rewards/{id}/confirm", "ReferralController@confirmReward")->name("rewards.confirm");
                Route::post("/rewards/{id}/reverse", "ReferralController@reverseReward")->name("rewards.reverse");
            });

            // Admin Support
            Route::get("tickets", "SupportTicketController@tickets")->name(
                "ticket",
            );
            Route::get(
                "tickets/pending",
                "SupportTicketController@pendingTicket",
            )->name("ticket.pending");
            Route::get(
                "tickets/closed",
                "SupportTicketController@closedTicket",
            )->name("ticket.closed");
            Route::get(
                "tickets/answered",
                "SupportTicketController@answeredTicket",
            )->name("ticket.answered");
            Route::get(
                "tickets/view/{id}",
                "SupportTicketController@ticketReply",
            )->name("ticket.view");
            Route::post(
                "ticket/reply/{id}",
                "SupportTicketController@ticketReplySend",
            )->name("ticket.reply");
            Route::get(
                "ticket/download/{ticket}",
                "SupportTicketController@ticketDownload",
            )->name("ticket.download");
            Route::post(
                "ticket/delete",
                "SupportTicketController@ticketDelete",
            )->name("ticket.delete");

            // Language Manager
            Route::get("/language", "LanguageController@langManage")->name(
                "language.manage",
            );
            Route::post("/language", "LanguageController@langStore")->name(
                "language.manage.store",
            );
            Route::post(
                "/language/delete/{id}",
                "LanguageController@langDel",
            )->name("language.manage.del");
            Route::post(
                "/language/update/{id}",
                "LanguageController@langUpdate",
            )->name("language.manage.update");
            Route::get(
                "/language/edit/{id}",
                "LanguageController@langEdit",
            )->name("language.key");
            Route::post(
                "/language/import",
                "LanguageController@langImport",
            )->name("language.importLang");

            Route::post(
                "language/store/key/{id}",
                "LanguageController@storeLanguageJson",
            )->name("language.store.key");
            Route::post(
                "language/delete/key/{id}",
                "LanguageController@deleteLanguageJson",
            )->name("language.delete.key");
            Route::post(
                "language/update/key/{id}",
                "LanguageController@updateLanguageJson",
            )->name("language.update.key");

            // General Setting
            Route::get(
                "general-setting",
                "GeneralSettingController@index",
            )->name("setting.index");
            Route::post(
                "general-setting",
                "GeneralSettingController@update",
            )->name("setting.update");
            Route::get("optimize", "GeneralSettingController@optimize")->name(
                "setting.optimize",
            );

            // Logo-Icon
            Route::get(
                "setting/logo-icon",
                "GeneralSettingController@logoIcon",
            )->name("setting.logo.icon");
            Route::post(
                "setting/logo-icon",
                "GeneralSettingController@logoIconUpdate",
            )->name("setting.logo.icon");

            //Custom CSS
            Route::get(
                "custom-css",
                "GeneralSettingController@customCss",
            )->name("setting.custom.css");
            Route::post(
                "custom-css",
                "GeneralSettingController@customCssSubmit",
            );

            //Cookie
            Route::get("cookie", "GeneralSettingController@cookie")->name(
                "setting.cookie",
            );
            Route::post("cookie", "GeneralSettingController@cookieSubmit");

            // Plugin
            Route::get("extensions", "ExtensionController@index")->name(
                "extensions.index",
            );
            Route::post(
                "extensions/update/{id}",
                "ExtensionController@update",
            )->name("extensions.update");
            Route::post(
                "extensions/activate",
                "ExtensionController@activate",
            )->name("extensions.activate");
            Route::post(
                "extensions/deactivate",
                "ExtensionController@deactivate",
            )->name("extensions.deactivate");

            // Email Setting
            Route::get(
                "email-template/global",
                "EmailTemplateController@emailTemplate",
            )->name("email.template.global");
            Route::post(
                "email-template/global",
                "EmailTemplateController@emailTemplateUpdate",
            )->name("email.template.global");
            Route::get(
                "email-template/setting",
                "EmailTemplateController@emailSetting",
            )->name("email.template.setting");
            Route::post(
                "email-template/setting",
                "EmailTemplateController@emailSettingUpdate",
            )->name("email.template.setting");
            Route::get(
                "email-template/index",
                "EmailTemplateController@index",
            )->name("email.template.index");
            Route::get(
                "email-template/{id}/edit",
                "EmailTemplateController@edit",
            )->name("email.template.edit");
            Route::post(
                "email-template/{id}/update",
                "EmailTemplateController@update",
            )->name("email.template.update");
            Route::post(
                "email-template/send-test-mail",
                "EmailTemplateController@sendTestMail",
            )->name("email.template.test.mail");

            // SMS Setting
            Route::get(
                "sms-template/global",
                "SmsTemplateController@smsTemplate",
            )->name("sms.template.global");
            Route::post(
                "sms-template/global",
                "SmsTemplateController@smsTemplateUpdate",
            )->name("sms.template.global");
            Route::get(
                "sms-template/setting",
                "SmsTemplateController@smsSetting",
            )->name("sms.templates.setting");
            Route::post(
                "sms-template/setting",
                "SmsTemplateController@smsSettingUpdate",
            )->name("sms.template.setting");
            Route::get(
                "sms-template/index",
                "SmsTemplateController@index",
            )->name("sms.template.index");
            Route::get(
                "sms-template/edit/{id}",
                "SmsTemplateController@edit",
            )->name("sms.template.edit");
            Route::post(
                "sms-template/update/{id}",
                "SmsTemplateController@update",
            )->name("sms.template.update");
            Route::post(
                "email-template/send-test-sms",
                "SmsTemplateController@sendTestSMS",
            )->name("sms.template.test.sms");

            // SEO
            Route::get("seo", "FrontendController@seoEdit")->name("seo");

            // Frontend
            Route::name("frontend.")
                ->prefix("frontend")
                ->group(function () {
                Route::get(
                    "templates",
                    "FrontendController@templates",
                )->name("templates");
                Route::post(
                    "templates",
                    "FrontendController@templatesActive",
                )->name("templates.active");

                Route::get(
                    "frontend-sections/{key}",
                    "FrontendController@frontendSections",
                )->name("sections");
                Route::post(
                    "frontend-content/{key}",
                    "FrontendController@frontendContent",
                )->name("sections.content");
                Route::get(
                    "frontend-element/{key}/{id?}",
                    "FrontendController@frontendElement",
                )->name("sections.element");
                Route::post("remove", "FrontendController@remove")->name(
                    "remove",
                );

                // Page Builder
                Route::get(
                    "manage-pages",
                    "PageBuilderController@managePages",
                )->name("manage.pages");
                Route::post(
                    "manage-pages",
                    "PageBuilderController@managePagesSave",
                )->name("manage.pages.save");
                Route::post(
                    "manage-pages/update",
                    "PageBuilderController@managePagesUpdate",
                )->name("manage.pages.update");
                Route::post(
                    "manage-pages/delete",
                    "PageBuilderController@managePagesDelete",
                )->name("manage.pages.delete");
                Route::get(
                    "manage-section/{id}",
                    "PageBuilderController@manageSection",
                )->name("manage.section");
                Route::post(
                    "manage-section/{id}",
                    "PageBuilderController@manageSectionUpdate",
                )->name("manage.section.update");
            });

            // Payout Management
            Route::prefix("payouts")
                ->name("payouts.")
                ->group(function () {
                Route::get("/", "PayoutController@index")->name("index");
                Route::get("create", "PayoutController@create")->name(
                    "create",
                );
                Route::post("generate", "PayoutController@generate")->name(
                    "generate",
                );
                Route::get("{payout}", "PayoutController@show")->name(
                    "show",
                );
                Route::get(
                    "{payout}/payment",
                    "PayoutController@paymentForm",
                )->name("payment");
                Route::post(
                    "{payout}/payment",
                    "PayoutController@recordPayment",
                )->name("record-payment");
                Route::patch(
                    "{payout}/cancel",
                    "PayoutController@cancel",
                )->name("cancel");
                Route::patch(
                    "{payout}/notes",
                    "PayoutController@updateNotes",
                )->name("update-notes");
                Route::get(
                    "statistics",
                    "PayoutController@statistics",
                )->name("statistics");
                Route::get("export", "PayoutController@export")->name(
                    "export",
                );
                Route::post(
                    "bulk-generate",
                    "PayoutController@bulkGenerate",
                )->name("bulk-generate");
            });
        });
    });

/*
|--------------------------------------------------------------------------
| Start Operator Area
|--------------------------------------------------------------------------
*/

Route::name("operator.")
    ->prefix("operator")
    ->group(function () {
        Route::get(
            "/login",
            "Operator\Auth\LoginController@showLoginForm",
        )->name("login");
        Route::post("/login", "Operator\Auth\LoginController@login");
        Route::get("logout", "Operator\Auth\LoginController@logout")->name(
            "logout",
        );

        // Password Reset Routes
        Route::get(
            "password/reset",
            "Operator\Auth\ForgotPasswordController@showLinkRequestForm",
        )->name("password.reset");
        Route::post(
            "password/email",
            "Operator\Auth\ForgotPasswordController@sendResetCodeEmail",
        )->name("password.email");
        Route::get(
            "password/code-verify",
            "Operator\Auth\ForgotPasswordController@codeVerify",
        )->name("password.code.verify");
        Route::post(
            "password/verify-code",
            "Operator\Auth\ForgotPasswordController@verifyCode",
        )->name("password.verify.code");
        Route::get(
            "password/reset/{token}",
            "Operator\Auth\ResetPasswordController@showResetForm",
        )->name("password.reset.form");
        Route::post(
            "password/reset",
            "Operator\Auth\ResetPasswordController@reset",
        )->name("password.update");

        Route::middleware("operator")->group(function () {
            Route::get(
                "dashboard",
                "Operator\OperatorController@dashboard",
            )->name("dashboard");
            Route::get("profile", "Operator\OperatorController@profile")->name(
                "profile",
            );
            Route::post("profile", "Operator\OperatorController@updateProfile");
            Route::get(
                "change-password",
                "Operator\OperatorController@changePassword",
            )->name("change-password");
            Route::post(
                "change-password",
                "Operator\OperatorController@updatePassword",
            );

            // Route Management
            Route::resource("routes", "Operator\RouteController")->names([
                "index" => "routes.index",
                "create" => "routes.create",
                "store" => "routes.store",
                "show" => "routes.show",
                "edit" => "routes.edit",
                "update" => "routes.update",
                "destroy" => "routes.destroy",
            ]);
            Route::patch(
                "routes/{route}/toggle-status",
                "Operator\RouteController@toggleStatus",
            )->name("routes.toggle-status");

            // Bus Management
            Route::resource("buses", "Operator\BusController")->names([
                "index" => "buses.index",
                "create" => "buses.create",
                "store" => "buses.store",
                "show" => "buses.show",
                "edit" => "buses.edit",
                "update" => "buses.update",
                "destroy" => "buses.destroy",
            ]);
            Route::patch(
                "buses/{bus}/toggle-status",
                "Operator\BusController@toggleStatus",
            )->name("buses.toggle-status");
            Route::get(
                "buses/{bus}/routes",
                "Operator\BusController@getRoutes",
            )->name("buses.routes");

            // Seat Layout Management
            Route::prefix("buses/{bus}")
                ->name("buses.")
                ->group(function () {
                Route::resource(
                    "seat-layouts",
                    "Operator\SeatLayoutController",
                )->names([
                            "index" => "seat-layouts.index",
                            "create" => "seat-layouts.create",
                            "store" => "seat-layouts.store",
                            "show" => "seat-layouts.show",
                            "edit" => "seat-layouts.edit",
                            "update" => "seat-layouts.update",
                            "destroy" => "seat-layouts.destroy",
                        ]);
                Route::patch(
                    "seat-layouts/{seatLayout}/toggle-status",
                    "Operator\SeatLayoutController@toggleStatus",
                )->name("seat-layouts.toggle-status");
                Route::post(
                    "seat-layouts/preview",
                    "Operator\SeatLayoutController@preview",
                )->name("seat-layouts.preview");

                // Cancellation Policy Management
                Route::get(
                    "cancellation-policy",
                    "Operator\BusController@showCancellationPolicy",
                )->name("cancellation-policy.show");
                Route::put(
                    "cancellation-policy",
                    "Operator\BusController@updateCancellationPolicy",
                )->name("cancellation-policy.update");
            });

            // Staff Management
            Route::resource("staff", "Operator\StaffController")->names([
                "index" => "staff.index",
                "create" => "staff.create",
                "store" => "staff.store",
                "show" => "staff.show",
                "edit" => "staff.edit",
                "update" => "staff.update",
                "destroy" => "staff.destroy",
            ]);
            Route::patch(
                "staff/{staff}/toggle-status",
                "Operator\StaffController@toggleStatus",
            )->name("staff.toggle-status");
            Route::get(
                "staff/get-by-role",
                "Operator\StaffController@getByRole",
            )->name("staff.get-by-role");

            // Crew Assignment Management
            Route::resource("crew", "Operator\CrewAssignmentController")->names(
                [
                    "index" => "crew.index",
                    "create" => "crew.create",
                    "store" => "crew.store",
                    "show" => "crew.show",
                    "edit" => "crew.edit",
                    "update" => "crew.update",
                    "destroy" => "crew.destroy",
                ],
            );
            Route::get(
                "crew/get-bus-crew",
                "Operator\CrewAssignmentController@getBusCrew",
            )->name("crew.get-bus-crew");
            Route::get(
                "crew/get-available-staff",
                "Operator\CrewAssignmentController@getAvailableStaff",
            )->name("crew.get-available-staff");
            Route::post(
                "crew/bulk-assign",
                "Operator\CrewAssignmentController@bulkAssign",
            )->name("crew.bulk-assign");

            // Attendance Management
            Route::resource(
                "attendance",
                "Operator\AttendanceController",
            )->names([
                        "index" => "attendance.index",
                        "create" => "attendance.create",
                        "store" => "attendance.store",
                        "show" => "attendance.show",
                        "edit" => "attendance.edit",
                        "update" => "attendance.update",
                        "destroy" => "attendance.destroy",
                    ]);
            Route::patch(
                "attendance/{attendance}/approve",
                "Operator\AttendanceController@approve",
            )->name("attendance.approve");
            Route::post(
                "attendance/bulk-approve",
                "Operator\AttendanceController@bulkApprove",
            )->name("attendance.bulk-approve");
            Route::post(
                "attendance/mark-today",
                "Operator\AttendanceController@markToday",
            )->name("attendance.mark-today");
            Route::get(
                "attendance/staff-summary",
                "Operator\AttendanceController@getStaffSummary",
            )->name("attendance.staff-summary");
            Route::get(
                "attendance/calendar-data",
                "Operator\AttendanceController@getCalendarData",
            )->name("attendance.calendar-data");
            Route::post(
                "attendance/update-status",
                "Operator\AttendanceController@updateStatus",
            )->name("attendance.update-status");
            Route::get(
                "attendance/export",
                "Operator\AttendanceController@export",
            )->name("attendance.export");

            // Schedule Management
            Route::resource("schedules", "Operator\ScheduleController")->names([
                "index" => "schedules.index",
                "create" => "schedules.create",
                "store" => "schedules.store",
                "show" => "schedules.show",
                "edit" => "schedules.edit",
                "update" => "schedules.update",
                "destroy" => "schedules.destroy",
            ]);
            Route::patch(
                "schedules/{schedule}/toggle-status",
                "Operator\ScheduleController@toggleStatus",
            )->name("schedules.toggle-status");
            // Route::get('schedules/get-for-date', 'Operator\ScheduleController@getSchedulesForDate')->name('schedules.get-for-date');
    
            // Operator Booking Management
            Route::resource(
                "bookings",
                "Operator\OperatorBookingController",
            )->names([
                        "index" => "bookings.index",
                        "create" => "bookings.create",
                        "store" => "bookings.store",
                        "show" => "bookings.show",
                        "edit" => "bookings.edit",
                        "update" => "bookings.update",
                        "destroy" => "bookings.destroy",
                    ]);
            Route::patch(
                "bookings/{booking}/toggle-status",
                "Operator\OperatorBookingController@toggleStatus",
            )->name("bookings.toggle-status");
            Route::get(
                "bookings/get-available-seats",
                "Operator\OperatorBookingController@getAvailableSeats",
            )->name("bookings.get-available-seats");
            Route::get(
                "bookings/get-seat-layout",
                "Operator\OperatorBookingController@getSeatLayout",
            )->name("bookings.get-seat-layout");
            Route::get(
                "bookings/get-schedules",
                "Operator\OperatorBookingController@getSchedules",
            )->name("bookings.get-schedules");

            // Revenue Management
            Route::prefix("revenue")
                ->name("revenue.")
                ->group(function () {
                Route::get(
                    "dashboard",
                    "Operator\RevenueController@dashboard",
                )->name("dashboard");
                Route::get(
                    "reports",
                    "Operator\RevenueController@reports",
                )->name("reports");
                Route::get(
                    "reports/{report}",
                    "Operator\RevenueController@showReport",
                )->name("reports.show");
                Route::post(
                    "reports/generate",
                    "Operator\RevenueController@generateReport",
                )->name("reports.generate");
                Route::get(
                    "payouts",
                    "Operator\RevenueController@payouts",
                )->name("payouts");
                Route::get(
                    "payouts/{payout}",
                    "Operator\RevenueController@showPayout",
                )->name("payouts.show");
                Route::get(
                    "export",
                    "Operator\RevenueController@export",
                )->name("export");
                Route::get(
                    "chart-data",
                    "Operator\RevenueController@chartData",
                )->name("chart-data");
                Route::get(
                    "summary",
                    "Operator\RevenueController@summary",
                )->name("summary");
            });
        });
    });

Route::get(
    "operator/schedules/get-for-date",
    "Operator\ScheduleController@getSchedulesForDate",
)->name("operator.schedules.get-for-date");
Route::get(
    "operator/buses/{bus}/routes",
    "Operator\BusController@getRoutes",
)->name("operator.buses.routes");
Route::get(
    "operator/bookings/get-seat-layout",
    "Operator\OperatorBookingController@getSeatLayout",
)->name("operator.bookings.get-seat-layout");

// Temporary routes without authentication for testing
Route::get("test-schedules", function () {
    $schedules = App\Models\BusSchedule::where("operator_id", 41)
        ->where("operator_bus_id", 1)
        ->where("is_daily", true)
        ->get();
    return response()->json($schedules);
});

/*
|--------------------------------------------------------------------------
| Start User Area
|--------------------------------------------------------------------------
*/

Route::name("user.")->group(function () {
    Route::get(
        "/print-ticket/{booking_id}",
        "TicketController@printTicket",
    )->name("print.ticket");

    Route::get("/login", "Auth\LoginController@showLoginForm")->name("login");
    Route::post("/login", "Auth\LoginController@login");
    Route::get("logout", "Auth\LoginController@logout")->name("logout");

    Route::get(
        "register",
        "Auth\RegisterController@showOtpRegistrationForm",
    )->name("register");
    Route::get(
        "register-traditional",
        "Auth\RegisterController@showRegistrationForm",
    )->name("register.traditional");
    Route::post("register", "Auth\RegisterController@register")->middleware(
        "regStatus",
    );
    Route::post("check-mail", "Auth\RegisterController@checkUser")->name(
        "checkUser",
    );

    Route::get(
        "password/reset",
        "Auth\ForgotPasswordController@showLinkRequestForm",
    )->name("password.request");
    Route::post(
        "password/email",
        "Auth\ForgotPasswordController@sendResetCodeEmail",
    )->name("password.email");
    Route::get(
        "password/code-verify",
        "Auth\ForgotPasswordController@codeVerify",
    )->name("password.code.verify");
    Route::post("password/reset", "Auth\ResetPasswordController@reset")->name(
        "password.update",
    );
    Route::get(
        "password/reset/{token}",
        "Auth\ResetPasswordController@showResetForm",
    )->name("password.reset");

    Route::get('operator/register', function () {
        $pageTitle = "Become an Operator";
        return view(
            "templates.basic.operator.auth.register",
            compact("pageTitle"),
        );
    })->name("operator.register");

});

Route::name("user.")
    ->prefix("user")
    ->group(function () {
        Route::middleware("auth")->group(function () {
            Route::get(
                "authorization",
                "AuthorizationController@authorizeForm",
            )->name("authorization");
            Route::get(
                "resend-verify",
                "AuthorizationController@sendVerifyCode",
            )->name("send.verify.code");
            Route::post(
                "verify-email",
                "AuthorizationController@emailVerification",
            )->name("verify.email");
            Route::post(
                "verify-sms",
                "AuthorizationController@smsVerification",
            )->name("verify.sms");

            Route::middleware(["checkStatus"])->group(function () {
                Route::get("dashboard", "UserController@home")->name("home");

                Route::get("profile-setting", "UserController@profile")->name(
                    "profile.setting",
                );
                Route::post("profile-setting", "UserController@submitProfile");
                Route::get(
                    "change-password",
                    "UserController@changePassword",
                )->name("change.password");
                Route::post("change-password", "UserController@submitPassword");

                //ticket
                Route::get(
                    "booked-ticket/history",
                    "UserController@ticketHistory",
                )->name("ticket.history");
                Route::get(
                    "booked-ticket/print/{id}",
                    "UserController@printTicket",
                )->name("ticket.print");
                // User-specific print route (alias to public route)
                Route::get(
                    "ticket/print/{id}",
                    "TicketController@publicPrintTicket",
                )->name("user.ticket.print");

                // Deposit //payment ticket booking
                Route::any(
                    "/ticket-booking/payment-gateway",
                    "Gateway\PaymentController@deposit",
                )->name("deposit");
                Route::post(
                    "ticket-booking/payment/insert",
                    "Gateway\PaymentController@depositInsert",
                )->name("deposit.insert");
                Route::get(
                    "ticket-booking/payment/preview",
                    "Gateway\PaymentController@depositPreview",
                )->name("deposit.preview");
                Route::get(
                    "ticket-booking/payment/confirm",
                    "Gateway\PaymentController@depositConfirm",
                )->name("deposit.confirm");
                Route::get(
                    "ticket-booking/payment/manual",
                    "Gateway\PaymentController@manualDepositConfirm",
                )->name("deposit.manual.confirm");
                Route::post(
                    "ticket-booking/payment/manual",
                    "Gateway\PaymentController@manualDepositUpdate",
                )->name("deposit.manual.update");
            });
            Route::any(
                "/book-by-razorpay",
                "Gateway\PaymentController@depositNew",
            )->name("deposit-new");
        });
    });

Route::get("/contact", "SiteController@contact")->name("contact");
Route::get("/tickets", "SiteController@ticket")->name("ticket");
// Route::get('/ticket/{id}/{slug}', 'SiteController@showSeat')->name('ticket.seats');
Route::get("/ticket/{id}/{slug}", "SiteController@selectSeat")->name(
    "ticket.seats",
);
Route::post("/get-boarding-points", "SiteController@getBoardingPoints")->name(
    "get.boarding.points",
);
// Add this route for blocking seats
Route::post("/block-seat", "SiteController@blockSeat")->name("block.seat");
Route::post("/book-seat", "SiteController@bookTicketApi")->name("book.ticket");
// Razorpay routes

Route::get("/admin/markup", [SiteController::class, "showMarkupPage"])->name("admin.markup");

// Add these routes to your web.php file
Route::post("/send-otp", [UserController::class, "sendOTP"])->name("send.otp");
Route::post("/verify-otp", [UserController::class, "verifyOtp"])->name(
    "verify.otp",
);
// Add this to your routes/web.php file

Route::post("/user/ticket/cancel", [TicketController::class, "cancelTicket"])
    ->name("user.ticket.cancel")
    ->middleware("auth");
// Route::get('/ticket/get-price', 'SiteController@getTicketPrice')->name('ticket.get-price');
// Route::post('/ticket/book/{id}', 'SiteController@bookTicket')->name('ticket.book');
Route::post("/contact", "SiteController@contactSubmit");
Route::get("/change/{lang?}", "SiteController@changeLanguage")->name("lang");
Route::get("/cookie/accept", "SiteController@cookieAccept")->name(
    "cookie.accept",
);
Route::get("/blog", "SiteController@blog")->name("blog");
Route::get("blog/{id}/{slug}", "SiteController@blogDetails")->name(
    "blog.details",
);
Route::get("policy/{id}/{slug}", "SiteController@policyDetails")->name(
    "policy.details",
);
Route::get("cookie/details", "SiteController@cookieDetails")->name(
    "cookie.details",
);
Route::get("placeholder-image/{size}", "SiteController@placeholderImage")->name(
    "placeholder.image",
);
Route::get("ticket/search", "SiteController@ticketSearch")->name("search");

// Public ticket print route (for mobile and web users)
Route::get("/users/print-ticket/{id}", "TicketController@publicPrintTicket")->name("public.ticket.print");

Route::get("/{slug}", "SiteController@pages")->name("pages");
Route::get("/", "SiteController@index")->name("home");

// Add this route for AJAX filtering
Route::get("/filter-trips", "SiteController@filterTrips")->name("filter.trips");

// Mobile Authentication Routes
Route::prefix("mobile")
    ->name("mobile.")
    ->group(function () {
        Route::get("/login", "MobileAuthController@showMobileLogin")->name(
            "login",
        );
        Route::post("/send-otp", "MobileAuthController@sendMobileOtp")->name(
            "send.otp",
        );
        Route::post(
            "/verify-otp",
            "MobileAuthController@verifyMobileOtp",
        )->name("verify.otp");
        Route::post("/logout", "MobileAuthController@logout")->name("logout");
    });

// User Dashboard Routes (Protected)
Route::middleware("auth")
    ->prefix("user")
    ->name("user.")
    ->group(function () {
        Route::get("/dashboard", "MobileAuthController@dashboard")->name(
            "dashboard",
        );
        Route::get("/home", "MobileAuthController@dashboard")->name("home"); // Alias for dashboard
        Route::get("/booking/{id}", "MobileAuthController@showBooking")->name(
            "booking.show",
        );
        Route::post(
            "/booking/{id}/cancel",
            "MobileAuthController@cancelBooking",
        )->name("booking.cancel");
        Route::post(
            "/profile/update",
            "MobileAuthController@updateProfile",
        )->name("profile.update");
        Route::get("/ticket/history", "MobileAuthController@dashboard")->name(
            "ticket.history",
        ); // Alias for dashboard
        Route::get("/profile/setting", "MobileAuthController@dashboard")->name(
            "profile.setting",
        ); // Alias for dashboard
        Route::get("/change/password", "MobileAuthController@dashboard")->name(
            "change.password",
        ); // Alias for dashboard
        Route::post("/logout", "MobileAuthController@logout")->name("logout");
    });

/*
|--------------------------------------------------------------------------
| Agent Panel Routes (PWA)
|--------------------------------------------------------------------------
*/

// Agent Authentication Routes (Public)
Route::prefix("agent")
    ->name("agent.")
    ->group(function () {
        Route::namespace("Agent")->group(function () {
            // Authentication
            Route::get("/register", "AuthController@showRegistration")->name(
                "register",
            );
            Route::post("/register", "AuthController@register")->name(
                "register.submit",
            );
            Route::get("/login", "AuthController@showLogin")->name("login");
            Route::post("/login", "AuthController@login")->name("login.submit");
            Route::post("/logout", "AuthController@logout")->name("logout");

            // PWA Manifest and Service Worker
            Route::get("/manifest.json", function () {
                return response()->file(public_path("agent-manifest.json"));
            })->name("manifest");

            Route::get("/sw.js", function () {
                $content = file_get_contents(public_path("agent-sw.js"));
                return response($content, 200, [
                    "Content-Type" => "application/javascript",
                    "Service-Worker-Allowed" => "/",
                ]);
            })->name("sw");
        });
    });

// Agent Panel Routes (Protected)
Route::middleware(["auth:agent"])
    ->prefix("agent")
    ->name("agent.")
    ->group(function () {
        Route::namespace("Agent")->group(function () {
            // Dashboard
            Route::get("/dashboard", "DashboardController@index")->name(
                "dashboard",
            );

            // Bus Search & Booking - Using existing API endpoints
            Route::get("/search", function () {
                $pageTitle = "Search Buses";
                $cities = \App\Models\City::orderBy("city_name")->get();
                return response()
                    ->view(
                        "agent.search.index",
                        compact("pageTitle", "cities"),
                    )
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            })->name("search");

            Route::get("/search/results", function (\Illuminate\Http\Request $request, ) {
                $pageTitle = "Search Results";

                // Validate search parameters
                $validatedData = $request->validate([
                    "OriginId" => "required|integer",
                    "DestinationId" => "required|integer|different:OriginId",
                    "DateOfJourney" => "required|after_or_equal:today",
                    "passengers" => "sometimes|integer|min:1|max:10",
                    "page" => "sometimes|integer|min:1",
                    "sortBy" => "sometimes|string|in:departure,price-low,price-high,duration",
                    "fleetType" => "sometimes|array",
                    "fleetType.*" => "string|in:A/c,Non-A/c,Seater,Sleeper",
                    "departure_time" => "sometimes|array",
                    "departure_time.*" => "string|in:morning,afternoon,evening,night",
                    "live_tracking" => "sometimes|boolean",
                    "min_price" => "sometimes|numeric|min:0",
                    "max_price" => "sometimes|numeric|gt:min_price",
                ]);

                // Use existing BusService to get results
                $busService = new \App\Services\BusService();
                $result = $busService->searchBuses($validatedData);

                // Store session data required for seat selection
                session()->put(
                    "search_token_id",
                    $result["SearchTokenId"] ?? null,
                );
                session()->put("user_ip", $request->ip());
                session()->put("origin_id", $validatedData["OriginId"]);
                session()->put("destination_id", $validatedData["DestinationId"]);
                session()->put("date_of_journey", $validatedData["DateOfJourney"]);
                session()->put("passengers", $validatedData["passengers"] ?? 1);

                // Debug logging
                \Log::info("Agent search session stored", [
                    "search_token_id" => $result["SearchTokenId"] ?? null,
                    "origin_id" => $validatedData["OriginId"],
                    "destination_id" => $validatedData["DestinationId"],
                    "date_of_journey" => $validatedData["DateOfJourney"],
                    "user_ip" => $request->ip(),
                    "page" => $validatedData["page"] ?? 1,
                ]);

                $fromCityData = \App\Models\City::where(
                    "city_id",
                    $validatedData["OriginId"],
                )->first();
                $toCityData = \App\Models\City::where(
                    "city_id",
                    $validatedData["DestinationId"],
                )->first();
                $dateOfJourney = $validatedData["DateOfJourney"];
                $passengers = $validatedData["passengers"] ?? 1;

                // Get trips and pagination from BusService results
                $availableBuses = $result["trips"] ?? [];
                $pagination = $result["pagination"] ?? null;

                return response()
                    ->view(
                        "agent.search.results",
                        compact(
                            "pageTitle",
                            "fromCityData",
                            "toCityData",
                            "dateOfJourney",
                            "passengers",
                            "availableBuses",
                            "pagination",
                        ),
                    )
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            })->name("search.results");

            // Get schedules for a bus
            Route::get("/search/bus/{bus}/schedules", function ($busId, \Illuminate\Http\Request $request, ) {
                $request->validate(["date" => "required|date"]);

                // For operator buses, get schedules from BusSchedule model
                if (str_starts_with($busId, "OP_")) {
                    $operatorBusId = (int) str_replace("OP_", "", $busId);
                    $schedules = \App\Models\BusSchedule::where(
                        "operator_bus_id",
                        $operatorBusId,
                    )
                        ->whereDate("departure_time", $request->date)
                        ->where("is_active", 1)
                        ->orderBy("departure_time")
                        ->get();

                    // Get bus price to include in schedule data
                    $bus = \App\Models\OperatorBus::find($operatorBusId);
                    $busPrice = $bus
                        ? $bus->published_price ?? $bus->base_price
                        : 0;

                    // Add bus price to each schedule
                    $schedules->each(function ($schedule) use ($busPrice) {
                        $schedule->bus_price = $busPrice;
                    });
                } else {
                    // For third-party buses, return empty schedules
                    $schedules = collect([]);
                }

                return response()->json([
                    "success" => true,
                    "schedules" => $schedules,
                ]);
            })->name("search.schedules");

            // Agent Booking Flow - Reusing existing SiteController methods
            Route::get(
                "/booking/seats/{id}/{slug}",
                "\App\Http\Controllers\SiteController@selectSeat",
            )->name("booking.seats");
            Route::post(
                "/booking/block-seat",
                "\App\Http\Controllers\SiteController@blockSeat",
            )->name("booking.block");
            Route::post(
                "/booking/confirm",
                "\App\Http\Controllers\SiteController@bookTicketApi",
            )->name("booking.confirm");
            Route::post(
                "/booking/boarding-points",
                "\App\Http\Controllers\SiteController@getBoardingPoints",
            )->name("booking.boarding-points");

            // My Bookings
            Route::get("/bookings", "BookingController@index")->name(
                "bookings",
            );
            Route::get("/bookings/{booking}", "BookingController@show")->name(
                "bookings.show",
            );
            Route::post(
                "/bookings/{booking}/cancel",
                "BookingController@cancel",
            )->name("bookings.cancel");
            Route::get(
                "/bookings/{booking}/print",
                "BookingController@print",
            )->name("bookings.print");

            // Earnings
            Route::get("/earnings", "EarningsController@index")->name(
                "earnings",
            );
            Route::get("/earnings/monthly", "EarningsController@monthly")->name(
                "earnings.monthly",
            );
            Route::get("/earnings/export", "EarningsController@export")->name(
                "earnings.export",
            );

            // Profile
            Route::get("/profile", "ProfileController@index")->name("profile");
            Route::post("/profile/update", "ProfileController@update")->name(
                "profile.update",
            );
            Route::post(
                "/profile/documents",
                "ProfileController@uploadDocuments",
            )->name("profile.documents");

            // API Routes for PWA
            Route::prefix("api")
                ->name("api.")
                ->group(function () {
                Route::get("/bus-search", "ApiController@busSearch")->name(
                    "bus.search",
                );
                Route::get(
                    "/schedules/{bus}",
                    "ApiController@getSchedules",
                )->name("schedules");
                Route::get(
                    "/seat-layout/{bus}/{schedule}",
                    "ApiController@getSeatLayout",
                )->name("seat.layout");
                Route::post(
                    "/booking",
                    "ApiController@createBooking",
                )->name("booking");
                Route::post("/commission-calculate", function (\Illuminate\Http\Request $request, ) {
                    $request->validate([
                        "booking_amount" => "required|numeric|min:0",
                    ]);
                    $calculator = new \App\Services\AgentCommissionCalculator();
                    $commissionConfig = $calculator->getCommissionConfig();
                    $commissionData = $calculator->calculate(
                        $request->booking_amount,
                        $commissionConfig,
                    );
                    return response()->json([
                        "success" => true,
                        "commission" => $commissionData,
                        "net_amount_paid" =>
                            $request->booking_amount -
                            $commissionData["commission_amount"],
                        "total_commission_earned" =>
                            $commissionData["commission_amount"],
                    ]);
                })->name("commission.calculate");
            });
        });
    });

// Admin Agent Management Routes
Route::middleware(["auth:admin"])
    ->prefix("admin")
    ->name("admin.")
    ->group(function () {
        Route::namespace("Admin")->group(function () {
            Route::prefix("agents")
                ->name("agents.")
                ->group(function () {
                    Route::get("/", "AgentController@index")->name("index");
                    Route::get("/create", "AgentController@create")->name(
                        "create",
                    );
                    Route::post("/store", "AgentController@store")->name(
                        "store",
                    );
                    Route::get("/{agent}", "AgentController@show")->name(
                        "show",
                    );
                    Route::get("/{agent}/edit", "AgentController@edit")->name(
                        "edit",
                    );
                    Route::put("/{agent}", "AgentController@update")->name(
                        "update",
                    );
                    Route::post(
                        "/{agent}/verify",
                        "AgentController@verify",
                    )->name("verify");
                    Route::post(
                        "/{agent}/suspend",
                        "AgentController@suspend",
                    )->name("suspend");
                    Route::get(
                        "/{agent}/bookings",
                        "AgentController@bookings",
                    )->name("bookings");
                    Route::get(
                        "/{agent}/earnings",
                        "AgentController@earnings",
                    )->name("earnings");
                });
        });
    });

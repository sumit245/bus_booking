<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ManageTripController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\TicketController;
Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::namespace('Gateway')->prefix('ipn')->name('ipn.')->group(function () {
    Route::post('razorpay', 'Razorpay\ProcessController@ipn')->name('Razorpay');
    // Deleted unnecessary payment gateway
});

// User Support Ticket
Route::prefix('ticket')->group(function () {
    Route::get('/', 'TicketController@supportTicket')->name('support_ticket');
    Route::get('/new', 'TicketController@openSupportTicket')->name('ticket.open')->middleware('auth');;
    Route::post('/create', 'TicketController@storeSupportTicket')->name('ticket.store')->middleware('auth');;
    Route::get('/view/{ticket}', 'TicketController@viewTicket')->name('ticket.view')->middleware('auth');;
    Route::post('/reply/{ticket}', 'TicketController@replyTicket')->name('ticket.reply')->middleware('auth');;
    Route::get('/download/{ticket}', 'TicketController@ticketDownload')->name('ticket.download')->middleware('auth');;
});


/*
|--------------------------------------------------------------------------
| Start Admin Area
|--------------------------------------------------------------------------
*/

Route::namespace('Admin')->prefix('admin')->name('admin.')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::get('/', 'LoginController@showLoginForm')->name('login');
        Route::post('/', 'LoginController@login')->name('login');
        Route::get('logout', 'LoginController@logout')->name('logout');
        // Admin Password Reset
        Route::get('password/reset', 'ForgotPasswordController@showLinkRequestForm')->name('password.reset');
        Route::post('password/reset', 'ForgotPasswordController@sendResetCodeEmail');
        Route::post('password/verify-code', 'ForgotPasswordController@verifyCode')->name('password.verify.code');
        Route::get('password/reset/{token}', 'ResetPasswordController@showResetForm')->name('password.reset.form');
        Route::post('password/reset/change', 'ResetPasswordController@reset')->name('password.change');
    });

    Route::middleware('admin')->group(function () {
        Route::get('dashboard', 'AdminController@dashboard')->name('dashboard');
        Route::get('profile', 'AdminController@profile')->name('profile');
        Route::post('profile', 'AdminController@profileUpdate')->name('profile.update');
        Route::get('password', 'AdminController@password')->name('password');
        Route::post('password', 'AdminController@passwordUpdate')->name('password.update');

        //Notification
        Route::get('notifications', 'AdminController@notifications')->name('notifications');
        Route::get('notification/read/{id}', 'AdminController@notificationRead')->name('notification.read');
        Route::get('notifications/read-all', 'AdminController@readAll')->name('notifications.readAll');

        //Report Bugs
        Route::get('request-report', 'AdminController@requestReport')->name('request.report');
        Route::post('request-report', 'AdminController@reportSubmit');

        Route::get('system-info', 'AdminController@systemInfo')->name('system.info');


        // Users Manager
        Route::get('users', 'ManageUsersController@allUsers')->name('users.all');
        Route::get('users/active', 'ManageUsersController@activeUsers')->name('users.active');
        Route::get('users/banned', 'ManageUsersController@bannedUsers')->name('users.banned');
        Route::get('users/email-verified', 'ManageUsersController@emailVerifiedUsers')->name('users.email.verified');
        Route::get('users/email-unverified', 'ManageUsersController@emailUnverifiedUsers')->name('users.email.unverified');
        Route::get('users/sms-unverified', 'ManageUsersController@smsUnverifiedUsers')->name('users.sms.unverified');
        Route::get('users/sms-verified', 'ManageUsersController@smsVerifiedUsers')->name('users.sms.verified');

        Route::get('users/{scope}/search', 'ManageUsersController@search')->name('users.search');
        Route::get('user/detail/{id}', 'ManageUsersController@detail')->name('users.detail');
        Route::post('user/update/{id}', 'ManageUsersController@update')->name('users.update');
        Route::post('user/add-sub-balance/{id}', 'ManageUsersController@addSubBalance')->name('users.add.sub.balance');
        Route::get('user/send-email/{id}', 'ManageUsersController@showEmailSingleForm')->name('users.email.single');
        Route::post('user/send-email/{id}', 'ManageUsersController@sendEmailSingle')->name('users.email.single');
        Route::get('user/login/{id}', 'ManageUsersController@login')->name('users.login');
        Route::get('user/transactions/{id}', 'ManageUsersController@transactions')->name('users.transactions');
        Route::get('user/deposits/{id}', 'ManageUsersController@deposits')->name('users.deposits');
        Route::get('user/deposits/via/{method}/{type?}/{userId}', 'ManageUsersController@depositViaMethod')->name('users.deposits.method');
        Route::get('user/withdrawals/{id}', 'ManageUsersController@withdrawals')->name('users.withdrawals');
        Route::get('user/withdrawals/via/{method}/{type?}/{userId}', 'ManageUsersController@withdrawalsViaMethod')->name('users.withdrawals.method');
        // Login History
        Route::get('users/login/history/{id}', 'ManageUsersController@userLoginHistory')->name('users.login.history.single');

        Route::get('users/send-email', 'ManageUsersController@showEmailAllForm')->name('users.email.all');
        Route::post('users/send-email', 'ManageUsersController@sendEmailAll')->name('users.email.send');
        Route::get('users/email-log/{id}', 'ManageUsersController@emailLog')->name('users.email.log');
        Route::get('users/email-details/{id}', 'ManageUsersController@emailDetails')->name('users.email.details');

        /*
        |--------------------------------------------------------------------------
        | Transport Manage portion
        |--------------------------------------------------------------------------
        */

        //manage counter
        Route::name('manage.')->prefix('manage')->group(function () {
            Route::get('counter', 'CounterController@counters')->name('counter');
            Route::post('counter', 'CounterController@counterStore')->name('counter.store');
            Route::post('counter/update/{id}', 'CounterController@counterUpdate')->name('counter.update');
            Route::post('counter/active-disable', 'CounterController@counterActiveDisabled')->name('counter.active.disable');
        });

        // Fleet & Trip manage
        Route::name('fleet.')->prefix('manage')->group(function () {
            //seat layouts
            Route::get('seat_layouts', 'ManageFleetController@seatLayouts')->name('seat.layouts');
            Route::post('seat_layouts', 'ManageFleetController@seatLayoutStore')->name('seat.layouts.store');
            Route::post('seat_layouts/remove', 'ManageFleetController@seatLayoutDelete')->name('seat.layouts.delete');
            Route::post('seat_layouts/{id}', 'ManageFleetController@seatLayoutUpdate')->name('seat.layouts.update');

            //fleet type
            Route::get('fleet-type', 'ManageFleetController@fleetLists')->name('type');
            Route::post('fleet-type', 'ManageFleetController@fleetTypeStore')->name('type.store');
            Route::post('fleet-type/update/{id}', 'ManageFleetController@fleetTypeUpdate')->name('type.update');
            Route::post('fleet-type/active-disable', 'ManageFleetController@fleetEnableDisabled')->name('type.active.disable');

            //vechiles
            Route::get('vehicles', 'ManageFleetController@vehicles')->name('vehicles');
            Route::post('vehicles', 'ManageFleetController@vehiclesStore')->name('vehicles.store');
            Route::post('vehicles/update/{id}', 'ManageFleetController@vehiclesUpdate')->name('vehicles.update');
            Route::post('vehicles/active-disable', 'ManageFleetController@vehiclesActiveDisabled')->name('vehicles.active.disable');
            Route::get('vehicles/search', 'ManageFleetController@vehicleSearch')->name('vehicles.search');
        });

        //manage trip
        Route::name('trip.')->prefix('manage')->group(function () {
            //route
            Route::get('route', 'ManageTripController@routeList')->name('route');
            Route::get('route/create', 'ManageTripController@routeCreate')->name('route.create');
            Route::get('route/edit/{id}', 'ManageTripController@routeEdit')->name('route.edit');
            Route::post('route', 'ManageTripController@routeStore')->name('route.store');
            Route::post('route/update/{id}', 'ManageTripController@routeUpdate')->name('route.update');
            Route::post('route/active-disable', 'ManageTripController@routeActiveDisabled')->name('route.active.disable');

            //schedule
            Route::get('schedule', 'ManageTripController@schedules')->name('schedule');
            Route::post('schedule', 'ManageTripController@schduleStore')->name('schedule.store');
            Route::post('schedule/update/{id}', 'ManageTripController@schduleUpdate')->name('schedule.update');
            Route::post('schedule/active-disable', 'ManageTripController@schduleActiveDisabled')->name('schedule.active.disable');

            //ticket price
            Route::get('ticket-price', 'VehicleTicketController@ticketPriceList')->name('ticket.price');
            Route::get('ticket-price/create', 'VehicleTicketController@ticketPriceCreate')->name('ticket.price.create');
            Route::post('ticket-price', 'VehicleTicketController@ticketPriceStore')->name('ticket.price.store');
            Route::get('route-data', 'VehicleTicketController@getRouteData')->name('ticket.get_route_data');
            Route::get('ticket-price/check_price', 'VehicleTicketController@checkTicketPrice')->name('ticket.check_price');
            Route::get('ticket-price/edit/{id}', 'VehicleTicketController@ticketPriceEdit')->name('ticket.price.edit');
            Route::post('ticket-price/update/{id}', 'VehicleTicketController@ticketPriceUpdate')->name('ticket.price.update');
            Route::post('ticket-price/delete', 'VehicleTicketController@ticketPriceDelete')->name('ticket.price.delete');

            //trip
            Route::get('trip', 'ManageTripController@trips')->name('list');
            Route::post('trip', 'ManageTripController@tripStore')->name('store');
            Route::post('trip/update/{id}', 'ManageTripController@tripUpdate')->name('update');
            Route::post('trip/active-disable', 'ManageTripController@tripActiveDisable')->name('active.disable');

            //assigned vehicle
            Route::get('assigned-vehicle', 'ManageTripController@assignedVehicleLists')->name('vehicle.assign');
            Route::post('assigned-vehicle', 'ManageTripController@assignVehicle')->name('vehicle.assign');
            Route::post('assigned-vehicle/update/{id}', 'ManageTripController@assignedVehicleUpdate')->name('assigned.vehicle.update');
            Route::post('assigned-vehicle/active-disable', 'ManageTripController@assignedVehicleActiveDisabled')->name('assigned.vehicle.active.disable');
            Route::get('markup', 'ManageTripController@markup')->name('markup');
        });

        


        // DEPOSIT SYSTEM
        Route::name('deposit.')->prefix('payment')->group(function () {
            Route::get('pending', 'DepositController@pending')->name('pending');
            Route::get('successful', 'DepositController@successful')->name('successful');
            Route::get('rejected', 'DepositController@rejected')->name('rejected');
            Route::get('all', 'DepositController@all')->name('all');
            Route::get('details/{id}', 'DepositController@details')->name('details');

            Route::post('reject', 'DepositController@reject')->name('reject');
            Route::post('approve', 'DepositController@approve')->name('approve');
            Route::get('via/{method}/{type?}', 'DepositController@depositViaMethod')->name('method');
            Route::get('/{scope}/search', 'DepositController@search')->name('search');
            Route::get('date-search/{scope}', 'DepositController@dateSearch')->name('dateSearch');
        });


        // Deposit Gateway
        Route::name('gateway.')->prefix('gateway')->group(function () {
            // Automatic Gateway
            Route::get('automatic', 'GatewayController@index')->name('automatic.index');
            Route::get('automatic/edit/{alias}', 'GatewayController@edit')->name('automatic.edit');
            Route::post('automatic/update/{code}', 'GatewayController@update')->name('automatic.update');
            Route::post('automatic/remove/{code}', 'GatewayController@remove')->name('automatic.remove');
            Route::post('automatic/activate', 'GatewayController@activate')->name('automatic.activate');
            Route::post('automatic/deactivate', 'GatewayController@deactivate')->name('automatic.deactivate');


            // Manual Methods
            Route::get('manual', 'ManualGatewayController@index')->name('manual.index');
            Route::get('manual/new', 'ManualGatewayController@create')->name('manual.create');
            Route::post('manual/new', 'ManualGatewayController@store')->name('manual.store');
            Route::get('manual/edit/{alias}', 'ManualGatewayController@edit')->name('manual.edit');
            Route::post('manual/update/{id}', 'ManualGatewayController@update')->name('manual.update');
            Route::post('manual/activate', 'ManualGatewayController@activate')->name('manual.activate');
            Route::post('manual/deactivate', 'ManualGatewayController@deactivate')->name('manual.deactivate');
        });

        // ticket booking history
        Route::name('vehicle.ticket.')->prefix('ticket')->group(function () {
            Route::get('booked', 'VehicleTicketController@booked')->name('booked');
            Route::get('pending', 'VehicleTicketController@pending')->name('pending');
            Route::get('rejected', 'VehicleTicketController@rejected')->name('rejected');
            Route::get('list', 'VehicleTicketController@list')->name('list');
            Route::get('pending/details/{id}', 'VehicleTicketController@pendingDetails')->name('pending.details');
            Route::get('{scope}/search', 'VehicleTicketController@search')->name('search');
        });

        // Report
        Route::get('report/login/history', 'ReportController@loginHistory')->name('report.login.history');
        Route::get('report/login/ipHistory/{ip}', 'ReportController@loginIpHistory')->name('report.login.ipHistory');
        Route::get('report/email/history', 'ReportController@emailHistory')->name('report.email.history');

        // Admin Support
        Route::get('tickets', 'SupportTicketController@tickets')->name('ticket');
        Route::get('tickets/pending', 'SupportTicketController@pendingTicket')->name('ticket.pending');
        Route::get('tickets/closed', 'SupportTicketController@closedTicket')->name('ticket.closed');
        Route::get('tickets/answered', 'SupportTicketController@answeredTicket')->name('ticket.answered');
        Route::get('tickets/view/{id}', 'SupportTicketController@ticketReply')->name('ticket.view');
        Route::post('ticket/reply/{id}', 'SupportTicketController@ticketReplySend')->name('ticket.reply');
        Route::get('ticket/download/{ticket}', 'SupportTicketController@ticketDownload')->name('ticket.download');
        Route::post('ticket/delete', 'SupportTicketController@ticketDelete')->name('ticket.delete');


        // Language Manager
        Route::get('/language', 'LanguageController@langManage')->name('language.manage');
        Route::post('/language', 'LanguageController@langStore')->name('language.manage.store');
        Route::post('/language/delete/{id}', 'LanguageController@langDel')->name('language.manage.del');
        Route::post('/language/update/{id}', 'LanguageController@langUpdate')->name('language.manage.update');
        Route::get('/language/edit/{id}', 'LanguageController@langEdit')->name('language.key');
        Route::post('/language/import', 'LanguageController@langImport')->name('language.importLang');



        Route::post('language/store/key/{id}', 'LanguageController@storeLanguageJson')->name('language.store.key');
        Route::post('language/delete/key/{id}', 'LanguageController@deleteLanguageJson')->name('language.delete.key');
        Route::post('language/update/key/{id}', 'LanguageController@updateLanguageJson')->name('language.update.key');



        // General Setting
        Route::get('general-setting', 'GeneralSettingController@index')->name('setting.index');
        Route::post('general-setting', 'GeneralSettingController@update')->name('setting.update');
        Route::get('optimize', 'GeneralSettingController@optimize')->name('setting.optimize');

        // Logo-Icon
        Route::get('setting/logo-icon', 'GeneralSettingController@logoIcon')->name('setting.logo.icon');
        Route::post('setting/logo-icon', 'GeneralSettingController@logoIconUpdate')->name('setting.logo.icon');

        //Custom CSS
        Route::get('custom-css', 'GeneralSettingController@customCss')->name('setting.custom.css');
        Route::post('custom-css', 'GeneralSettingController@customCssSubmit');


        //Cookie
        Route::get('cookie', 'GeneralSettingController@cookie')->name('setting.cookie');
        Route::post('cookie', 'GeneralSettingController@cookieSubmit');


        // Plugin
        Route::get('extensions', 'ExtensionController@index')->name('extensions.index');
        Route::post('extensions/update/{id}', 'ExtensionController@update')->name('extensions.update');
        Route::post('extensions/activate', 'ExtensionController@activate')->name('extensions.activate');
        Route::post('extensions/deactivate', 'ExtensionController@deactivate')->name('extensions.deactivate');



        // Email Setting
        Route::get('email-template/global', 'EmailTemplateController@emailTemplate')->name('email.template.global');
        Route::post('email-template/global', 'EmailTemplateController@emailTemplateUpdate')->name('email.template.global');
        Route::get('email-template/setting', 'EmailTemplateController@emailSetting')->name('email.template.setting');
        Route::post('email-template/setting', 'EmailTemplateController@emailSettingUpdate')->name('email.template.setting');
        Route::get('email-template/index', 'EmailTemplateController@index')->name('email.template.index');
        Route::get('email-template/{id}/edit', 'EmailTemplateController@edit')->name('email.template.edit');
        Route::post('email-template/{id}/update', 'EmailTemplateController@update')->name('email.template.update');
        Route::post('email-template/send-test-mail', 'EmailTemplateController@sendTestMail')->name('email.template.test.mail');


        // SMS Setting
        Route::get('sms-template/global', 'SmsTemplateController@smsTemplate')->name('sms.template.global');
        Route::post('sms-template/global', 'SmsTemplateController@smsTemplateUpdate')->name('sms.template.global');
        Route::get('sms-template/setting', 'SmsTemplateController@smsSetting')->name('sms.templates.setting');
        Route::post('sms-template/setting', 'SmsTemplateController@smsSettingUpdate')->name('sms.template.setting');
        Route::get('sms-template/index', 'SmsTemplateController@index')->name('sms.template.index');
        Route::get('sms-template/edit/{id}', 'SmsTemplateController@edit')->name('sms.template.edit');
        Route::post('sms-template/update/{id}', 'SmsTemplateController@update')->name('sms.template.update');
        Route::post('email-template/send-test-sms', 'SmsTemplateController@sendTestSMS')->name('sms.template.test.sms');

        // SEO
        Route::get('seo', 'FrontendController@seoEdit')->name('seo');


        // Frontend
        Route::name('frontend.')->prefix('frontend')->group(function () {

            Route::get('templates', 'FrontendController@templates')->name('templates');
            Route::post('templates', 'FrontendController@templatesActive')->name('templates.active');

            Route::get('frontend-sections/{key}', 'FrontendController@frontendSections')->name('sections');
            Route::post('frontend-content/{key}', 'FrontendController@frontendContent')->name('sections.content');
            Route::get('frontend-element/{key}/{id?}', 'FrontendController@frontendElement')->name('sections.element');
            Route::post('remove', 'FrontendController@remove')->name('remove');

            // Page Builder
            Route::get('manage-pages', 'PageBuilderController@managePages')->name('manage.pages');
            Route::post('manage-pages', 'PageBuilderController@managePagesSave')->name('manage.pages.save');
            Route::post('manage-pages/update', 'PageBuilderController@managePagesUpdate')->name('manage.pages.update');
            Route::post('manage-pages/delete', 'PageBuilderController@managePagesDelete')->name('manage.pages.delete');
            Route::get('manage-section/{id}', 'PageBuilderController@manageSection')->name('manage.section');
            Route::post('manage-section/{id}', 'PageBuilderController@manageSectionUpdate')->name('manage.section.update');
        });
    });
});




/*
|--------------------------------------------------------------------------
| Start User Area
|--------------------------------------------------------------------------
*/


Route::name('user.')->group(function () {
    Route::get('/print-ticket/{booking_id}', 'TicketController@printTicket')->name('print.ticket');



    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login', 'Auth\LoginController@login');
    Route::get('logout', 'Auth\LoginController@logout')->name('logout');

    Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::post('register', 'Auth\RegisterController@register')->middleware('regStatus');
    Route::post('check-mail', 'Auth\RegisterController@checkUser')->name('checkUser');

    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetCodeEmail')->name('password.email');
    Route::get('password/code-verify', 'Auth\ForgotPasswordController@codeVerify')->name('password.code.verify');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/verify-code', 'Auth\ForgotPasswordController@verifyCode')->name('password.verify.code');
});

Route::name('user.')->prefix('user')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('authorization', 'AuthorizationController@authorizeForm')->name('authorization');
        Route::get('resend-verify', 'AuthorizationController@sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'AuthorizationController@emailVerification')->name('verify.email');
        Route::post('verify-sms', 'AuthorizationController@smsVerification')->name('verify.sms');

        Route::middleware(['checkStatus'])->group(function () {
            Route::get('dashboard', 'UserController@home')->name('home');

            Route::get('profile-setting', 'UserController@profile')->name('profile.setting');
            Route::post('profile-setting', 'UserController@submitProfile');
            Route::get('change-password', 'UserController@changePassword')->name('change.password');
            Route::post('change-password', 'UserController@submitPassword');

            //ticket
            Route::get('booked-ticket/history', 'UserController@ticketHistory')->name('ticket.history');
            Route::get('booked-ticket/print/{id}', 'UserController@printTicket')->name('ticket.print');

            // Deposit //payment ticket booking
            Route::any('/ticket-booking/payment-gateway', 'Gateway\PaymentController@deposit')->name('deposit');
            Route::post('ticket-booking/payment/insert', 'Gateway\PaymentController@depositInsert')->name('deposit.insert');
            Route::get('ticket-booking/payment/preview', 'Gateway\PaymentController@depositPreview')->name('deposit.preview');
            Route::get('ticket-booking/payment/confirm', 'Gateway\PaymentController@depositConfirm')->name('deposit.confirm');
            Route::get('ticket-booking/payment/manual', 'Gateway\PaymentController@manualDepositConfirm')->name('deposit.manual.confirm');
            Route::post('ticket-booking/payment/manual', 'Gateway\PaymentController@manualDepositUpdate')->name('deposit.manual.update');
        });
        Route::any('/book-by-razorpay', 'Gateway\PaymentController@depositNew')->name('deposit-new');
    });
});

Route::get('/contact', 'SiteController@contact')->name('contact');
Route::get('/tickets', 'SiteController@ticket')->name('ticket');
// Route::get('/ticket/{id}/{slug}', 'SiteController@showSeat')->name('ticket.seats');
Route::get('/ticket/{id}/{slug}', 'SiteController@selectSeat')->name('ticket.seats');
Route::post('/get-boarding-points', 'SiteController@getBoardingPoints')->name('get.boarding.points');
// Add this route for blocking seats
Route::post('/block-seat', 'SiteController@blockSeat')->name('block.seat');
Route::post('/book-seat', 'SiteController@bookTicketApi')->name('book.ticket');
// Razorpay routes
Route::post('/razorpay/create-order', 'RazorpayController@createOrder')->name('razorpay.create-order');
Route::post('/razorpay/verify-payment', 'RazorpayController@verifyPayment')->name('razorpay.verify-payment');
// Add these routes to your web.php file
Route::post('/create-razorpay-order', [App\Http\Controllers\RazorpayController::class, 'createOrder'])->name('create.razorpay.order');
Route::post('/verify-razorpay-payment', [App\Http\Controllers\RazorpayController::class, 'verifyPayment'])->name('verify.razorpay.payment');

// Update your existing book.ticket route to use the verification method
Route::post('/book-ticket', [App\Http\Controllers\RazorpayController::class, 'verifyPayment'])->name('book.ticket');
Route::get('/admin/markup', [SiteController::class, 'showMarkupPage'])->name('admin.markup');
Route::put('admin/markup/update', [ManageTripController::class, 'updateMarkup'])->name('markup.update');

// Add these routes to your web.php file



Route::post('/send-otp', [OtpController::class, 'sendOtp'])->name('send.otp');
Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->name('verify.otp');
// Add this to your routes/web.php file



Route::post('/user/ticket/cancel', [TicketController::class, 'cancelTicket'])->name('user.ticket.cancel')->middleware('auth');

// Route::get('/ticket/get-price', 'SiteController@getTicketPrice')->name('ticket.get-price');
// Route::post('/ticket/book/{id}', 'SiteController@bookTicket')->name('ticket.book');
Route::post('/contact', 'SiteController@contactSubmit');
Route::get('/change/{lang?}', 'SiteController@changeLanguage')->name('lang');
Route::get('/cookie/accept', 'SiteController@cookieAccept')->name('cookie.accept');
Route::get('/blog', 'SiteController@blog')->name('blog');
Route::get('blog/{id}/{slug}', 'SiteController@blogDetails')->name('blog.details');
Route::get('policy/{id}/{slug}', 'SiteController@policyDetails')->name('policy.details');
Route::get('cookie/details', 'SiteController@cookieDetails')->name('cookie.details');
Route::get('placeholder-image/{size}', 'SiteController@placeholderImage')->name('placeholder.image');
Route::get('ticket/search', 'SiteController@ticketSearch')->name('search');
Route::get('/{slug}', 'SiteController@pages')->name('pages');
Route::get('/', 'SiteController@index')->name('home');
// Add this route for AJAX filtering
Route::get('/filter-trips', 'SiteController@filterTrips')->name('filter.trips');
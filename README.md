## Bus Booking – Comprehensive Documentation

This document provides a deep technical overview of the Bus Booking Laravel application, including a detailed, line-by-line walkthrough of the Operator Management module and a structured map of the rest of the codebase. It is intended for developers who will maintain, extend, or audit the system.

### Tech Stack

- **Framework**: Laravel (PHP)
- **Frontend**: Blade templates, Bootstrap-based admin UI
- **Database**: MySQL (via Eloquent ORM)
- **Build**: Laravel Mix (see `core/webpack.mix.js`)
- **Payments**: Razorpay **integration**
- **Notifications**: Email (SMTP/Sendgrid/Mailjet), SMS, WhatsApp (via custom API)

### High-level Directory Layout

- `core/app`: Laravel application code (controllers, models, middleware, helpers, services)
- `core/config`: Framework and application configuration
- `core/resources/views`: Blade templates (admin and frontend)
- `core/routes`: Route files (`web.php`, `api.php`, etc.)
- `core/database/migrations`: Database schema migrations
- `assets`: Static assets for admin, frontend, and error pages

---

## Operator Management Module (Deep Dive)

This module manages Operators (a specific role tied to fleet operations). It includes:

- Controller: `App\Http\Controllers\Admin\OperatorController`
- Routes: Admin routes under `admin/manage/operators`
- Model: `App\Models\Operator`
- Migrations: Creating and extending `operators` table
- Views: Admin create form and listing pages
- Helpers: `imagePath()`, `uploadImage()` for file processing

### Controller: `core/app/Http/Controllers/Admin/OperatorController.php`

Below is a line-by-line explanation of the controller.

```1:162:core/app/Http/Controllers/Admin/OperatorController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Operator;                           // Imports the Operator Eloquent model
use Illuminate\Http\Request;                        // For type-hinted Request dependency
use App\Http\Controllers\Controller;               // Base controller class

class OperatorController extends Controller
{
    /**
     * Display a listing of the operators.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $operators = Operator::all();                // Fetches all operators from DB
        $pageTitle = "Manage Operators";            // Page title for views
        return view('operators.index', compact('operators', 'pageTitle')); // Renders resources/views/operators/index.blade.php
    }

    /**
     * Show the form for creating a new operator.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $pageTitle = 'Add New Operator';            // Page title for creation form
        return view('operators.create', compact('pageTitle')); // Renders resources/views/operators/create.blade.php
    }

    /**
     * Store a newly created operator in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:operators,email',
            'mobile' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',      // expects password_confirmation
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'photo' => ['image', 'nullable', \Illuminate\Validation\Rule::dimensions()->maxWidth(400)->maxHeight(400)],
            'pan_card' => ['image', 'nullable'],
            'aadhaar_card' => ['image', 'nullable'],
            'driving_license' => ['image', 'nullable'],
            'bank_name' => 'nullable|string|max:255',             // retained in validation; migrated into bank_details JSON
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'gst_number' => 'nullable|string|max:20',
            'cancelled_cheque' => ['image', 'nullable'],
        ]);

        $operator = new Operator();
        $operator->name = $request->name;
        $operator->email = $request->email;
        $operator->mobile = $request->mobile;
        $operator->password = bcrypt($request->password);        // Hashes password for storage
        $operator->address = $request->address;
        $operator->company_name = $request->company_name;
        $operator->city = $request->city;
        $operator->state = $request->state;

        $path = imagePath()['profile']['operator']['path'];       // 'assets/images/operator/profile'
        $size = imagePath()['profile']['operator']['size'];       // '400x400'

        $fileUploads = ['photo', 'pan_card', 'aadhaar_card', 'driving_license', 'cancelled_cheque'];
        foreach ($fileUploads as $file_field) {
            if ($request->hasFile($file_field)) {
                try {
                    $filename = uploadImage($request->file($file_field), $path, $size); // Handles resize + save
                    $operator->{$file_field} = $filename;         // Dynamic assignment of attribute
                } catch (\Exception $exp) {
                    $notify[] = ['error', 'Could not upload the ' . str_replace('_', ' ', $file_field)];
                    return back()->withNotify($notify);
                }
            }
        }

        $operator->bank_details = [                              // Stored as JSON per migration
            'bank_name' => $request->bank_name,
            'account_holder_name' => $request->account_holder_name,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'gst_number' => $request->gst_number,
        ];

        $operator->save();

        $notify[] = ['success', 'Operator created successfully.'];
        return redirect()->route('admin.fleet.operators.index')->withNotify($notify);
    }

    /**
     * Display the specified operator.
     *
     * @param  \App\Models\Operator  $operator  // Implicit route-model binding (id param)
     * @return \Illuminate\View\View
     */
    public function show(Operator $operator)
    {
        return view('operators.show', compact('operator'));
    }

    /**
     * Show the form for editing the specified operator.
     *
     * @param  \App\Models\Operator  $operator
     * @return \Illuminate\View\View
     */
    public function edit(Operator $operator)
    {
        return view('operators.edit', compact('operator'));
    }

    /**
     * Update the specified operator in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Operator  $operator
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Operator $operator)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:operators,email,' . $operator->id, // ignore current
        ]);

        $operator->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $notify[] = ['success', 'Operator updated successfully.'];
        return redirect()->route('admin.fleet.operators.index')->withNotify($notify);
    }

    /**
     * Remove the specified operator from storage.
     *
     * @param  \App\Models\Operator  $operator
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Operator $operator)
    {
        $operator->delete();

        $notify[] = ['success', 'Operator deleted successfully.'];
        return redirect()->route('admin.fleet.operators.index')->withNotify($notify);
    }
}
```

Notes:

- The controller expects routes with implicit model binding for `show`, `edit`, `update`, and `destroy` actions (matching numeric `{operator}` path parameters).
- File uploads use `uploadImage()` from `helpers.php` and the `imagePath()` configuration for destination and size.
- Bank details are consolidated into `bank_details` JSON (see migrations).

### Admin Routes for Operators: `core/routes/web.php`

Operator admin routes are defined within the `admin` group (namespace `Admin`, name prefix `admin.`). The relevant entries are:

```149:154:core/routes/web.php
Route::get('manage/operators', 'OperatorController@index')->name('fleet.operators.index');
Route::get('manage/operators/create', 'OperatorController@create')->name('fleet.operators.create');
Route::post('manage/operators/store', 'OperatorController@store')->name('fleet.operators.store');
```

- These map to `index`, `create`, and `store` in `OperatorController`.
- The index and create views referred by `OperatorController` are `resources/views/operators/index.blade.php` and `resources/views/operators/create.blade.php` (present under `core/resources/views/operators/`).

### Model: `core/app/Models/Operator.php`

```1:25:core/app/Models/Operator.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $guarded = ['id'];                   // Mass-assignment protection for primary key

    protected $casts = [
        'bank_details' => 'json'                   // Casts bank_details to array
    ];

    /**
     * Get the user that owns the operator.
     */
    public function user()
    {
        return $this->belongsTo(User::class);      // FK user_id -> users.id
    }
}
```

Key points:

- `guarded` protects `id`; all other fields are mass-assignable.
- `bank_details` is automatically cast to/from JSON.
- Relation: each Operator belongs to a User (`operators.user_id`).

### Database Schema (Migrations)

1. Create table: `2024_07_23_100000_create_operators_table.php`

```15:34:core/database/migrations/2024_07_23_100000_create_operators_table.php
Schema::create('operators', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('name');
    $table->string('mobile')->unique();
    $table->string('email')->unique();
    $table->text('address')->nullable();
    $table->string('photo')->nullable();
    $table->string('pan_card')->nullable();
    $table->string('aadhaar_card')->nullable();
    $table->string('driving_license')->nullable();
    $table->string('bank_name')->nullable();
    $table->string('account_holder_name')->nullable();
    $table->string('account_number')->nullable();
    $table->string('ifsc_code')->nullable();
    $table->string('gst_number')->nullable();
    $table->string('cancelled_cheque')->nullable();
    $table->boolean('status')->default(1);
    $table->timestamps();
});
```

2. Alter table: `2025_10_09_133921_add_fields_to_operator.php`

```16:28:core/database/migrations/2025_10_09_133921_add_fields_to_operator.php
Schema::table('operators', function (Blueprint $table) {
    $table->string('password');                    // Operator-auth password
    $table->string('company_name')->nullable();
    $table->string('city')->nullable();
    $table->string('state')->nullable();
    $table->boolean('sv')->default(0);             // sms verified flag
    $table->boolean('ev')->default(0);             // email verified flag
    $table->rememberToken();                       // auth remember token
    $table->softDeletes();                         // deleted_at
    $table->json('bank_details')->nullable();      // replaces individual bank columns

    $table->dropColumn(['bank_name', 'account_holder_name', 'account_number', 'ifsc_code', 'gst_number']);
});
```

Notes:

- The controller’s `bank_details` assignment aligns with this migration.
- The presence of `password`, `remember_token`, and `softDeletes` suggests operators may log in separately (ensure guards/providers if needed).

### Admin Create View: `core/resources/views/admin/fleet/operator_create.blade.php`

This is an admin-facing form (distinct from `resources/views/operators/create.blade.php`). It posts to `admin.fleet.operators.store` and includes tabs for Basic Details, Documents, and Bank Details.

```6:26:core/resources/views/admin/fleet/operator_create.blade.php
<form action="{{ route('admin.fleet.operators.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  ...
  <ul class="nav nav-tabs" id="operatorTabs" role="tablist">
    <!-- Basic Details / Documents / Bank Details -->
  </ul>
  <div class="tab-content mt-4" id="operatorTabsContent">
    <!-- Basic Details fields: name, email, mobile, address, password, password_confirmation -->
    <!-- Documents fields: photo, pan_card, aadhaar_card, driving_license -->
    <!-- Bank Details fields: bank_name, account_holder_name, account_number, ifsc_code, gst_number, cancelled_cheque -->
  </div>
</form>
```

File upload fields use the `profilePicUpload` class and accept `.png,.jpg,.jpeg`. Image previews use `getImage('', imagePath()['operator']['size'])` for placeholder and `imagePath()['operator']['size']` for target sizing.

### Helpers Used (files and images)

- `imagePath()` in `core/app/Http/Helpers/helpers.php` defines upload destinations and sizes:

```724:737:core/app/Http/Helpers/helpers.php
$data['profile'] = [
    'user' => ['path' => 'assets/images/user/profile', 'size' => '350x300'],
    'admin' => ['path' => 'assets/admin/images/profile', 'size' => '400x400'],
    'operator' => ['path' => 'assets/images/operator/profile', 'size' => '400x400']
];
```

- `uploadImage($file, $location, $size, $old = null, $thumb = null)` saves and optionally resizes the image via Intervention Image:

```100:125:core/app/Http/Helpers/helpers.php
$filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
$image = Image::make($file);
if ($size) {
    $size = explode('x', strtolower($size));
    $image->resize($size[0], $size[1]);
}
$image->save($location . '/' . $filename);
return $filename;
```

---

## System Map (Outline)

This section enumerates core areas to guide further deep dives.

- `Admin` Controllers: user management, fleet, trips, tickets, coupons, extensions, settings, SEO, languages, notifications, reports.
- `Frontend` Controllers: `SiteController` (home, pages, blog, contact), `TicketController` (ticket flows), `RazorpayController`.
- `Gateway` Controllers: Payment lifecycle under `Gateway\PaymentController` and IPN callbacks.
- `Models`: `User`, `Trip`, `Vehicle`, `SeatLayout`, `Ticket`, `BookedTicket`, `TicketPrice`, `Counter`, `City`, `Language`, `EmailTemplate`, `SmsTemplate`, `Gateway`, etc.
- `Middleware`: AuthN/AuthZ and status checks for web and API, admin guards.
- `Helpers`: Uploads, templating, captcha, analytics, WhatsApp/SMS/email senders, bus API integration, seat parsing, date/number utilities.
- `Views`: Admin dashboard, fleet mgmt (types, vehicles, seats, markup), trips and tickets, users, content builder; Frontend templates (`templates/basic/**`).
- `Routes`: `web.php` wires all admin and user flows; notable custom routes include Razorpay order/verify and ticket booking endpoints.

---

## How to Extend Operator Module

- Add edit/update/delete routes mirroring Laravel resource conventions under `admin/manage/operators` if needed.
- If enabling operator login, configure guards/providers and password hashing/verification accordingly (fields exist in the schema).
- For additional documents, add validation rules, input names to `$fileUploads`, and columns or nested JSON as appropriate.

## Environment and Setup

1. Copy `.env.example` to `.env` and configure DB, mail, SMS/WhatsApp, and Razorpay.
2. Install dependencies and build assets:
   - `composer install`
   - `npm install && npm run dev`
3. Run migrations: `php artisan migrate`
4. Serve: `php artisan serve` (or use your local web server/XAMPP)

## Conventions

- Use `imagePath()` for all media destinations and sizes.
- Use Eloquent casts for JSON columns (`bank_details`).
- Wrap notifications in `$notify[]` and redirect with `withNotify($notify)` as established across admin flows.

## Security Notes

- Validate and sanitize all upload inputs; current validation ensures image type and dimension constraints (for photo) and image type for documents.
- Passwords are hashed with `bcrypt()` before storage.
- Ensure CSRF protection on forms (`@csrf`) and route grouping under appropriate middleware (`admin`, `auth`).

---

If you need a full, line-by-line deep dive beyond the Operator module, continue this pattern for each controller, model, migration, and view:

1. Cite source with line ranges.
2. Explain each statement (purpose, inputs, outputs, side-effects).
3. Cross-reference routes, requests, and views.

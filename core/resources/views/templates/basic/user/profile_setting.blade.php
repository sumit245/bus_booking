@extends($activeTemplate.'layouts.master')
@section('content')
<div class="padding-top padding-bottom">
    <div class="container">
        <div class="profile__edit__wrapper">
            <div class="profile__edit__form">
                <form class="register prevent-double-click" action="" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="profile__content__edit p-0">
                                <h5 class="title">{{ __($pageTitle) }}</h5>
                                <div class="row gy-3 p-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="InputFirstname" class="form-label">@lang('First Name')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="InputFirstname" name="firstname" placeholder="@lang('First Name')" value="{{$user->firstname}}" minlength="3">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lastname" class="form-label">@lang('Last Name')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="lastname" name="lastname" placeholder="@lang('Last Name')" value="{{$user->lastname}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">@lang('E-mail Address')</label>
                                            <input class="form-contorl form--control radius-0" id="email" value="{{$user->email}}" disabled>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">@lang('Mobile Number')</label>
                                            <input class="form-contorl form--control radius-0" id="phone" value="{{$user->mobile}}" disabled>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="address" class="form-label">@lang('Address')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="address" name="address" placeholder="@lang('Address')" value="{{@$user->address->address}}" required="">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="state" class="form-label">@lang('State')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="state" name="state" placeholder="@lang('state')" value="{{@$user->address->state}}" required="">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="zip" class="form-label">@lang('Zip Code')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="zip" name="zip" placeholder="@lang('Zip Code')" value="{{@$user->address->zip}}" required="">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="city" class="form-label">@lang('City')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="city" name="city" placeholder="@lang('City')" value="{{@$user->address->city}}" required="">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="zip" class="form-label">@lang('Zip Code')</label>
                                            <input type="text" class="form-contorl form--control radius-0" id="zip" name="zip" placeholder="@lang('Zip Code')" value="{{@$user->address->zip}}" required="">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                        <label class="form-label">@lang('Country')</label>
                                        <input class="form-contorl form--control radius-0" value="{{@$user->address->country}}" disabled>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn--base btn--block mt-3 h-auto">@lang('Update Profile')</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

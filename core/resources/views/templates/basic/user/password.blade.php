@extends($activeTemplate.'layouts.sage')
@section('content')
    <div class="container" style="height: calc(100vh - 35px);">
        <div class="row justify-content-center vertical-center">
            <div class="col-md-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <form action="" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">@lang('Current Password')</label>
                                <input type="password" class="form-control cmn--form--control" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="form-group mt-1">
                                <label class="form-label">@lang('Password')</label>
                                <input type="password" class="form-control cmn--form--control" name="password" required autocomplete="current-password">
                                @if($general->secure_password)
                                    <div class="input-popup">
                                      <p class="error lower">@lang('1 small letter minimum')</p>
                                      <p class="error capital">@lang('1 capital letter minimum')</p>
                                      <p class="error number">@lang('1 number minimum')</p>
                                      <p class="error special">@lang('1 special character minimum')</p>
                                      <p class="error minimum">@lang('6 character password')</p>
                                    </div>
                                @endif
                            </div>
                            <div class="form-group mt-1">
                                <label class="form-label">@lang('Confirm Password')</label>
                                <input type="password" class="form-control cmn--form--control" name="password_confirmation" required autocomplete="current-password">
                            </div>
                            <div class="asset_item_top mt-2 px-6">
                                <button type="submit" class="asset_a">@lang('Submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@if($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('style')
    <style>
        .hover-input-popup .input-popup{
            bottom:80%;
        }
    </style>
@endpush

@extends('admin.layouts.app')
@section('panel')

<main class="main-body">
    <div class="container-fluid px-0 main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h2>{{ textFormat(['_'], $title, ' ') }}</h2>
                <div class="breadcrumb-wrapper">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route("admin.dashboard") }}">{{ translate("Dashboard") }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"> {{ textFormat(['_'], $title, ' ') }} </li>
                    </ol>
                </nav>
                </div>
            </div>
        </div>
        <div class="card">
        
            <div class="card-body pt-0">
                <form action="{{ route("admin.system.setting.store") }}" method="POST" enctype="multipart/form-data" class="settingsForm">
                    @csrf
                    <div class="form-element">
                        <div class="row gy-4">
                            <div class="col-xxl-2 col-xl-3">
                                <h5 class="form-element-title">{{ translate("AI Functions") }}</h5>
                                </div>
                                <div class="col-xxl-8 col-xl-9">
                                <div class="row gy-4">
                                    <div class="col-md-12">
                                        <div class="form-inner parent">
                                            <label class="form-label"> {{ translate("Allow AI Functionality") }} </label>
                                            <div class="form-inner-switch">
                                                <label class="pointer" for="ai_functions">{{ translate("Turn on/off AI functions") }}</label>
                                                <div class="switch-wrapper mb-1 checkbox-data">
                                                    <input {{ site_settings(\App\Enums\SettingKey::AI_FUNCTIONS->value, \App\Enums\StatusEnum::FALSE->status()) == \App\Enums\StatusEnum::TRUE->status() ? 'checked' : '' }} type="checkbox" class="switch-input" id="ai_functions" name="site_settings[{{ \App\Enums\SettingKey::AI_FUNCTIONS->value }}]"/>
                                                    <label for="ai_functions" class="toggle">
                                                    <span></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <p class="form-element-note text-danger">{{ translate("Enables/disables AI functionalities") }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach( json_decode(site_settings(\App\Enums\SettingKey::AI_MODELS->value), true) as $model => $model_creds) 
                        <div class="form-element child">
                            <div class="row gy-4">
                                <div class="col-xxl-2 col-xl-3">
                                    <h5 class="form-element-title">{{ translate(textFormat(['_'], $model, ' ')) }}</h5>
                                    </div>
                                    <div class="col-xxl-8 col-xl-9">
                                    <div class="row gy-4">
                                        @foreach( $model_creds as $model_key => $model_value) 
                                            <div class="col-md-12">
                                                @if($model_key == 'status')

                                                    <div class="form-inner child">
                                                        <label class="form-label"> {{ translate(textFormat(['_'], $model, ' ')) }} </label>
                                                        <div class="form-inner-switch">
                                                            <label class="pointer" for="{{ $model.'_'.$model_key }}">{{ translate("Turn on/off ".textFormat(['_'], $model, ' ')." model") }}</label>
                                                            <div class="switch-wrapper mb-1 checkbox-data">
                                                                <input {{ $model_value == \App\Enums\StatusEnum::TRUE->status() ? 'checked' : '' }} type="checkbox" class="switch-input" id="{{ $model.'_'.$model_key }}" name="site_settings[{{ \App\Enums\SettingKey::AI_MODELS->value }}][{{ $model }}][{{ $model_key }}]"/>
                                                                <label for="{{$model.'_'.$model_key }}" class="toggle">
                                                                <span></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                @else
                                                    <div class="form-inner child">
                                                        <label for="{{ $model.'_'.$model_key }}" class="form-label"> {{ translate(textFormat(['_'], $model_key, ' ')) }} </label>
                                                        <input type="text" id="{{ $model.'_'.$model_key }}" name="site_settings[{{ \App\Enums\SettingKey::AI_MODELS->value }}][{{ $model }}][{{$model_key}}]" class="form-control" placeholder="{{ translate('Enter the ').translate(textFormat(['_'], $model, ' ').textFormat(['_'], $model_key, ' '))}}" aria-label="{{ translate('Enter the ').translate(textFormat(['_'], $model, ' ').textFormat(['_'], $model_key, ' '))}}" value="{{ $model_value }}"/>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="row">
                        <div class="col-xxl-10">
                            <div class="form-action justify-content-end">
                            <button type="reset" class="i-btn btn--danger outline btn--md"> {{ translate("Reset") }} </button>
                            <button type="submit" class="i-btn btn--primary btn--md"> {{ translate("Save") }} </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>


@endsection

@push('script-push')
    <script>
        "use strict";
        $(document).ready(function() {

            setInitialVisibility();
            $('.parent input[type="checkbox"]').change(function() {

                toggleChildren();
            });
            $('.switch-input').on('change', function() {

                updateBackgroundClass();
            });
            $('form').on('submit', function(e) {
                $('.checkbox-data').each(function() {
                    var $checkbox = $(this).find('.switch-input');
                    var $hiddenInput = $(this).find('input[type="hidden"]');

                    if ($checkbox.is(':checked')) {
                        if ($hiddenInput.length === 0) {
                            $(this).append('<input type="hidden" name="' + $checkbox.attr('name') + '" value="{{ \App\Enums\StatusEnum::TRUE->status() }}">');
                        } else {
                            $hiddenInput.val('{{ \App\Enums\StatusEnum::TRUE->status() }}');
                        }
                    } else {
                        if ($hiddenInput.length === 0) {
                            $(this).append('<input type="hidden" name="' + $checkbox.attr('name') + '" value="{{ \App\Enums\StatusEnum::FALSE->status() }}">');
                        } else {
                            $hiddenInput.val('{{ \App\Enums\StatusEnum::FALSE->status() }}');
                        }
                    }
                });
            });
        });
    </script>
@endpush

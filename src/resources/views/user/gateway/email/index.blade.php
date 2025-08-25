@push("style-include")
  <link rel="stylesheet" href="{{ asset('assets/theme/global/css/select2.min.css')}}">
@endpush
@extends('user.gateway.index')
@section('tab-content')
@php
    $jsonArray   = json_encode($credentials);
    $plan_access = $allowedAccess->type == App\Enums\StatusENum::FALSE->status();
@endphp
<div class="tab-pane active fade show" id="{{url()->current()}}" role="tabpanel">
    <div class="table-filter mb-4">
        <form action="{{route(Route::currentRouteName())}}" class="filter-form">
           
            <div class="row g-3">
                <div class="col-xxl-3 col-xl-4 col-lg-4">
                    <div class="filter-search">
                        <input type="search" value="{{request()->search}}" name="search" class="form-control" id="filter-search" placeholder="{{ translate("Search by name") }}" />
                        <span><i class="ri-search-line"></i></span>
                    </div>
                </div>
                <div class="col-xxl-5 col-xl-6 col-lg-7 offset-xxl-4 offset-xl-2">
                    <div class="filter-action">

                        <div class="input-group">
                            <input type="text" class="form-control" id="datePicker" name="date" value="{{request()->input('date')}}"  placeholder="{{translate('Filter by date')}}"  aria-describedby="filterByDate">
                            <span class="input-group-text" id="filterByDate">
                                <i class="ri-calendar-2-line"></i>
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" class="filter-action-btn ">
                                <i class="ri-menu-search-line"></i> {{ translate("Filter") }}
                            </button>
                            <a class="filter-action-btn bg-danger text-white" href="{{route(Route::currentRouteName())}}">
                                <i class="ri-refresh-line"></i> {{ translate("Reset") }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <h4 class="card-title">{{$title}}</h4>
            </div>
            <div class="card-header-right d-flex align-content-center flex-wrap flex-sm-nowrap gap-2">
                @if($plan_access)
                    <button class="i-btn btn--primary btn--sm add-email-gateway space-nowrap" type="button" data-bs-toggle="modal" data-bs-target="#addEmailGateway">
                        <i class="ri-add-fill fs-16"></i> {{ translate("Add Gateway") }}
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body px-0 pt-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">{{ translate("Gateway Name") }}</th>
                            <th scope="col">{{ translate("Gateway Type") }}</th>
                            @if($plan_access)<th scope="col">{{ translate("Default") }}</th>@endif
                            <th scope="col">{{ translate("Status") }}</th>
                            @if($plan_access)<th scope="col">{{ translate("Option") }}</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gateways as $gateway)
                            @php
                                $driver_info = json_encode($gateway->meta_data);
                            @endphp
                            <tr class="@if($loop->even)@endif">

                                <td data-label="{{ translate('Gateway Name')}}"><span class="text-dark">{{ucfirst($gateway->name)}}</span></td>
                                <td data-label="{{ translate('Gateway Type')}}"><span class="text-dark">{{preg_replace('/[[:digit:]]/','', setInputLabel($gateway->type))}}</span></td>
                                @if($plan_access)
                                <td data-label="{{ translate('Default') }}">
                                    @if($gateway->is_default)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="i-badge dot success-soft pill">{{ translate("Default") }}</span>
                                        </div>
                                    @else
                                        <div class="switch-wrapper checkbox-data">
                                            <input {{ $gateway->is_default ? 'checked' : '' }}
                                                    type="checkbox"
                                                    class="switch-input statusUpdate"
                                                    data-id="{{ $gateway->id }}"
                                                    data-column="is_default"
                                                    data-value="{{ $gateway->is_default ? 0 : 1 }}"
                                                    data-route="{{route('admin.gateway.email.status.update')}}"
                                                    id="{{ 'default_'.$gateway->id }}"
                                                    name="is_default"/>
                                            <label for="{{ 'default_'.$gateway->id }}" class="toggle">
                                                <span></span>
                                            </label>
                                        </div>
                                    @endif
                                </td>
                                @endif
                                <td data-label="{{ translate('Status')}}">
                                    @if($plan_access)
                                    <div class="switch-wrapper checkbox-data">
                                        <input {{ empty($gateway->getRawOriginal('status')) || $gateway->getRawOriginal('status') == '' 
                                                    ? '' 
                                                    : ($gateway->getRawOriginal('status') == \App\Enums\Common\Status::ACTIVE->value ? 'checked' : '') }}
                                                type="checkbox"
                                                class="switch-input statusUpdate"
                                                data-id="{{ $gateway->id }}"
                                                data-column="status"
                                                data-value="{{ empty($gateway->getRawOriginal('status')) || $gateway->getRawOriginal('status') == '' 
                                                            ? \App\Enums\Common\Status::ACTIVE->value 
                                                            : ($gateway->getRawOriginal('status') == \App\Enums\Common\Status::ACTIVE->value 
                                                                ? \App\Enums\Common\Status::INACTIVE->value 
                                                                : \App\Enums\Common\Status::ACTIVE->value) }}"
                                                data-route="{{ route('user.gateway.email.status.update') }}"
                                                id="{{ 'status_' . $gateway->id }}"
                                                name="is_default"/>
                                        <label for="{{ 'status_'.$gateway->id }}" class="toggle">
                                            <span></span>
                                        </label>
                                    </div>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            {{ $gateway->status->badge() }}
                                        </div>
                                    @endif
                                </td>
                                @if($plan_access)
                                <td data-label={{ translate('Option')}}>
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="icon-btn btn-ghost btn-sm success-soft circle update-email-gateway"
                                                type="button"
                                                data-url="{{ route('user.gateway.email.update', ['id' => $gateway->id])}}"
                                                    data-gateway_type="{{$gateway?->type}}"
                                                    data-gateway_name="{{$gateway?->name}}"
                                                    data-gateway_address="{{$gateway?->address}}"
                                                    data-bulk_contact_limit="{{$gateway?->bulk_contact_limit}}"
                                                    data-per_message_min_delay="{{$gateway?->per_message_min_delay}}"
                                                    data-per_message_max_delay="{{$gateway?->per_message_max_delay}}"
                                                    data-delay_after_count="{{$gateway?->delay_after_count}}"
                                                    data-reset_after_count="{{$gateway?->reset_after_count}}"
                                                    data-delay_after_duration="{{$gateway?->delay_after_duration}}"
                                                    data-gateway_driver_information="{{$driver_info}}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateEmailGateway">
                                            <i class="ri-edit-line"></i>
                                            <span class="tooltiptext"> {{ translate("Update") }} </span>
                                        </button>
                                        <button class="icon-btn btn-ghost btn-sm info-soft circle text-info quick-view"
                                                type="button"
                                                data-meta_data="{{$driver_info}}"
                                                data-uid="{{$gateway->uid}}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#quick_view">
                                                <i class="ri-information-line"></i>
                                            <span class="tooltiptext"> {{ translate("Quick View") }} </span>
                                        </button>
                                        <button class="icon-btn btn-ghost btn-sm danger-soft circle text-danger delete-email-gateway"
                                                type="button"
                                                data-gateway-id="{{$gateway->id}}"
                                                data-url="{{route('user.gateway.email.delete', ['id' => $gateway->id ])}}" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteEmailGateway">
                                            <i class="ri-delete-bin-line"></i>
                                            <span class="tooltiptext"> {{ translate("Delete Email Gateway") }} </span>
                                        </button>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ translate('No Data Found')}}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('user.partials.pagination', ['paginator' => $gateways])
        </div>
    </div>
</div>

@endsection

@section('modal')
@if($plan_access)
<div class="modal fade" id="addEmailGateway" tabindex="-1" aria-labelledby="addEmailGateway" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered ">
        <div class="modal-content">
            <form action="{{route('user.gateway.email.store')}}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ translate("Add Email Gateway") }} </h5>
                    <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                        <i class="ri-close-large-line"></i>
                    </button>
                </div>
                <div class="modal-body modal-lg-custom-height">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="name" class="form-label"> {{ translate('Gateway Name')}} </label>
                                <input type="text" id="name" name="name" placeholder="{{ translate('Enter Gateway Name')}}" class="form-control" aria-label="name"/>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="address" class="form-label"> {{ translate('Gateway Email Address')}} </label>
                                <input type="email" id="address" name="address" placeholder="{{ translate('Enter Gateway Name')}}" class="form-control" aria-label="address"/>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="per_message_min_delay" class="form-label">{{ translate('Per Message Minimum Delay (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="per_message_min_delay" name="per_message_min_delay" placeholder="{{ translate('e.g., 0.5 seconds minimum delay per message') }}" class="form-control" aria-label="Per Message Minimum Delay" value="{{ old('per_message_min_delay', $gateway->per_message_min_delay ?? 0) }}" />
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="per_message_max_delay" class="form-label">{{ translate('Per Message Maximum Delay (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="per_message_max_delay" name="per_message_max_delay" placeholder="{{ translate('e.g., 0.5 seconds maximum delay per message') }}" class="form-control" aria-label="Per Message Maximum Delay" value="{{ old('per_message_max_delay', $gateway->per_message_max_delay ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="delay_after_count" class="form-label">{{ translate('Delay After Count') }}</label>
                                <input type="number" min="0" step="1" id="delay_after_count" name="delay_after_count" placeholder="{{ translate('e.g., pause after 50 messages') }}" class="form-control" aria-label="Delay After Count" value="{{ old('delay_after_count', $gateway->delay_after_count ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="delay_after_duration" class="form-label">{{ translate('Delay After Duration (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="delay_after_duration" name="delay_after_duration" placeholder="{{ translate('e.g., pause for 5 seconds') }}" class="form-control" aria-label="Delay After Duration" value="{{ old('delay_after_duration', $gateway->delay_after_duration ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-inner">
                                <label for="reset_after_count" class="form-label">{{ translate('Reset After Count') }}</label>
                                <input type="number" min="0" step="1" id="reset_after_count" name="reset_after_count" placeholder="{{ translate('e.g., reset after 200 messages') }}" class="form-control" aria-label="Reset After Count" value="{{ old('reset_after_count', $gateway->reset_after_count ?? 0) }}" />
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-inner">
                                <label for="country-code" class="form-label">{{ translate("Gateway Type") }}</label>
                                
                                <select data-placeholder="{{translate('Select a gateway type')}}" class="form-select select2-search gateway_type_add_modal" data-show="5" id="add_gateway_type" name="type">
                                    <option value=""></option>
                                    @foreach($credentials as $credential_key => $credential)
                                    @if(array_key_exists('allowed_gateways', (array)$user->runningSubscription()->currentPlan()->email) && $user->runningSubscription()->currentPlan()->email->allowed_gateways != null)
                                        @foreach($user->runningSubscription()->currentPlan()->email->allowed_gateways as $key => $value)

                                            @php 
                                                $remaining = isset($gatewayCount[$key]) ? $value - $gatewayCount[$key] : $value; 
                                            @endphp

                                            @if(preg_replace('/_/','',$key) == strtolower($credential_key) && $remaining > 0)

                                                <option value="{{strToLower($credential_key)}}">{{strtoupper($credential_key)}} ({{translate("Remaining Gateway: ").$remaining  }})</option>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row new-data-add-modal"></div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal"> {{ translate("Close") }} </button>
                    <button type="submit" class="i-btn btn--primary btn--md"> {{ translate("Save") }} </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updateEmailGateway" tabindex="-1" aria-labelledby="updateEmailGateway" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered ">
        <div class="modal-content">
            <form id="updateEmailGatewayForm" method="post">
                @csrf
                <input type="hidden" name="_method" value="PATCH">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ translate("Update Email Gateway") }} </h5>
                    <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                        <i class="ri-close-large-line"></i>
                    </button>
                </div>
                <div class="modal-body modal-lg-custom-height">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="name" class="form-label"> {{ translate('Gateway Name')}} </label>
                                <input type="text" id="name" name="name" placeholder="{{ translate('Enter Gateway Name')}}" class="form-control" aria-label="name"/>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="address" class="form-label"> {{ translate('Gateway Email Address')}} </label>
                                <input type="email" id="address" name="address" placeholder="{{ translate('Enter Gateway Name')}}" class="form-control" aria-label="address"/>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="per_message_min_delay" class="form-label">{{ translate('Per Message Minimum Delay (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="per_message_min_delay" name="per_message_min_delay" placeholder="{{ translate('e.g., 0.5 seconds minimum delay per message') }}" class="form-control" aria-label="Per Message Minimum Delay" value="{{ old('per_message_min_delay', $gateway->per_message_min_delay ?? 0) }}" />
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="per_message_max_delay" class="form-label">{{ translate('Per Message Maximum Delay (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="per_message_max_delay" name="per_message_max_delay" placeholder="{{ translate('e.g., 0.5 seconds maximum delay per message') }}" class="form-control" aria-label="Per Message Maximum Delay" value="{{ old('per_message_max_delay', $gateway->per_message_max_delay ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="delay_after_count" class="form-label">{{ translate('Delay After Count') }}</label>
                                <input type="number" min="0" step="1" id="delay_after_count" name="delay_after_count" placeholder="{{ translate('e.g., pause after 50 messages') }}" class="form-control" aria-label="Delay After Count" value="{{ old('delay_after_count', $gateway->delay_after_count ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-inner">
                                <label for="delay_after_duration" class="form-label">{{ translate('Delay After Duration (Seconds)') }}</label>
                                <input type="number" min="0" step="0.1" id="delay_after_duration" name="delay_after_duration" placeholder="{{ translate('e.g., pause for 5 seconds') }}" class="form-control" aria-label="Delay After Duration" value="{{ old('delay_after_duration', $gateway->delay_after_duration ?? 0) }}" />
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-inner">
                                <label for="reset_after_count" class="form-label">{{ translate('Reset After Count') }}</label>
                                <input type="number" min="0" step="1" id="reset_after_count" name="reset_after_count" placeholder="{{ translate('e.g., reset after 200 messages') }}" class="form-control" aria-label="Reset After Count" value="{{ old('reset_after_count', $gateway->reset_after_count ?? 0) }}" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-inner">
                                <label for="country-code" class="form-label">{{ translate("Gateway Type") }}</label>
                                <select data-placeholder="{{translate('Select a gateway type')}}" class="form-select select-gateway-type gateway_type_update_modal" data-show="5" id="gateway_type_edit" name="type">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="row new-data-edit-modal"></div>
                        </div>
                        <div class="col-12 mt-1">

                            <div class="row oldData"></div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal"> {{ translate("Close") }} </button>
                    <button type="submit" class="i-btn btn--primary btn--md"> {{ translate("Save") }} </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quick_view" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate("Email Gateway Information") }}</h5>
                <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="modal-body">
                <ul class="information-list"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal">{{ translate("Close") }}</button>
                <button type="button" class="i-btn btn--primary btn--md">{{ translate("Save") }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade actionModal" id="deleteEmailGateway" tabindex="-1" aria-labelledby="deleteEmailGateway" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
        <div class="modal-header text-start">
            <span class="action-icon danger">
            <i class="bi bi-exclamation-circle"></i>
            </span>
        </div>
        <form method="POST" id="deleteEmailGateway">
            @csrf
            <div class="modal-body">
                <input type="hidden" name="_method" value="DELETE">
                <div class="action-message">
                    <h5>{{ translate("Are you sure to delete this gateway?") }}</h5>
                    <p>{{ translate("By clicking on 'Delete', you will permanently remove the gateway from the application") }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="i-btn btn--dark outline btn--lg" data-bs-dismiss="modal"> {{ translate("Cancel") }} </button>
                <button type="submit" class="i-btn btn--danger btn--lg" data-bs-dismiss="modal"> {{ translate("Delete") }} </button>
            </div>
        </form>
        </div>
    </div>
</div>
@endif
@endsection

@push("script-include")
  <script src="{{asset('assets/theme/global/js/select2.min.js')}}"></script>
@endpush

@push('script-push')
<script>
	(function($){
		"use strict";

        select2_search($('.select2-search').data('placeholder'));
        flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            mode: "range",
        });

        $(document).ready(function() {

            var oldType = '';
            var oldInfo = [];
            var bulkContactLimit = 1;
            $('.add-email-gateway').on('click', function() {

                const modal = $('#addEmailGateway');
                modal.modal('show');
            });

            $('.update-email-gateway').on('click', function() {

                $('.new-data-edit-modal').empty();
                $('.oldData').empty();
                $('.select-gateway-type').empty();
                $('.active').attr("selected",false);
                $('.inactive').attr("selected",false);
                $('.gatewayType').attr("selected",false);

                var modal = $('#updateEmailGateway');
                modal.find('form[id=updateEmailGatewayForm]').attr('action', $(this).data('url'));
                modal.find('input[name=name]').val($(this).data('gateway_name'));
                modal.find('input[name=address]').val($(this).data('gateway_address'));

                bulkContactLimit = $(this).data('bulk_contact_limit');
                modal.find('input[name=per_message_min_delay]').val($(this).data('per_message_min_delay'));
                modal.find('input[name=per_message_max_delay]').val($(this).data('per_message_max_delay'));
                modal.find('input[name=delay_after_count]').val($(this).data('delay_after_count'));
                modal.find('input[name=reset_after_count]').val($(this).data('reset_after_count'));

                var previousType = $(this).data('gateway_type');

                $(this).data('gateway_status') == 1 ? $('.active').attr("selected",true) : $('.inactive').attr("selected",true);
                oldType = $(this).data('gateway_type');

                    var user = <?php echo json_encode(@$user->runningSubscription()->currentPlan()->email->allowed_gateways) ?>;
                    var data = Object.keys(<?php echo $jsonArray ?>);
                    var creds = <?php echo $jsonArray ?>;
                    $.each(data, function(key, value) {

                        $.each(user, function(u_key, u_value) {

                            if(u_key == value) {

                                if(previousType == value) {

                                    var previous = $('<option class="text-uppercase gatewayType" value="'+ value +'">'+ previousType +'</option>');
                                }
                                if(previousType != value) {

                                    var option = $('<option class="text-uppercase gatewayType" value="'+ value +'">'+ value +'</option>');
                                }
                                $('.select-gateway-type').append(previous, option);
                                if(oldType == value){
                                    if (creds[value].native_bulk_support == true) {
                                        var bulkLimitBlock = `
                                            <div class="col-12 mb-4" id="bulk_contact_limit_wrapper">
                                                <div class="form-inner">
                                                    <label for="bulk_contact_limit" class="form-label">Bulk Contact Limit</label>
                                                    <input value="1" type="number" min="1" id="bulk_contact_limit" name="bulk_contact_limit" placeholder="Enter Bulk Contact Limit" class="form-control" aria-label="bulk_contact_limit"/>
                                                </div>
                                            </div>
                                        `;
                                        
                                        $('.oldData').append(bulkLimitBlock);
                                            
                                    }
                                    $('.gatewayType').attr("selected",true)
                                }
                            }
                        });
                    });
                    modal.find('input[name=bulk_contact_limit]').val(bulkContactLimit);
                    oldInfo = $(this).data('gateway_driver_information');
                    
                    $.each(oldInfo, function(key, value) {
                        
                        if(key != 'encryption') {
                           
                            var filterkey = key.replace("_", " ");
                            var div   = $('<div class="mb-4 col-lg-6"></div>');
                            var label = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                            var input = $('<input type="text" class="form-control" id="' + key + '" value="' + value + '" name="meta_data[' + key + ']" placeholder="Enter ' + filterkey + '" required>');
                            div.append(label, input);
                            $('.oldData').append(div);
                        }
                        else{
                            $.each(creds[oldType], function(k, v) {

                                $.each(v, function(cred_key, cred_value) {

                                    if(cred_key == key) {
                                    
                                        var filterkey = key.replace("_", " ");
                                        var div   = $('<div class="mb-4 col-lg-6"></div>');
                                        var label  = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                                        var select = $('<select class="form-select" name="meta_data[' + key + ']" id="'+key+'"></select>')
                                        $.each(cred_value, function(name, method) {

                                            var option = $('<option class="encryptionType" value="'+method+'">'+name+'</option>');

                                            select.append(option);
                                            if(method == value){
                                                option.attr("selected",true)
                                            }

                                        });


                                        div.append(label,select);
                                        $('.oldData').append(div);

                                    }
                                });
                            });

                        }
                    });


                modal.modal('show');

            });

            $('.gateway_type_add_modal').on('change', function(){

                $('.new-data-add-modal').empty();
                var data                = <?php echo $jsonArray ?>[this.value].meta_data;
                var native_bulk_support = <?php echo $jsonArray ?>[this.value].native_bulk_support;
                var newType = this.value;
                if (native_bulk_support == true) {
                    var bulkLimitBlock = `
                        <div class="col-12 mb-4" id="bulk_contact_limit_wrapper">
                            <div class="form-inner">
                                <label for="bulk_contact_limit" class="form-label">Bulk Contact Limit</label>
                                <input value="1" type="number" min="1" id="bulk_contact_limit" name="bulk_contact_limit" placeholder="Enter Bulk Contact Limit" class="form-control" aria-label="bulk_contact_limit"/>
                            </div>
                        </div>
                    `;
                    
                    $('.new-data-add-modal').append(bulkLimitBlock); 
                }
                $.each(data, function(key, v) {
                    $('.oldData').empty();
                    var filterkey = key.replace("_", " ");
                    var div   = $('<div class="mb-4 col-lg-6"></div>');
                    if(key != 'encryption'){
                        var label = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                        var input = $('<input type="text" class="form-control" id="' + key + '" name="meta_data[' + key + ']" placeholder="Enter ' + filterkey + '" required>');
                        div.append(label, input);
                        $('.new-data-add-modal').append(div);
                    }
                    else{

                        var label  = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                        var select = $('<select class="form-select" name="meta_data[' + key + ']" id="'+key+'"></select>')
                        $.each(v, function(name, method) {
                            var option = $('<option value="'+method+'">'+name+'</option>')
                            select.append(option);
                        });
                        div.append(label,select);
                        $('.new-data-add-modal').append(div);
                    }
                });
            });
            $('.gateway_type_update_modal').on('change', function(){

                $('.new-data-edit-modal').empty();
                $('.oldData').empty();
                var data                = <?php echo $jsonArray ?>[this.value].meta_data;
                var native_bulk_support = <?php echo $jsonArray ?>[this.value].native_bulk_support;
                var newType = this.value;

                if(newType != oldType){

                    if (native_bulk_support == true) {
                        var bulkLimitBlock = `
                            <div class="col-12 mb-4" id="bulk_contact_limit_wrapper">
                                <div class="form-inner">
                                    <label for="bulk_contact_limit" class="form-label">Bulk Contact Limit</label>
                                    <input value="1" type="number" min="1" id="bulk_contact_limit" name="bulk_contact_limit" placeholder="Enter Bulk Contact Limit" class="form-control" aria-label="bulk_contact_limit"/>
                                </div>
                            </div>
                        `;
                        $('.new-data-edit-modal').append(bulkLimitBlock); 
                    }
                    $.each(data, function(key, v) {
                        $('.oldData').empty();
                        var filterkey = key.replace("_", " ");
                        var div   = $('<div class="mb-4 col-lg-6"></div>');
                        if(key != 'encryption'){
                            var label = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                            var input = $('<input type="text" class="form-control" id="' + key + '" name="meta_data[' + key + ']" placeholder="Enter ' + filterkey + '" required>');
                            div.append(label, input);
                            $('.new-data-edit-modal').append(div);
                        }
                        else{

                            var label  = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                            var select = $('<select class="form-select" name="meta_data[' + key + ']" id="'+key+'"></select>')
                            $.each(v, function(name, method) {
                                var option = $('<option value="'+method+'">'+name+'</option>')
                                select.append(option);
                            });
                            div.append(label,select);
                            $('.new-data-edit-modal').append(div);
                        }
                    });
                }
                else{

                    if (native_bulk_support == true) {
                        var bulkLimitBlock = `
                            <div class="col-12 mb-4" id="bulk_contact_limit_wrapper">
                                <div class="form-inner">
                                    <label for="bulk_contact_limit" class="form-label">Bulk Contact Limit</label>
                                    <input value="1" type="number" min="1" id="bulk_contact_limit" name="bulk_contact_limit" placeholder="Enter Bulk Contact Limit" class="form-control" aria-label="bulk_contact_limit"/>
                                </div>
                            </div>
                        `;
                        
                        $('.oldData').append(bulkLimitBlock); 
                    }
                    $('#updateEmailGateway').find('input[name=bulk_contact_limit]').val(bulkContactLimit);
                    $.each(oldInfo, function(key, value) {
                        var filterkey = key.replace("_", " ");
                        var div   = $('<div class="mb-4 col-lg-6"></div>');
                        var label = $('<label for="' + key + '" class="form-label text-capitalize">' + filterkey + '<sup class="text--danger">*</sup></label>');
                        var input = $('<input type="text" class="form-control" id="' + key + '" value="' + value + '" name="meta_data[' + key + ']" placeholder="Enter ' + filterkey + '" required>');
                        div.append(label, input);
                        $('.oldData').append(div);
                    });
                }
            });

            $('.quick-view').on('click', function() {
                const modal = $('#quick_view');
                const modalBodyInformation = modal.find('.modal-body .information-list');
                modalBodyInformation.empty();

                var driver = $(this).data('meta_data');
                var uid = $(this).data('uid');

                $.each(driver, function(key, value) {
                    const listItem = $('<li>');
                    const paramKeySpan = $('<span>').text(textFormat(['_'], key, ' '));
                    const arrowIcon = $('<i>').addClass('bi bi-arrow-right');
                    const paramValueSpan = $('<span>').addClass('text-break text-muted').text(value);

                    listItem.append(paramKeySpan).append(arrowIcon).append(paramValueSpan);
                    modalBodyInformation.append(listItem);
                });
                if(uid) {
                    var title = 'gateway_identifier';
                    const listItem = $('<li>');
                    const paramKeySpan = $('<span>').text(textFormat(['_'], title, ' '));
                    const arrowIcon = $('<i>').addClass('bi bi-arrow-right');
                    const paramValueSpan = $(`<span title='${title}'>`).addClass('text-break text-muted').text(uid);

                    listItem.append(paramKeySpan).append(arrowIcon).append(paramValueSpan);
                    modalBodyInformation.append(listItem);
                }
                modal.modal('show');
            });

            $('.delete-email-gateway').on('click', function() {

                var modal = $('#deleteEmailGateway');
                modal.find('form[id=deleteEmailGateway]').attr('action', $(this).data('url'));
                modal.modal('show');
            });
        });
	})(jQuery);
</script>
@endpush

@extends('admin.gateway.index')
@section('tab-content')
    <div class="tab-pane active fade show" id="{{ url()->current() }}" role="tabpanel">
        <div class="table-filter mb-4">
            <form action="{{ route(Route::currentRouteName()) }}" class="filter-form">
                <div class="row g-3">
                    <div class="col-xxl-3 col-xl-4 col-lg-4">
                        <div class="filter-search">
                            <input type="search" value="{{ request()->search }}" name="search" class="form-control"
                                id="filter-search" placeholder="{{ translate('Search by name') }}" />
                            <span><i class="ri-search-line"></i></span>
                        </div>
                    </div>
                    <div class="col-xxl-5 col-xl-6 col-lg-7 offset-xxl-4 offset-xl-2">
                        <div class="filter-action">
                            <div class="input-group">
                                <input type="text" class="form-control" id="datePicker" name="date"
                                    value="{{ request()->input('date') }}" placeholder="{{ translate('Filter by date') }}"
                                    aria-describedby="filterByDate">
                                <span class="input-group-text" id="filterByDate">
                                    <i class="ri-calendar-2-line"></i>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="filter-action-btn ">
                                    <i class="ri-menu-search-line"></i> {{ translate('Filter') }}
                                </button>
                                <a class="filter-action-btn bg-danger text-white"
                                    href="{{ route(Route::currentRouteName()) }}">
                                    <i class="ri-refresh-line"></i> {{ translate('Reset') }}
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
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-header-right">
                    <button class="i-btn btn--primary btn--sm" type="button" data-bs-toggle="modal" data-bs-target="#addEvolutionGateway">
                        <i class="ri-add-fill fs-16"></i> {{ translate('Add Evolution Gateway') }}
                    </button>
                </div>
            </div>

            <div class="card-body px-0 pt-0">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ translate('Gateway Name') }}</th>
                                <th scope="col">{{ translate('Instance') }}</th>
                                <th scope="col">{{ translate('Server') }}</th>
                                <th scope="col">{{ translate('Delay Settings') }}</th>
                                <th scope="col">{{ translate('Status') }}</th>
                                <th scope="col">{{ translate('Option') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gateways as $item)
                                <tbody>
                                    <tr>
                                        <td data-label="{{ translate('Gateway Name') }}">{{ $item->name }}</td>
                                        <td data-label="{{ translate('Instance') }}">{{ \Illuminate\Support\Arr::get($item->meta_data, 'instance', translate('N/A')) }}</td>
                                        <td data-label="{{ translate('Server') }}">{{ \Illuminate\Support\Arr::get($item->meta_data, 'server', translate('N/A')) }}</td>
                                        <td data-label="{{ translate('Delay Settings') }}">
                                            <div class="d-flex flex-column gap-1 align-items-start ">
                                                <span>{{ translate('Per Message Min: ') }}{{ $item->per_message_min_delay }}</span>
                                                <span>{{ translate('Per Message Max: ') }}{{ $item->per_message_max_delay }}</span>
                                                <span>{{ translate('Delay After Count: ') }}{{ $item->delay_after_count }}</span>
                                                <span>{{ translate('Delay After Duration: ') }}{{ $item->delay_after_duration }}</span>
                                                <span>{{ translate('Reset After Count: ') }}{{ $item->reset_after_count }}</span>
                                            </div>
                                        </td>
                                        <td data-label="{{ translate('Status') }}">{{ $item->status->badge() }}</td>
                                        <td data-label={{ translate('Option')}}>
                                            <div class="d-flex align-items-center gap-1">
                                                <button class="icon-btn btn-ghost btn-sm info-soft circle update-evolution-gateway"
                                                    type="button"
                                                    data-url="{{ route('admin.gateway.whatsapp.evolution.update', ['id' => $item->id])}}"
                                                    data-name="{{ $item->name }}"
                                                    data-per_message_min_delay="{{ $item->per_message_min_delay }}"
                                                    data-per_message_max_delay="{{ $item->per_message_max_delay }}"
                                                    data-delay_after_count="{{ $item->delay_after_count }}"
                                                    data-delay_after_duration="{{ $item->delay_after_duration }}"
                                                    data-reset_after_count="{{ $item->reset_after_count }}"
                                                    data-credentials="{{ json_encode($item->meta_data) }}" data-bs-toggle="modal"
                                                    data-bs-target="#updateEvolutionGateway">
                                                    <i class="ri-edit-line"></i>
                                                    <span class="tooltiptext"> {{ translate('Update Evolution Gateway') }} </span>
                                                </button>
                                                <button class="icon-btn btn-ghost btn-sm danger-soft circle text-danger delete-evolution-gateway"
                                                    type="button"
                                                    data-item-id="{{ $item->id }}"
                                                    data-url="{{route('admin.gateway.whatsapp.evolution.destroy', ['id' => $item->id ])}}" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteEvolutionGateway">
                                                    <i class="ri-delete-bin-line"></i>
                                                    <span class="tooltiptext"> {{ translate('Delete Evolution Gateway') }} </span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="50"><span class="text-danger">{{ translate('No data Available') }}</span></td>
                                    </tr>
                                </tbody>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('admin.partials.pagination', ['paginator' => $gateways])
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <div class="modal fade" id="addEvolutionGateway" tabindex="-1" aria-labelledby="addEvolutionGateway" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered ">
            <div class="modal-content">
                <form action="{{route('admin.gateway.whatsapp.evolution.store')}}" method="POST">
                    @csrf
                    <input type="text" hidden name="type" value="evolution">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"> {{ translate('Add Evolution Gateway') }} </h5>
                        <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                            <i class="ri-close-large-line"></i>
                        </button>
                    </div>
                    <div class="modal-body modal-lg-custom-height">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label" for="name">{{ translate('Gateway Name')}} <span class="text-danger">*</span></label>
                                <input type="text" class="mt-2 form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{old('name')}}" placeholder="{{ translate('Add a name for your Evolution Gateway')}}" autocomplete="true" aria-label="name">
                                @error('name')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="per_message_min_delay" class="form-label"> {{ translate('Per Message Minimum Delay (Seconds)')}}<span class="text-danger">*</span> </label>
                                    <input type="number" id="per_message_min_delay" name="per_message_min_delay"  placeholder="{{ translate('e.g., 0.5 seconds minimum delay per message') }}" class="form-control" aria-label="per_message_min_delay"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="per_message_max_delay" class="form-label"> {{ translate('Per Message Maximum Delay (Seconds)')}}<span class="text-danger">*</span> </label>
                                    <input type="number" id="per_message_max_delay" name="per_message_max_delay" placeholder="{{ translate('e.g., 0.5 seconds max delay per message') }}" class="form-control" aria-label="per_message_max_delay"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="delay_after_count" class="form-label">{{ translate('Delay After Count') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="1" id="delay_after_count" name="delay_after_count" placeholder="{{ translate('e.g., pause after 50 messages') }}" class="form-control" aria-label="Delay After Count"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="delay_after_duration" class="form-label">{{ translate('Delay After Duration (Seconds)') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="0.1" id="delay_after_duration" name="delay_after_duration" placeholder="{{ translate('e.g., pause for 5 seconds') }}" class="form-control" aria-label="Delay After Duration"/>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-inner">
                                    <label for="reset_after_count" class="form-label">{{ translate('Reset After Count') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="1" id="reset_after_count" name="reset_after_count" placeholder="{{ translate('e.g., reset after 200 messages') }}" class="form-control" aria-label="Reset After Count"/>
                                </div>
                            </div>
                            @foreach ($credentials['required'] as $creds_key => $creds_value)
                                <div class="col-12 col-lg-6">
                                    <label class="form-label" for="{{ $creds_key }}">{{translate(textFormat(['_'], $creds_key))}} <span class="text-danger">*</span></label>
                                    <input type="text" id="{{ $creds_key }}" class="mt-2 form-control" name="meta_data[{{$creds_key}}]" value="{{old($creds_key)}}" placeholder="Enter the {{translate(textFormat(['_'], $creds_key))}}"  aria-label="{{$creds_key}}" autocomplete="true">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal"> {{ translate('Close') }} </button>
                        <button type="submit" class="i-btn btn--primary btn--md"> {{ translate('Save') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateEvolutionGateway" tabindex="-1"
        aria-labelledby="updateEvolutionGateway" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered ">
            <div class="modal-content">
                <form id="updateEvolutionGatewayForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="text" hidden name="type" value="evolution">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            {{ translate('Update Evolution Gateway') }} </h5>
                        <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer"
                            data-bs-dismiss="modal">
                            <i class="ri-close-large-line"></i>
                        </button>
                    </div>
                    <div class="modal-body modal-lg-custom-height">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <label for="name" class="form-label">{{ translate('Gateway Name') }} <sup
                                        class="text--danger">*</sup></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="{{ translate('Update Gateway Name') }}" autocomplete="true">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="per_message_min_delay" class="form-label"> {{ translate('Per Message Minimum Delay (Seconds)')}}<span class="text-danger">*</span> </label>
                                    <input type="number" id="per_message_min_delay" name="per_message_min_delay"  placeholder="{{ translate('e.g., 0.5 seconds minimum delay per message') }}" class="form-control" aria-label="per_message_min_delay"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="per_message_max_delay" class="form-label"> {{ translate('Per Message Maximum Delay (Seconds)')}}<span class="text-danger">*</span> </label>
                                    <input type="number" id="per_message_max_delay" name="per_message_max_delay" placeholder="{{ translate('e.g., 0.5 seconds max delay per message') }}" class="form-control" aria-label="per_message_max_delay"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="delay_after_count" class="form-label">{{ translate('Delay After Count') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="1" id="delay_after_count" name="delay_after_count" placeholder="{{ translate('e.g., pause after 50 messages') }}" class="form-control" aria-label="Delay After Count"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-inner">
                                    <label for="delay_after_duration" class="form-label">{{ translate('Delay After Duration (Seconds)') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="0.1" id="delay_after_duration" name="delay_after_duration" placeholder="{{ translate('e.g., pause for 5 seconds') }}" class="form-control" aria-label="Delay After Duration"/>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-inner">
                                    <label for="reset_after_count" class="form-label">{{ translate('Reset After Count') }}<span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="1" id="reset_after_count" name="reset_after_count" placeholder="{{ translate('e.g., reset after 200 messages') }}" class="form-control" aria-label="Reset After Count"/>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="row" id="edit_cred"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="i-btn btn--danger outline btn--md" data-bs-dismiss="modal">
                            {{ translate('Close') }} </button>
                        <button type="submit" class="i-btn btn--primary btn--md"> {{ translate('Save') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade actionModal" id="deleteEvolutionGateway" tabindex="-1"
        aria-labelledby="deleteEvolutionGateway" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header text-start">
                    <span class="action-icon danger">
                        <i class="bi bi-exclamation-circle"></i>
                    </span>
                </div>
                <form method="POST" id="deleteEvolutionGateway">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="_method" value="DELETE">
                        <div class="action-message">
                            <h5>{{ translate('Are you sure to delete this Evolution gateway?') }}</h5>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="i-btn btn--dark outline btn--lg" data-bs-dismiss="modal">
                            {{ translate('Cancel') }} </button>
                        <button type="submit" class="i-btn btn--danger btn--lg" data-bs-dismiss="modal">
                            {{ translate('Delete') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script-push')
<script>
    (function($) {
        "use strict";
        flatpickr("#datePicker", { dateFormat: "Y-m-d", mode: "range" });
        $(document).ready(function() {
            $('.update-evolution-gateway').on('click', function() {
                $("#edit_cred").empty();
                var credentials = $(this).data('credentials');
                const modal = $('#updateEvolutionGateway');
                modal.find('form[id=updateEvolutionGatewayForm]').attr('action', $(this).data('url'));
                modal.find('input[name=name]').val($(this).attr('data-name'));
                modal.find('input[name=per_message_min_delay]').val($(this).data('per_message_min_delay'));
                modal.find('input[name=per_message_max_delay]').val($(this).data('per_message_max_delay'));
                modal.find('input[name=delay_after_count]').val($(this).data('delay_after_count'));
                modal.find('input[name=delay_after_duration]').val($(this).data('delay_after_duration'));
                modal.find('input[name=reset_after_count]').val($(this).data('reset_after_count'));
                var html = ``;
                $.each(credentials, function(key, value) {
                    html += `
                        <div class="col-lg-6">
                            <label class="form-label mt-3" for="${key}">${textFormat(['_'],key)}<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="meta_data[${key}]" value="${value}" placeholder="Enter the ${key}">
                        </div>`;
                });
                $("#edit_cred").append(html);
                modal.modal('show');
            });

            $('.delete-evolution-gateway').on('click', function() {
                var modal = $('#deleteEvolutionGateway');
                modal.find('form[id=deleteEvolutionGateway]').attr('action', $(this).data('url'));
                modal.modal('show');
            });
        });
    })(jQuery);
</script>
@endpush



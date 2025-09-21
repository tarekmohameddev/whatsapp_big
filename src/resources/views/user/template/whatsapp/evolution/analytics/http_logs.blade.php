@extends('user.layouts.app')
@section('panel')

<main class="main-body">
    <div class="container-fluid px-0 main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h2>{{ $title }}</h2>
                <div class="breadcrumb-wrapper">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('user.dashboard') }}">{{ translate('Dashboard') }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="table-filter mb-4">
            <form action="{{ route(Route::currentRouteName()) }}" class="filter-form">
                <div class="row g-3">
                    <div class="col-xxl-3 col-lg-3">
                        <select class="form-select" name="template_id">
                            <option value="">{{ translate('All Templates') }}</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ request('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl-3 col-lg-3">
                        <select class="form-select" name="status">
                            <option value="">{{ translate('Any Status') }}</option>
                            <option value="success" {{ request('status')==='success' ? 'selected' : '' }}>{{ translate('Success') }}</option>
                            <option value="error" {{ request('status')==='error' ? 'selected' : '' }}>{{ translate('Error') }}</option>
                        </select>
                    </div>
                    <div class="col-xxl-3 col-lg-3">
                        <select class="form-select" name="method">
                            <option value="">{{ translate('Any Method') }}</option>
                            @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                                <option value="{{ $m }}" {{ request('method')===$m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl-3 col-lg-3">
                        <div class="filter-action">
                            <div class="input-group">
                                <input type="text" class="form-control" id="datePicker" name="date" value="{{ request('date') }}" placeholder="{{ translate('Filter by date') }}" aria-describedby="filterByDate">
                                <span class="input-group-text" id="filterByDate">
                                    <i class="ri-calendar-2-line"></i>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="filter-action-btn">
                                    <i class="ri-menu-search-line"></i> {{ translate('Search') }}
                                </button>
                                <a class="filter-action-btn bg-danger text-white" href="{{ route(Route::currentRouteName()) }}">
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
                    <h4 class="card-title">{{ translate('HTTP Action Logs') }}</h4>
                </div>
            </div>
            <div class="card-body px-0 pt-0">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ translate('Date') }}</th>
                                <th scope="col">{{ translate('Template') }}</th>
                                <th scope="col">{{ translate('Row ID') }}</th>
                                <th scope="col">{{ translate('Method') }}</th>
                                <th scope="col">{{ translate('URL') }}</th>
                                <th scope="col">{{ translate('Status') }}</th>
                                <th scope="col">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at }}</td>
                                    <td>#{{ $log->template_id }}</td>
                                    <td>{{ $log->selected_row_id }}</td>
                                    <td>{{ $log->method }}</td>
                                    <td class="text-break" style="max-width:320px;">{{ $log->url }}</td>
                                    <td>
                                        @if($log->error_message)
                                            <span class="badge bg-danger">{{ translate('Error') }}</span>
                                        @elseif(!is_null($log->response_status))
                                            <span class="badge bg-success">{{ $log->response_status }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button 
                                            class="icon-btn btn-ghost btn-sm info-soft circle"
                                            data-bs-toggle="modal" data-bs-target="#viewLogModal"
                                            data-request='@json(["headers"=>$log->request_headers, "body"=>$log->request_body])'
                                            data-response='@json(["status"=>$log->response_status, "body"=>$log->response_body, "error"=>$log->error_message])'
                                        >
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ translate('No Data Found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('user.partials.pagination', ['paginator' => $logs])
            </div>
        </div>
    </div>
</main>

@endsection

@push('script-push')
<script>
    "use strict";
    flatpickr("#datePicker", {
        dateFormat: "Y-m-d",
        mode: "range",
    });

    const modalEl = document.getElementById('viewLogModal');
    modalEl?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const req = btn?.getAttribute('data-request');
        const res = btn?.getAttribute('data-response');
        try {
            const reqObj = JSON.parse(req || '{}');
            const resObj = JSON.parse(res || '{}');
            document.getElementById('reqJson').textContent = JSON.stringify(reqObj, null, 2);
            document.getElementById('resJson').textContent = JSON.stringify(resObj, null, 2);
        } catch (e) {
            document.getElementById('reqJson').textContent = '-';
            document.getElementById('resJson').textContent = '-';
        }
    });
</script>
@endpush

@section('modal')
<div class="modal fade" id="viewLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('HTTP Action Details') }}</h5>
                <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle modal-closer" data-bs-dismiss="modal">
                    <i class="ri-close-large-line"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>{{ translate('Request') }}</h6>
                        <pre class="bg-light p-2 rounded small" id="reqJson" style="max-height:300px;overflow:auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6>{{ translate('Response') }}</h6>
                        <pre class="bg-light p-2 rounded small" id="resJson" style="max-height:300px;overflow:auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



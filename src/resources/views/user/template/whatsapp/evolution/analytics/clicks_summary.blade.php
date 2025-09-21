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
            <div class="page-header-right">
                <a href="{{ route('user.template.whatsapp.evolution.analytics.clicks') }}" class="i-btn btn--sm">{{ translate('View Raw Clicks') }}</a>
            </div>
        </div>

        <div class="table-filter mb-4">
            <form action="{{ route(Route::currentRouteName()) }}" class="filter-form">
                <div class="row g-3">
                    <div class="col-xxl-6 col-lg-6">
                        <select class="form-select" name="template_id">
                            <option value="">{{ translate('All Templates') }}</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ request('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl-6 col-lg-6">
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
                    <h4 class="card-title">{{ translate('Clicks Summary') }}</h4>
                </div>
            </div>
            <div class="card-body px-0 pt-0">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ translate('Template') }}</th>
                                <th scope="col">{{ translate('Row ID') }}</th>
                                <th scope="col">{{ translate('Title') }}</th>
                                <th scope="col" class="text-end">{{ translate('Clicks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $r)
                                <tr>
                                    <td>#{{ $r->template_id }}</td>
                                    <td>{{ $r->selected_row_id }}</td>
                                    <td>{{ $r->row_title ?: '-' }}</td>
                                    <td class="text-end">{{ $r->clicks }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ translate('No Data Found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('user.partials.pagination', ['paginator' => $rows])
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
</script>
@endpush



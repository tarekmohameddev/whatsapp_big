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
                <a href="{{ route('user.dashboard') }}">{{ translate('dashboard') }}</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
            </ol>
          </nav>
        </div>
      </div>
      <div class="page-header-right">
        <a class="i-btn btn--primary btn--sm" href="{{ route('user.template.whatsapp.evolution.create') }}">
          <i class="ri-add-fill fs-16"></i> {{ translate('Create') }}
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-header-left">
          <h4 class="card-title">{{ translate('Template List') }}</h4>
        </div>
      </div>
      <div class="card-body px-0 pt-0">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th scope="col">{{ translate('Name') }}</th>
                <th scope="col">{{ translate('Type') }}</th>
                <th scope="col">{{ translate('Status') }}</th>
                <th scope="col">{{ translate('Updated At') }}</th>
                <th scope="col">{{ translate('Option') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($templates as $template)
              <tr>
                <td><span class="fw-semibold text-dark">{{ $template->name }}</span></td>
                <td>{{ \Illuminate\Support\Str::headline(str_replace('_',' ',$template->type->value)) }}</td>
                <td data-label="{{ translate('Status')}}">
                  <div class="i-badge {{ $template->status->value == \App\Enums\Common\Status::ACTIVE->value ? 'success-soft' : 'danger-soft' }} pill">
                    {{ $template->status->value }}
                  </div>
                </td>
                <td>{{ $template->updated_at?->toDayDateTimeString() }}</td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('user.template.whatsapp.evolution.edit', $template->uid) }}" class="icon-btn btn-ghost btn-sm success-soft circle">
                      <i class="ri-edit-line"></i>
                      <span class="tooltiptext">{{ translate('Edit Template') }}</span>
                    </a>
                    <form action="{{ route('user.template.whatsapp.evolution.destroy', $template->uid) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this template?') }}')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="icon-btn btn-ghost btn-sm danger-soft circle text-danger">
                        <i class="ri-delete-bin-line"></i>
                        <span class="tooltiptext">{{ translate('Delete template') }}</span>
                      </button>
                    </form>
                  </div>
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
        @include('user.partials.pagination', ['paginator' => $templates])
      </div>
    </div>
  </div>
</main>
@endsection



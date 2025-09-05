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
        <a href="{{ route('user.pipelines.integrations.create') }}" class="i-btn btn--primary btn--sm">{{ translate('Create Integration') }}</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>{{ translate('Name') }}</th>
                <th>{{ translate('Webhook URL') }}</th>
                <th>{{ translate('Phone Path') }}</th>
                <th class="text-end">{{ translate('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($integrations as $integration)
              <tr>
                <td>{{ $integration->name }}</td>
                <td>
                  <code>{{ url('/api/integrations/' . $integration->uid) }}</code>
                </td>
                <td><code>{{ $integration->phone_path }}</code></td>
                <td class="text-end">
                  <a href="{{ route('user.pipelines.integrations.edit', $integration->uid) }}" class="i-btn btn--sm btn--secondary">{{ translate('Edit') }}</a>
                  <form action="{{ route('user.pipelines.integrations.destroy', $integration->uid) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="i-btn btn--sm btn--danger" onclick="return confirm('{{ translate('Are you sure?') }}')">{{ translate('Delete') }}</button>
                  </form>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center">{{ translate('No integrations yet') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">{{ $integrations->links() }}</div>
      </div>
    </div>
  </div>
</main>
@endsection



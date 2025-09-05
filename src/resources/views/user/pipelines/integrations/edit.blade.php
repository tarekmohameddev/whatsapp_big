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
              <li class="breadcrumb-item">
                <a href="{{ route('user.pipelines.integrations.index') }}">{{ translate('Pipelines & Integrations') }}</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="alert alert-info d-flex align-items-center gap-2" role="alert">
          <span>{{ translate('Webhook URL') }}:</span>
          <code>{{ url('/api/integrations/' . $integration->uid) }}</code>
          <span class="ms-3">{{ translate('Header') }}: <code>X-Webhook-Secret: {{ $integration->webhook_secret }}</code></span>
        </div>
        <form action="{{ route('user.pipelines.integrations.update', $integration->uid) }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="row g-4">
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Name') }}</label>
                <input type="text" name="name" class="form-control" value="{{ $integration->name }}" required />
              </div>
            </div>
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Webhook Password (Header: X-Webhook-Secret)') }}</label>
                <input type="text" name="webhook_secret" class="form-control" value="{{ $integration->webhook_secret }}" required />
              </div>
            </div>
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Phone Number JSON Path') }}</label>
                <input type="text" name="phone_path" class="form-control" value="{{ $integration->phone_path }}" required />
              </div>
            </div>

            <div class="col-12"><hr /></div>

            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Allowed Methods') }}</label>
                @php $allowed = (array) ($integration->allowed_methods ?? []); @endphp
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="allowed_methods[cloud_api]" value="1" id="m_cloud" {{ (isset($allowed['cloud_api']) && $allowed['cloud_api']) ? 'checked' : '' }}>
                  <label class="form-check-label" for="m_cloud">{{ translate('Meta Cloud Official') }}</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="allowed_methods[evolution_api]" value="1" id="m_evo" {{ (isset($allowed['evolution_api']) && $allowed['evolution_api']) ? 'checked' : '' }}>
                  <label class="form-check-label" for="m_evo">{{ translate('Evolution API') }}</label>
                </div>
              </div>
            </div>

            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Allowed Gateways (IDs)') }}</label>
                @php $ag = (array) ($integration->allowed_gateways ?? []); @endphp
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">{{ translate('Cloud API Gateways') }}</label>
                    <select class="form-select select2-search" name="allowed_gateways[cloud_api][]" multiple>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                        <option value="{{ $g->id }}" {{ in_array($g->id, (array) ($ag['cloud_api'] ?? [])) ? 'selected' : '' }}>{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">{{ translate('Evolution API Gateways') }}</label>
                    <select class="form-select select2-search" name="allowed_gateways[evolution_api][]" multiple>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                        <option value="{{ $g->id }}" {{ in_array($g->id, (array) ($ag['evolution_api'] ?? [])) ? 'selected' : '' }}>{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12"><hr /></div>

            <div class="col-xl-12">
              <div class="form-inner">
                @php $defaults = (array) ($integration->defaults ?? []); @endphp
                <label class="form-label">{{ translate('Defaults (used when no rules match)') }}</label>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">{{ translate('Default Method') }}</label>
                    <select class="form-select" name="defaults[method]" id="default_method">
                      <option value="">{{ translate('None') }}</option>
                      <option value="cloud_api" {{ ($defaults['method'] ?? '') === 'cloud_api' ? 'selected' : '' }}>{{ translate('Meta Cloud Official') }}</option>
                      <option value="evolution_api" {{ ($defaults['method'] ?? '') === 'evolution_api' ? 'selected' : '' }}>{{ translate('Evolution API') }}</option>
                    </select>
                  </div>
                  <div class="col-md-4 default-cloud d-none">
                    <label class="form-label">{{ translate('Default Cloud Gateway') }}</label>
                    <select class="form-select select2-search" name="defaults[cloud_gateway_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                        <option value="{{ $g->id }}" {{ ($defaults['gateway_id'] ?? '') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4 default-cloud d-none">
                    <label class="form-label">{{ translate('Default Cloud Template') }}</label>
                    <select class="form-select select2-search" name="defaults[cloud_template_id]">
                      <option value="">{{ translate('Select') }}</option>
                    </select>
                  </div>
                  <div class="col-md-4 default-evo d-none">
                    <label class="form-label">{{ translate('Default Evolution Gateway') }}</label>
                    <select class="form-select select2-search" name="defaults[evolution_gateway_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                        <option value="{{ $g->id }}" {{ ($defaults['gateway_id'] ?? '') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4 default-evo d-none">
                    <label class="form-label">{{ translate('Default Evolution Template') }}</label>
                    <select class="form-select select2-search" name="defaults[evolution_template_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @isset($evolutionTemplates)
                        @foreach($evolutionTemplates as $tpl)
                          <option value="{{ $tpl->id }}" {{ ($defaults['template_id'] ?? '') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}</option>
                        @endforeach
                      @endisset
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12"><hr /></div>

            <div class="col-12">
              <h6 class="mb-2">{{ translate('Rules') }}</h6>
              <div id="rules-container">
                @foreach($rules as $i => $rule)
                <div class="border rounded p-3 mb-3">
                  <input type="hidden" name="rules[{{ $i }}][id]" value="{{ $rule->id }}" />
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">{{ translate('Rule Name') }}</label>
                      <input class="form-control" name="rules[{{ $i }}][name]" value="{{ $rule->name }}" required />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">{{ translate('Match Path') }}</label>
                      <input class="form-control" name="rules[{{ $i }}][match_path]" value="{{ $rule->match_path }}" required />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">{{ translate('Operator') }}</label>
                      <select class="form-select" name="rules[{{ $i }}][operator]">
                        @foreach(['equals','in','not_equals','exists'] as $op)
                          <option value="{{ $op }}" {{ $rule->operator === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">{{ translate('Value') }}</label>
                      <input class="form-control" name="rules[{{ $i }}][value]" value="{{ $rule->value }}" />
                    </div>
                    <div class="col-12"><hr /></div>
                    @php $act = (array) ($rule->action ?? []); @endphp
                    <div class="col-md-3">
                      <label class="form-label">{{ translate('Action Method') }}</label>
                      <select class="form-select" name="rules[{{ $i }}][action][method]">
                        <option value="cloud_api" {{ ($act['method'] ?? '') === 'cloud_api' ? 'selected' : '' }}>{{ translate('Meta Cloud Official') }}</option>
                        <option value="evolution_api" {{ ($act['method'] ?? '') === 'evolution_api' ? 'selected' : '' }}>{{ translate('Evolution API') }}</option>
                      </select>
                    </div>
                    <div class="col-md-3 action-cloud d-none">
                      <label class="form-label">{{ translate('Cloud Gateways') }}</label>
                      <select class="form-select select2-search" name="rules[{{ $i }}][action][cloud_gateway_ids][]" multiple>
                        @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                          <option value="{{ $g->id }}" {{ in_array($g->id, (array) ($act['gateway_ids'] ?? [])) && ($act['method'] ?? '') === 'cloud_api' ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3 action-cloud d-none">
                      <label class="form-label">{{ translate('Cloud Template') }}</label>
                      <select class="form-select select2-search" name="rules[{{ $i }}][action][cloud_template_id]">
                        <option value="">{{ translate('Select') }}</option>
                      </select>
                    </div>
                    <div class="col-md-3 action-evo d-none">
                      <label class="form-label">{{ translate('Evolution Gateways') }}</label>
                      <select class="form-select select2-search" name="rules[{{ $i }}][action][evolution_gateway_ids][]" multiple>
                        @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                          <option value="{{ $g->id }}" {{ in_array($g->id, (array) ($act['gateway_ids'] ?? [])) && ($act['method'] ?? '') === 'evolution_api' ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3 action-evo d-none">
                      <label class="form-label">{{ translate('Evolution Template') }}</label>
                      <select class="form-select select2-search" name="rules[{{ $i }}][action][evolution_template_id]">
                        <option value="">{{ translate('Select') }}</option>
                        @isset($evolutionTemplates)
                          @foreach($evolutionTemplates as $tpl)
                            <option value="{{ $tpl->id }}" {{ (($act['template_id'] ?? null) == $tpl->id) && ($act['method'] ?? '') === 'evolution_api' ? 'selected' : '' }}>{{ $tpl->name }}</option>
                          @endforeach
                        @endisset
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">{{ translate('Priority') }}</label>
                      <input type="number" class="form-control" name="rules[{{ $i }}][priority]" value="{{ $rule->priority }}" />
                    </div>
                    <div class="col-md-1">
                      <label class="form-label">{{ translate('Status') }}</label>
                      <select class="form-select" name="rules[{{ $i }}][status]">
                        <option value="active" {{ $rule->status === 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                        <option value="inactive" {{ $rule->status === 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                      </select>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
              <button type="button" class="i-btn btn--sm btn--secondary mt-2" id="add-rule">{{ translate('Add Rule') }}</button>
            </div>

            <div class="col-12">
              <div class="form-action">
                <button type="submit" class="i-btn btn--primary btn--md">{{ translate('Save') }}</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

@push('script-push')
<script>
  (function(){
    const wrap = document.getElementById('rules-container');
    const addBtn = document.getElementById('add-rule');
    let idx = {{ count($rules) }};
    function row(i){
      return `
      <div class="border rounded p-3 mb-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">{{ translate('Rule Name') }}</label>
            <input class="form-control" name="rules[${i}][name]" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Match Path') }}</label>
            <input class="form-control" name="rules[${i}][match_path]" placeholder="order.category_id" required />
          </div>
          <div class="col-md-2">
            <label class="form-label">{{ translate('Operator') }}</label>
            <select class="form-select" name="rules[${i}][operator]">
              <option value="equals">equals</option>
              <option value="in">in</option>
              <option value="not_equals">not_equals</option>
              <option value="exists">exists</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ translate('Value') }}</label>
            <input class="form-control" name="rules[${i}][value]" />
          </div>
          <div class="col-12"><hr /></div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Action Method') }}</label>
            <select class="form-select" name="rules[${i}][action][method]">
              <option value="cloud_api">{{ translate('Meta Cloud Official') }}</option>
              <option value="evolution_api">{{ translate('Evolution API') }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Gateway IDs (comma separated)') }}</label>
            <input class="form-control" name="rules[${i}][action][gateway_ids_str]" placeholder="1,2" />
          </div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Template ID') }}</label>
            <input class="form-control" name="rules[${i}][action][template_id]" />
          </div>
          <div class="col-md-2">
            <label class="form-label">{{ translate('Priority') }}</label>
            <input type="number" class="form-control" name="rules[${i}][priority]" value="100" />
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button class="i-btn btn--danger btn--sm remove-rule" type="button">&times;</button>
          </div>
        </div>
      </div>`;
    }
    addBtn.addEventListener('click', function(){
      wrap.insertAdjacentHTML('beforeend', row(idx++));
    });
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('remove-rule')){
        e.target.closest('.border').remove();
      }
    });
    function toggleDefaultByMethod(){
      const method = document.getElementById('default_method').value;
      document.querySelectorAll('.default-cloud').forEach(e=>e.classList.toggle('d-none', method!=='cloud_api'));
      document.querySelectorAll('.default-evo').forEach(e=>e.classList.toggle('d-none', method!=='evolution_api'));
    }
    document.getElementById('default_method').addEventListener('change', toggleDefaultByMethod);
    toggleDefaultByMethod();
    document.addEventListener('change', function(e){
      if(e.target && e.target.name && e.target.name.endsWith('[action][method]')){
        const row = e.target.closest('.row');
        const method = e.target.value;
        row.querySelectorAll('.action-cloud').forEach(el=>el.classList.toggle('d-none', method!=='cloud_api'));
        row.querySelectorAll('.action-evo').forEach(el=>el.classList.toggle('d-none', method!=='evolution_api'));
      }
    });
  })();
</script>
@endpush

@endsection



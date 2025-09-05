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
        <form action="{{ route('user.pipelines.integrations.store') }}" method="POST">
          @csrf
          <div class="row g-4">
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Name') }}</label>
                <input type="text" name="name" class="form-control" required />
              </div>
            </div>
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Webhook Password (Header: X-Webhook-Secret)') }}</label>
                <input type="text" name="webhook_secret" class="form-control" required />
              </div>
            </div>
            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Phone Number JSON Path') }}</label>
                <input type="text" name="phone_path" class="form-control" placeholder="order.customer.phone" required />
              </div>
            </div>

            <div class="col-12"><hr /></div>

            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Allowed Methods') }}</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="allowed_methods[cloud_api]" value="1" id="m_cloud">
                  <label class="form-check-label" for="m_cloud">{{ translate('Meta Cloud Official') }}</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="allowed_methods[evolution_api]" value="1" id="m_evo">
                  <label class="form-check-label" for="m_evo">{{ translate('Evolution API') }}</label>
                </div>
              </div>
            </div>

            <div class="col-xl-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Allowed Gateways (IDs)') }}</label>
                <small class="d-block mb-2">{{ translate('Choose gateways under each method') }}</small>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">{{ translate('Cloud API Gateways') }}</label>
                    <select class="form-select select2-search" name="allowed_gateways[cloud_api][]" multiple>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">{{ translate('Evolution API Gateways') }}</label>
                    <select class="form-select select2-search" name="allowed_gateways[evolution_api][]" multiple>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12"><hr /></div>

            <div class="col-xl-12">
              <div class="form-inner">
                <label class="form-label">{{ translate('Defaults (used when no rules match)') }}</label>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">{{ translate('Default Method') }}</label>
                    <select class="form-select" name="defaults[method]" id="default_method">
                      <option value="">{{ translate('None') }}</option>
                      <option value="cloud_api">{{ translate('Meta Cloud Official') }}</option>
                      <option value="evolution_api">{{ translate('Evolution API') }}</option>
                    </select>
                  </div>
                  <div class="col-md-4 default-cloud d-none">
                    <label class="form-label">{{ translate('Default Cloud Gateway') }}</label>
                    <select class="form-select select2-search" name="defaults[cloud_gateway_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4 default-cloud d-none">
                    <label class="form-label">{{ translate('Default Cloud Template') }}</label>
                    <select class="form-select select2-search" name="defaults[cloud_template_id]">
                      <option value="">{{ translate('Select') }}</option>
                      {{-- Cloud templates loaded dynamically in existing WhatsApp UI; keep ID entry minimal here --}}
                    </select>
                  </div>
                  <div class="col-md-4 default-evo d-none">
                    <label class="form-label">{{ translate('Default Evolution Gateway') }}</label>
                    <select class="form-select select2-search" name="defaults[evolution_gateway_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4 default-evo d-none">
                    <label class="form-label">{{ translate('Default Evolution Template') }}</label>
                    <select class="form-select select2-search" name="defaults[evolution_template_id]">
                      <option value="">{{ translate('Select') }}</option>
                      @isset($evolutionTemplates)
                        @foreach($evolutionTemplates as $tpl)
                          <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
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
              <div id="rules-container"></div>
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
    function row(idx){
      return `
      <div class="border rounded p-3 mb-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">{{ translate('Rule Name') }}</label>
            <input class="form-control" name="rules[${idx}][name]" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Match Path') }}</label>
            <input class="form-control" name="rules[${idx}][match_path]" placeholder="order.category_id" required />
          </div>
          <div class="col-md-2">
            <label class="form-label">{{ translate('Operator') }}</label>
            <select class="form-select" name="rules[${idx}][operator]">
              <option value="equals">equals</option>
              <option value="in">in</option>
              <option value="not_equals">not_equals</option>
              <option value="exists">exists</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ translate('Value') }}</label>
            <input class="form-control" name="rules[${idx}][value]" placeholder="e.g. 12 or 1,2,3" />
          </div>
          <div class="col-12"><hr /></div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Action Method') }}</label>
            <select class="form-select" name="rules[${idx}][action][method]">
              <option value="cloud_api">{{ translate('Meta Cloud Official') }}</option>
              <option value="evolution_api">{{ translate('Evolution API') }}</option>
            </select>
          </div>
          <div class="col-md-3 action-cloud d-none">
            <label class="form-label">{{ translate('Cloud Gateways') }}</label>
            <select class="form-select select2-search" name="rules[${idx}][action][cloud_gateway_ids][]" multiple>
              @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 action-cloud d-none">
            <label class="form-label">{{ translate('Cloud Template') }}</label>
            <select class="form-select select2-search" name="rules[${idx}][action][cloud_template_id]">
              <option value="">{{ translate('Select') }}</option>
            </select>
          </div>
          <div class="col-md-3 action-evo d-none">
            <label class="form-label">{{ translate('Evolution Gateways') }}</label>
            <select class="form-select select2-search" name="rules[${idx}][action][evolution_gateway_ids][]" multiple>
              @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 action-evo d-none">
            <label class="form-label">{{ translate('Evolution Template') }}</label>
            <select class="form-select select2-search" name="rules[${idx}][action][evolution_template_id]">
              <option value="">{{ translate('Select') }}</option>
              @isset($evolutionTemplates)
                @foreach($evolutionTemplates as $tpl)
                  <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                @endforeach
              @endisset
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">{{ translate('Priority') }}</label>
            <input type="number" class="form-control" name="rules[${idx}][priority]" value="100" />
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button class="i-btn btn--danger btn--sm remove-rule" type="button">&times;</button>
          </div>
        </div>
      </div>`;
    }
    let idx = 0;
    addBtn.addEventListener('click', function(){
      wrap.insertAdjacentHTML('beforeend', row(idx++));
    });
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('remove-rule')){
        e.target.closest('.border').remove();
      }
    });
    // Toggle default selects by method
    function toggleDefaultByMethod(){
      const method = document.getElementById('default_method').value;
      document.querySelectorAll('.default-cloud').forEach(e=>e.classList.toggle('d-none', method!=='cloud_api'));
      document.querySelectorAll('.default-evo').forEach(e=>e.classList.toggle('d-none', method!=='evolution_api'));
    }
    document.getElementById('default_method').addEventListener('change', toggleDefaultByMethod);
    toggleDefaultByMethod();
    // Toggle rule action controls per method
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



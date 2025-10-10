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
          <div class="form-wrapper mb-4">
            <h6 class="form-wrapper-title mb-3">{{ translate('General') }}</h6>
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

            </div>
          </div>

            <div class="col-12"><hr /></div>

            <div class="col-12"><hr /></div>

            <div class="col-12">
              <h6 class="mb-2">{{ translate('Rules') }}</h6>
              <p class="text-muted mb-3">{{ translate('Add conditional rules to select method, gateways and templates based on your payload.') }}</p>
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
    // Build gateway/template sources by method
    const METHOD_GATEWAYS = {
      cloud_api: [
        @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)
          { id: "{{ $g->id }}", name: "{{ addslashes($g->name) }}" },
        @endforeach
      ],
      evolution_api: [
        @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)
          { id: "{{ $g->id }}", name: "{{ addslashes($g->name) }}" },
        @endforeach
      ],
    };
    const METHOD_TEMPLATES = {
      cloud_api: [
        // Cloud templates can be populated via existing UI later
      ],
      evolution_api: [
        @isset($evolutionTemplates)
          @foreach($evolutionTemplates as $tpl)
            { id: "{{ $tpl->id }}", name: "{{ addslashes($tpl->name) }}" },
          @endforeach
        @endisset
      ],
    };
    const TEMPLATE_FETCH_URL = "{{ route('user.template.fetch', ['type' => 'whatsapp']) }}";
    function rebuildOptions(selectEl, items, placeholder){
      const cur = selectEl.value;
      let html = `<option value="">${placeholder || '{{ translate('Select') }}'}</option>`;
      items.forEach(function(it){ html += `<option value="${it.id}">${it.name}</option>`; });
      selectEl.innerHTML = html;
      // restore selection if still present
      if (items.some(it => String(it.id) === String(cur))) {
        selectEl.value = String(cur);
      }
      if (window.jQuery && jQuery().select2) { jQuery(selectEl).trigger('change.select2'); }
    }
    function updateTargetRow(row){
      const methodSel = row.querySelector('select.target-method');
      const gwSel = row.querySelector('select.target-gateway');
      const tplSel = row.querySelector('select.target-template');
      if(!methodSel || !gwSel || !tplSel) return;
      const method = methodSel.value;
      rebuildOptions(gwSel, METHOD_GATEWAYS[method] || [], '{{ translate('Select gateway') }}');
      if(method === 'cloud_api'){
        const cloudId = gwSel.value;
        if(cloudId && window.jQuery){
          jQuery.get(TEMPLATE_FETCH_URL, { cloud_id: cloudId }).done(function(resp){
            const list = (resp && resp.templates) ? resp.templates.map(function(t){ return { id: t.id, name: t.name + (t.template_data && t.template_data.language ? ' ('+t.template_data.language+')' : '') }; }) : [];
            rebuildOptions(tplSel, list, '{{ translate('Select Cloud template') }}');
          }).fail(function(){ rebuildOptions(tplSel, [], '{{ translate('Select Cloud template') }}'); });
        } else {
          rebuildOptions(tplSel, [], '{{ translate('Select Cloud template') }}');
        }
      } else {
        rebuildOptions(tplSel, METHOD_TEMPLATES[method] || [], '{{ translate('Select template') }}');
      }
    }
    function row(idx){
      return `
      <div class=\"border rounded p-3 mb-3\">
        <div class=\"d-flex justify-content-between align-items-center mb-2\">
          <span class=\"badge bg-secondary\">{{ translate('Rule') }} #${idx + 1}</span>
          <button class=\"i-btn btn--danger btn--sm remove-rule\" type=\"button\">&times;</button>
        </div>
        <div class=\"row g-3\">
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
          <div class="col-12">
            <label class="form-label">{{ translate('Action Targets (Round-robin)') }}</label>
            <div class="targets-wrap" data-idx="${idx}"></div>
            <button type="button" class="i-btn btn--sm btn--light add-target" data-idx="${idx}">{{ translate('Add Target') }}</button>
            <div class="text-muted small mt-2">{{ translate('Add one or more targets. Each target has Method, Gateway and Template. Messages will rotate across targets.') }}</div>
          </div>
          
          <div class=\"col-md-2\">
            <label class="form-label">{{ translate('Priority') }}</label>
            <input type="number" class="form-control" name="rules[${idx}][priority]" value="100" />
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
    document.addEventListener('change', function(e){
      if(e.target && e.target.classList.contains('target-method')){
        const row = e.target.closest('.row');
        updateTargetRow(row);
      }
      if(e.target && e.target.classList.contains('target-gateway')){
        const row = e.target.closest('.row');
        const methodSel = row.querySelector('select.target-method');
        if(methodSel && methodSel.value === 'cloud_api'){
          updateTargetRow(row);
        }
      }
    });
    // Targets repeater
    function renderTargetRow(ruleIdx, tIdx){
      return `
      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-3">
          <label class="form-label">{{ translate('Method') }}</label>
          <select class="form-select target-method" name="rules[${ruleIdx}][action][targets][${tIdx}][method]">
            <option value="cloud_api">{{ translate('Meta Cloud Official') }}</option>
            <option value="evolution_api">{{ translate('Evolution API') }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ translate('Gateway') }}</label>
          <select class="form-select select2-search target-gateway" name="rules[${ruleIdx}][action][targets][${tIdx}][gateway_id]">
            <option value="">{{ translate('Select') }}</option>
            @foreach($cloudGateways as $g)
              <option data-type="{{ $g->type }}" value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ translate('Template') }}</label>
          <select class="form-select select2-search target-template" name="rules[${ruleIdx}][action][targets][${tIdx}][template_id]">
            <option value="">{{ translate('Select (Cloud/Evolution)') }}</option>
            @isset($evolutionTemplates)
              @foreach($evolutionTemplates as $tpl)
                <option data-method="evolution_api" value="{{ $tpl->id }}">{{ $tpl->name }}</option>
              @endforeach
            @endisset
          </select>
          <div class="small text-muted mt-1">{{ translate('Add variable mappings for this target (optional). Leave value empty to use JSON path.') }}</div>
          <div class="target-vars" data-rule="${ruleIdx}" data-target="${tIdx}"></div>
          <button type="button" class="i-btn btn--sm btn--light add-target-var" data-rule="${ruleIdx}" data-target="${tIdx}">{{ translate('Add Target Variable') }}</button>
        </div>
        <div class="col-md-1">
          <button type="button" class="i-btn btn--danger btn--sm remove-target">&times;</button>
        </div>
      </div>`;
    }
    const targetCounters = {};
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('add-target')){
        const idx = e.target.getAttribute('data-idx');
        targetCounters[idx] = (targetCounters[idx] || 0) + 1;
        const wrap = document.querySelector(`.targets-wrap[data-idx="${idx}"]`);
        if(wrap){ 
          wrap.insertAdjacentHTML('beforeend', renderTargetRow(idx, targetCounters[idx]-1)); 
          initSelect2(wrap);
          const row = wrap.querySelectorAll('.row.g-2').item(wrap.querySelectorAll('.row.g-2').length - 1);
          if(row){ updateTargetRow(row); }
        }
      }
      if(e.target && e.target.classList.contains('remove-target')){
        e.target.closest('.row').remove();
      }
      if(e.target && e.target.classList.contains('add-target-var')){
        const r = e.target.getAttribute('data-rule');
        const t = e.target.getAttribute('data-target');
        const wrap = document.querySelector(`.target-vars[data-rule="${r}"][data-target="${t}"]`);
        if(!wrap) return;
        const count = wrap.querySelectorAll('.row.g-2').length;
        wrap.insertAdjacentHTML('beforeend', `
        <div class="row g-2 align-items-end mb-2">
          <div class="col-md-4">
            <label class="form-label">{{ translate('Variable Name') }}</label>
            <input class="form-control" name="rules[${r}][action][targets][${t}][variables][${count}][name]" placeholder="order_id" />
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ translate('JSON Path') }}</label>
            <input class="form-control" name="rules[${r}][action][targets][${t}][variables][${count}][path]" placeholder="order.id" />
          </div>
          <div class="col-md-3">
            <label class="form-label">{{ translate('Static Value (optional)') }}</label>
            <input class="form-control" name="rules[${r}][action][targets][${t}][variables][${count}][value]" placeholder="STATIC_TEXT" />
          </div>
          <div class="col-md-1">
            <button type="button" class="i-btn btn--danger btn--sm remove-target-var">&times;</button>
          </div>
        </div>`);
      }
      if(e.target && e.target.classList.contains('remove-target-var')){
        e.target.closest('.row').remove();
      }
    });
    // Init select2 if available with modern chips and placeholder
    function initSelect2(scope){
      if(window.jQuery && jQuery().select2){
        jQuery(scope).find('.select2-search').select2({
          width:'100%',
          placeholder: function(){ return jQuery(this).data('placeholder') || '{{ translate('Select options') }}'; },
          allowClear: true,
          closeOnSelect: false,
          templateSelection: function (data) { return data.text; },
        });
      }
    }
    initSelect2(document);
  })();
</script>
@endpush

@endsection



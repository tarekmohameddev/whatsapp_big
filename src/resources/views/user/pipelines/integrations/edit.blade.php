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
        <div class="alert alert-info d-flex flex-wrap align-items-center gap-2" role="alert">
          <span>{{ translate('Webhook URL') }}:</span>
          <code id="webhook-url">{{ url('/api/integrations/' . $integration->uid) }}</code>
          <button type="button" class="i-btn btn--sm btn--secondary ms-2" id="copy-webhook">{{ translate('Copy') }}</button>
          <span class="ms-3">{{ translate('Header') }}: <code>X-Webhook-Secret: {{ $integration->webhook_secret }}</code></span>
        </div>
        <form action="{{ route('user.pipelines.integrations.update', $integration->uid) }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="form-wrapper mb-4">
            <h6 class="form-wrapper-title mb-3">{{ translate('General') }}</h6>
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

            </div>
          </div>
          <div class="col-12"><hr /></div>

            

            

            <div class="col-12"><hr /></div>

            <div class="col-12">
              <h6 class="mb-2">{{ translate('Rules') }}</h6>
              <p class="text-muted mb-3">{{ translate('Add conditional rules to select method, gateways and templates based on your payload.') }}</p>
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
                    <div class="col-12">
                      <label class="form-label">{{ translate('Action Targets (Round-robin)') }}</label>
                      <div class="targets-wrap" data-idx="{{ $i }}">
                        @php $targets = collect(($act['targets'] ?? []))->values(); @endphp
                        @foreach($targets as $ti => $t)
                        <div class="row g-2 align-items-end mb-2">
                          <div class="col-md-3">
                            <label class="form-label">{{ translate('Method') }}</label>
                            <select class="form-select target-method" name="rules[{{ $i }}][action][targets][{{ $ti }}][method]">
                              <option value="cloud_api" {{ (($t['method'] ?? '') === 'cloud_api') ? 'selected' : '' }}>{{ translate('Meta Cloud Official') }}</option>
                              <option value="evolution_api" {{ (($t['method'] ?? '') === 'evolution_api') ? 'selected' : '' }}>{{ translate('Evolution API') }}</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">{{ translate('Gateway') }}</label>
                            <select class="form-select select2-search target-gateway" name="rules[{{ $i }}][action][targets][{{ $ti }}][gateway_id]">
                              <option value="">{{ translate('Select') }}</option>
                              @foreach($cloudGateways as $g)
                                <option data-type="{{ $g->type }}" value="{{ $g->id }}" {{ ((string)($t['gateway_id'] ?? '') === (string)$g->id) ? 'selected' : '' }}>{{ $g->name }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">{{ translate('Template') }}</label>
                            <select class="form-select select2-search target-template" name="rules[{{ $i }}][action][targets][{{ $ti }}][template_id]">
                              <option value="">{{ translate('Select (Cloud/Evolution)') }}</option>
                              @isset($evolutionTemplates)
                                @foreach($evolutionTemplates as $tpl)
                                  <option data-method="evolution_api" value="{{ $tpl->id }}" {{ ((string)($t['template_id'] ?? '') === (string)$tpl->id) ? 'selected' : '' }}>{{ $tpl->name }}</option>
                                @endforeach
                              @endisset
                            </select>
                            @php $tVars = collect(($t['variables'] ?? []))->values(); @endphp
                            <div class="small text-muted mt-1 cloud-help d-none">{{ translate('Configure Cloud Template parameters below. These values fill template placeholders when dispatching.') }}</div>
                            <div class="cloud-template-params d-none" data-rule="{{ $i }}" data-target="{{ $ti }}"></div>
                            <div class="small text-muted mt-1 evo-help d-none">{{ translate('Add variable mappings for this target (optional). Leave value empty to use JSON path.') }}</div>
                            <div class="target-vars evo-block d-none" data-rule="{{ $i }}" data-target="{{ $ti }}">
                              @foreach($tVars as $tvi => $tv)
                              <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                  <label class="form-label">{{ translate('Variable Name') }}</label>
                                  <input class="form-control" name="rules[{{ $i }}][action][targets][{{ $ti }}][variables][{{ $tvi }}][name]" value="{{ $tv['name'] ?? '' }}" />
                                </div>
                                <div class="col-md-4">
                                  <label class="form-label">{{ translate('JSON Path') }}</label>
                                  <input class="form-control" name="rules[{{ $i }}][action][targets][{{ $ti }}][variables][{{ $tvi }}][path]" value="{{ $tv['path'] ?? '' }}" />
                                </div>
                                <div class="col-md-3">
                                  <label class="form-label">{{ translate('Static Value (optional)') }}</label>
                                  <input class="form-control" name="rules[{{ $i }}][action][targets][{{ $ti }}][variables][{{ $tvi }}][value]" value="{{ $tv['value'] ?? '' }}" />
                                </div>
                                <div class="col-md-1">
                                  <button type="button" class="i-btn btn--danger btn--sm remove-target-var">&times;</button>
                                </div>
                              </div>
                              @endforeach
                            </div>
                            <button type="button" class="i-btn btn--sm btn--light add-target-var evo-block d-none" data-rule="{{ $i }}" data-target="{{ $ti }}">{{ translate('Add Target Variable') }}</button>
                          </div>
                          <div class="col-md-1">
                            <button type="button" class="i-btn btn--danger btn--sm remove-target">&times;</button>
                          </div>
                        </div>
                        @endforeach
                      </div>
                      <button type="button" class="i-btn btn--sm btn--light add-target" data-idx="{{ $i }}">{{ translate('Add Target') }}</button>
                      <div class="text-muted small mt-2">{{ translate('Add one or more targets. Each target has Method, Gateway and Template. Messages will rotate across targets.') }}</div>
                    </div>
                    
                    <div class="col-12"><hr /></div>
                    <div class="col-12">
                      <label class="form-label">{{ translate('Variables Mapping (Rule)') }}</label>
                      @php $ruleVars = collect(($act['variables'] ?? []))->values(); @endphp
                      <div class="rule-vars-wrap" data-idx="{{ $i }}">
                        @foreach($ruleVars as $rvi => $var)
                        <div class="row g-2 align-items-end mb-2">
                          <div class="col-md-4">
                            <label class="form-label">{{ translate('Variable Name') }}</label>
                            <input class="form-control" name="rules[{{ $i }}][variables][{{ $rvi }}][name]" value="{{ $var['name'] ?? '' }}" />
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">{{ translate('JSON Path') }}</label>
                            <input class="form-control" name="rules[{{ $i }}][variables][{{ $rvi }}][path]" value="{{ $var['path'] ?? '' }}" />
                          </div>
                          <div class="col-md-2">
                            <button type="button" class="i-btn btn--danger btn--sm remove-rule-var">&times;</button>
                          </div>
                        </div>
                        @endforeach
                      </div>
                      <button type="button" class="i-btn btn--sm btn--light mt-2 add-rule-var" data-idx="{{ $i }}">{{ translate('Add Variable') }}</button>
                      <div class="text-muted small mt-2">{{ translate('Rule variables override defaults by name; new ones are appended.') }}</div>
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
        // Cloud templates may be populated via existing UI elsewhere
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
      let html = `<option value=\"\">${placeholder || '{{ translate('Select') }}'}</option>`;
      items.forEach(function(it){ html += `<option value=\"${it.id}\">${it.name}</option>`; });
      selectEl.innerHTML = html;
      if (items.some(it => String(it.id) === String(cur))) {
        selectEl.value = String(cur);
      }
      if (window.jQuery && jQuery().select2) { jQuery(selectEl).trigger('change.select2'); }
    }
    function updateTargetRow(row){
      const methodSel = row.querySelector('select.target-method');
      const gwSel = row.querySelector('select.target-gateway');
      const tplSel = row.querySelector('select.target-template');
      const cloudParams = row.querySelector('.cloud-template-params');
      const cloudHelp = row.querySelector('.cloud-help');
      const evoVars = row.querySelector('.target-vars');
      const evoAddBtn = row.querySelector('.add-target-var');
      const evoHelp = row.querySelector('.evo-help');
      if(!methodSel || !gwSel || !tplSel) return;
      const method = methodSel.value;
      rebuildOptions(gwSel, METHOD_GATEWAYS[method] || [], '{{ translate('Select gateway') }}');
      if(method === 'cloud_api'){
        cloudParams && cloudParams.classList.remove('d-none');
        cloudHelp && cloudHelp.classList.remove('d-none');
        evoVars && evoVars.classList.add('d-none');
        evoAddBtn && evoAddBtn.classList.add('d-none');
        evoHelp && evoHelp.classList.add('d-none');
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
        cloudParams && cloudParams.classList.add('d-none');
        cloudHelp && cloudHelp.classList.add('d-none');
        evoVars && evoVars.classList.remove('d-none');
        evoAddBtn && evoAddBtn.classList.remove('d-none');
        evoHelp && evoHelp.classList.remove('d-none');
        rebuildOptions(tplSel, METHOD_TEMPLATES[method] || [], '{{ translate('Select template') }}');
      }
    }
    function row(i){
      return `
      <div class=\"border rounded p-3 mb-3\">
        <div class=\"d-flex justify-content-between align-items-center mb-2\">
          <span class=\"badge bg-secondary\">{{ translate('Rule') }} #${i + 1}</span>
          <button class=\"i-btn btn--danger btn--sm remove-rule\" type=\"button\">&times;</button>
        </div>
        <div class=\"row g-3\">
          <div class=\"col-md-3\">
            <label class=\"form-label\">{{ translate('Rule Name') }}</label>
            <input class=\"form-control\" name=\"rules[${i}][name]\" required />
          </div>
          <div class=\"col-md-3\">
            <label class=\"form-label\">{{ translate('Match Path') }}</label>
            <input class=\"form-control\" name=\"rules[${i}][match_path]\" placeholder=\"order.category_id\" required />
          </div>
          <div class=\"col-md-2\">
            <label class=\"form-label\">{{ translate('Operator') }}</label>
            <select class=\"form-select\" name=\"rules[${i}][operator]\">\n              <option value=\"equals\">equals</option>\n              <option value=\"in\">in</option>\n              <option value=\"not_equals\">not_equals</option>\n              <option value=\"exists\">exists</option>\n            </select>
          </div>
          <div class=\"col-md-4\">
            <label class=\"form-label\">{{ translate('Value') }}</label>
            <input class=\"form-control\" name=\"rules[${i}][value]\" />
          </div>
          <div class=\"col-12\"><hr /></div>
          <div class=\"col-md-3\">
            <label class=\"form-label\">{{ translate('Action Method') }}</label>
            <select class=\"form-select\" name=\"rules[${i}][action][method]\">\n              <option value=\"cloud_api\">{{ translate('Meta Cloud Official') }}</option>\n              <option value=\"evolution_api\">{{ translate('Evolution API') }}</option>\n            </select>
          </div>
          <div class=\"col-md-3 action-cloud d-none\">
            <label class=\"form-label\">{{ translate('Cloud Gateways') }}</label>
            <select class=\"form-select select2-search\" name=\"rules[${i}][action][cloud_gateway_ids][]\" multiple>\n              @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::CLOUD->value) as $g)\n                <option value=\"{{ $g->id }}\">{{ $g->name }}</option>\n              @endforeach\n            </select>
          </div>
          <div class=\"col-md-3 action-cloud d-none\">
            <label class=\"form-label\">{{ translate('Cloud Template') }}</label>
            <select class=\"form-select select2-search\" name=\"rules[${i}][action][cloud_template_id]\">\n              <option value=\"\">{{ translate('Select') }}</option>\n            </select>
          </div>
          <div class=\"col-md-3 action-evo d-none\">
            <label class=\"form-label\">{{ translate('Evolution Gateways') }}</label>
            <select class=\"form-select select2-search\" name=\"rules[${i}][action][evolution_gateway_ids][]\" multiple>\n              @foreach($cloudGateways->where('type', \App\Enums\System\Gateway\WhatsAppGatewayTypeEnum::EVOLUTION->value) as $g)\n                <option value=\"{{ $g->id }}\">{{ $g->name }}</option>\n              @endforeach\n            </select>
          </div>
          <div class=\"col-md-3 action-evo d-none\">
            <label class=\"form-label\">{{ translate('Evolution Template') }}</label>
            <select class=\"form-select select2-search\" name=\"rules[${i}][action][evolution_template_id]\">\n              <option value=\"\">{{ translate('Select') }}</option>\n              @isset($evolutionTemplates)\n                @foreach($evolutionTemplates as $tpl)\n                  <option value=\"{{ $tpl->id }}\">{{ $tpl->name }}</option>\n                @endforeach\n              @endisset\n            </select>
          </div>
          <div class=\"col-md-2\">
            <label class=\"form-label\">{{ translate('Priority') }}</label>
            <input type=\"number\" class=\"form-control\" name=\"rules[${i}][priority]\" value=\"100\" />
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
      if(e.target && e.target.classList.contains('target-template')){
        const row = e.target.closest('.row');
        const methodSel = row.querySelector('select.target-method');
        const tplSel = row.querySelector('select.target-template');
        const cloudParams = row.querySelector('.cloud-template-params');
        if(methodSel && methodSel.value === 'cloud_api' && window.jQuery && cloudParams){
          const tplId = tplSel.value;
          if(!tplId){ cloudParams.innerHTML=''; return; }
          const url = "{{ route('user.template.get', ['uid' => 'UID_PLACEHOLDER']) }}".replace('UID_PLACEHOLDER', tplId);
          jQuery.get(url).done(function(data){
            try {
              const components = (data && data.components) ? data.components : [];
              const body = components.find(c=>c.type==='BODY');
              let bodyCount = 0;
              if(body && body.text){
                const matches = String(body.text).match(/\{\{\d+\}\}/g);
                bodyCount = matches ? matches.length : 0;
              }
              const btnContainer = components.find(c=>c.type==='BUTTONS');
              const urlButtons = [];
              if(btnContainer && Array.isArray(btnContainer.buttons)){
                btnContainer.buttons.forEach((b, idx)=>{
                  if(b.type==='URL'){
                    const m = (b.url||'').match(/\{\{\d+\}\}/g);
                    const pc = m ? m.length : 1;
                    urlButtons.push({ index: idx, count: pc });
                  }
                });
              }
              let html = '';
              if(bodyCount>0){
                html += `<div class=\"mb-2\"><strong>{{ translate('BODY parameters') }}</strong></div>`;
                for(let i=1;i<=bodyCount;i++){
                  html += `
                  <div class=\"row g-2 align-items-end mb-2\">
                    <div class=\"col-md-3\"><label class=\"form-label\">{{ translate('Param') }} #${i}</label>
                      <select class=\"form-select\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][body][${i-1}][source]\">
                        <option value=\"static\">{{ translate('Static') }}</option>
                        <option value=\"path\">{{ translate('JSON Path') }}</option>
                      </select>
                    </div>
                    <div class=\"col-md-5\"><label class=\"form-label\">{{ translate('Value') }}</label>
                      <input class=\"form-control\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][body][${i-1}][value]\" placeholder=\"STATIC or order.customer.name\" />
                    </div>
                  </div>`;
                }
              }
              if(urlButtons.length){
                html += `<div class=\"mb-2\"><strong>{{ translate('BUTTON URL parameters') }}</strong></div>`;
                urlButtons.forEach((btn, bi)=>{
                  for(let j=1;j<=btn.count;j++){
                    html += `
                    <div class=\"row g-2 align-items-end mb-2\">
                      <input type=\"hidden\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][buttons][${bi}][index]\" value=\"${btn.index}\">
                      <input type=\"hidden\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][buttons][${bi}][sub_type]\" value=\"URL\">
                      <div class=\"col-md-3\"><label class=\"form-label\">{{ translate('Button') }} #${btn.index} {{ translate('Param') }} #${j}</label>
                        <select class=\"form-select\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][buttons][${bi}][parameters][${j-1}][source]\">
                          <option value=\"static\">{{ translate('Static') }}</option>
                          <option value=\"path\">{{ translate('JSON Path') }}</option>
                        </select>
                      </div>
                      <div class=\"col-md-5\"><label class=\"form-label\">{{ translate('Value') }}</label>
                        <input class=\"form-control\" name=\"rules[${row.closest('[data-idx]')?.getAttribute('data-idx')||0}][action][targets][${row.querySelector('select.target-template').name.match(/targets\]\[(\d+)\]/)[1]}][template_params][buttons][${bi}][parameters][${j-1}][value]\" placeholder=\"STATIC or order.id\" />
                      </div>
                    </div>`;
                  }
                });
              }
              cloudParams.innerHTML = html || `<div class=\"text-muted\">{{ translate('No parameters in this template') }}</div>`;
            } catch(e){ cloudParams.innerHTML = `<div class=\"text-danger\">{{ translate('Failed to load template parameters') }}</div>`; }
          }).fail(function(){ cloudParams.innerHTML = `<div class=\"text-danger\">{{ translate('Failed to load template') }}</div>`; });
        }
      }
    });
    // Gate gateway selects by allowed methods
    function toggleGatewaySelects(){
      document.querySelectorAll('select[data-related-method]').forEach(function(sel){
        const methodCheckbox = document.getElementById(sel.getAttribute('data-related-method'));
        const enabled = !!(methodCheckbox && methodCheckbox.checked);
        sel.disabled = !enabled;
        sel.classList.toggle('disabled', !enabled);
      });
    }
    document.getElementById('m_cloud').addEventListener('change', toggleGatewaySelects);
    document.getElementById('m_evo').addEventListener('change', toggleGatewaySelects);
    toggleGatewaySelects();
    // Init select2 with placeholder and chips
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
    // Select All / Clear handlers
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('btn-select-all')){
        const sel = document.querySelector(e.target.getAttribute('data-target'));
        if(!sel) return;
        Array.from(sel.options).forEach(o=>o.selected=true);
        if(window.jQuery && jQuery().select2){ jQuery(sel).trigger('change'); }
      }
      if(e.target && e.target.classList.contains('btn-clear-all')){
        const sel = document.querySelector(e.target.getAttribute('data-target'));
        if(!sel) return;
        Array.from(sel.options).forEach(o=>o.selected=false);
        if(window.jQuery && jQuery().select2){ jQuery(sel).val(null).trigger('change'); }
      }
    });
    // Copy webhook URL
    document.getElementById('copy-webhook').addEventListener('click', function(){
      const text = document.getElementById('webhook-url').innerText.trim();
      if(navigator.clipboard){
        navigator.clipboard.writeText(text);
      } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
      }
    });
    // Default variables repeater (edit)
    let defaultVarIdx = {{ isset($defaultVars) ? $defaultVars->count() : 0 }};
    function renderDefaultVarRow(i){
      return `
      <div class=\"row g-2 align-items-end mb-2\">
        <div class=\"col-md-4\">
          <label class=\"form-label\">{{ translate('Variable Name') }}</label>
          <input class=\"form-control\" name=\"defaults[variables][${i}][name]\" placeholder=\"order_id\" />
        </div>
        <div class=\"col-md-6\">
          <label class=\"form-label\">{{ translate('JSON Path') }}</label>
          <input class=\"form-control\" name=\"defaults[variables][${i}][path]\" placeholder=\"order_id\" />
        </div>
        <div class=\"col-md-2\">
          <button type=\"button\" class=\"i-btn btn--danger btn--sm remove-default-var\">&times;</button>
        </div>
      </div>`;
    }
    const addDefaultVarBtn = document.getElementById('add-default-var');
    if(addDefaultVarBtn){
      addDefaultVarBtn.addEventListener('click', function(){
        document.getElementById('default-vars-wrap').insertAdjacentHTML('beforeend', renderDefaultVarRow(defaultVarIdx++));
      });
    }
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('remove-default-var')){
        e.target.closest('.row').remove();
      }
    });

    // Rule variables repeater (edit)
    const ruleVarCounters = {};
    document.querySelectorAll('.rule-vars-wrap').forEach(function(wrap){
      const idx = wrap.getAttribute('data-idx');
      const count = wrap.querySelectorAll('.row.g-2').length;
      ruleVarCounters[idx] = count;
    });

    // Targets repeater (edit)
    function renderTargetRow(idx, tIdx){
      return `
      <div class=\"row g-2 align-items-end mb-2\">\n        <div class=\"col-md-3\">\n          <label class=\"form-label\">{{ translate('Method') }}</label>\n          <select class=\"form-select target-method\" name=\"rules[${idx}][action][targets][${tIdx}][method]\">\n            <option value=\"cloud_api\">{{ translate('Meta Cloud Official') }}</option>\n            <option value=\"evolution_api\">{{ translate('Evolution API') }}</option>\n          </select>\n        </div>\n        <div class=\"col-md-4\">\n          <label class=\"form-label\">{{ translate('Gateway') }}</label>\n          <select class=\"form-select select2-search target-gateway\" name=\"rules[${idx}][action][targets][${tIdx}][gateway_id]\">\n            <option value=\"\">{{ translate('Select') }}</option>\n            @foreach($cloudGateways as $g)\n              <option data-type=\"{{ $g->type }}\" value=\"{{ $g->id }}\">{{ $g->name }}</option>\n            @endforeach\n          </select>\n        </div>\n        <div class=\"col-md-4\">\n          <label class=\"form-label\">{{ translate('Template') }}</label>\n          <select class=\"form-select select2-search target-template\" name=\"rules[${idx}][action][targets][${tIdx}][template_id]\">\n            <option value=\"\">{{ translate('Select (Cloud/Evolution)') }}</option>\n            @isset($evolutionTemplates)\n              @foreach($evolutionTemplates as $tpl)\n                <option data-method=\"evolution_api\" value=\"{{ $tpl->id }}\">{{ $tpl->name }}</option>\n              @endforeach\n            @endisset\n          </select>\n        </div>\n        <div class=\"col-md-1\">\n          <button type=\"button\" class=\"i-btn btn--danger btn--sm remove-target\">&times;</button>\n        </div>\n      </div>`;
    }
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('add-target')){
        const idx = e.target.getAttribute('data-idx');
        const wrap = document.querySelector(`.targets-wrap[data-idx="${idx}"]`);
        const count = wrap ? wrap.querySelectorAll('.row.g-2').length : 0;
        if(wrap){ 
          wrap.insertAdjacentHTML('beforeend', renderTargetRow(idx, count)); 
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
    // Initialize existing target rows with correct options
    document.querySelectorAll('.targets-wrap').forEach(function(wrap){
      wrap.querySelectorAll('.row.g-2').forEach(function(row){ updateTargetRow(row); });
    });
    function renderRuleVarRow(idx, i){
      return `
      <div class=\"row g-2 align-items-end mb-2\">
        <div class=\"col-md-4\">
          <label class=\"form-label\">{{ translate('Variable Name') }}</label>
          <input class=\"form-control\" name=\"rules[${idx}][variables][${i}][name]\" placeholder=\"order_id\" />
        </div>
        <div class=\"col-md-6\">
          <label class=\"form-label\">{{ translate('JSON Path') }}</label>
          <input class=\"form-control\" name=\"rules[${idx}][variables][${i}][path]\" placeholder=\"order_id\" />
        </div>
        <div class=\"col-md-2\">
          <button type=\"button\" class=\"i-btn btn--danger btn--sm remove-rule-var\">&times;</button>
        </div>
      </div>`;
    }
    document.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('add-rule-var')){
        const idx = e.target.getAttribute('data-idx');
        ruleVarCounters[idx] = (ruleVarCounters[idx] || 0) + 1;
        const wrap = document.querySelector(`.rule-vars-wrap[data-idx=\\"${idx}\\"]`);
        if(wrap){ wrap.insertAdjacentHTML('beforeend', renderRuleVarRow(idx, ruleVarCounters[idx]-1)); }
      }
      if(e.target && e.target.classList.contains('remove-rule-var')){
        e.target.closest('.row').remove();
      }
    });
  })();
</script>
@endpush

@endsection



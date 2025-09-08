@extends('user.layouts.app')
@section('panel')
<main class="main-body">
  <div class="container-fluid px-0 main-content">
    <div class="page-header">
      <div class="page-header-left">
        <h2>{{ $title }}</h2>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-header-left">
          <h4 class="card-title">{{ $title }}</h4>
        </div>
      </div>
      <div class="card-body">
        <form action="{{ route('user.template.whatsapp.evolution.store') }}" method="POST" id="evoTplForm">
          @csrf
          <div class="row g-4">
            <div class="col-lg-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Template Name') }}<span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="{{ translate('Enter template name') }}">
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Type') }}<span class="text-danger">*</span></label>
                <select class="form-select" name="type" id="tplType" required>
                  <option value="" disabled selected>{{ translate('Select type') }}</option>
                  <option value="simple_txt">{{ translate('Simple Text') }}</option>
                  <option value="image">{{ translate('Image with text') }}</option>
                  <option value="poll">{{ translate('Poll') }}</option>
                  <option value="list_buttons">{{ translate('List Buttons') }}</option>
                </select>
              </div>
            </div>

            <div id="typeFields"></div>

            <div class="col-12">
              <div class="form-action justify-content-end">
                <a href="{{ route('user.template.whatsapp.evolution.index') }}" class="i-btn btn--danger outline btn--md">{{ translate('Cancel') }}</a>
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
  const typeEl = document.getElementById('tplType');
  const container = document.getElementById('typeFields');

  function html(strings){ return strings[0]; }

  function render(type){
    container.innerHTML = '';
    if(!type) return;

    if(type === 'simple_txt'){
      container.innerHTML = html`
        <div class="col-12">
          <div class="form-inner">
            <label class="form-label">{{ translate('Text') }}<span class="text-danger">*</span></label>
            <textarea class="form-control" name="text" rows="4" placeholder="{{ translate('Message text') }}" required></textarea>
          </div>
        </div>`;
      return;
    }

    if(type === 'image'){
      container.innerHTML = html`
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Media URL') }}<span class="text-danger">*</span></label>
            <input type="url" class="form-control" name="media" placeholder="https://..." required>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="form-inner">
            <label class="form-label">{{ translate('File Name') }}</label>
            <input type="text" class="form-control" name="fileName" placeholder="image.jpg">
          </div>
        </div>
        <div class="col-lg-3">
          <div class="form-inner">
            <label class="form-label">{{ translate('Caption') }}</label>
            <input type="text" class="form-control" name="caption" placeholder="{{ translate('Optional caption') }}">
          </div>
        </div>`;
      return;
    }

    if(type === 'poll'){
      container.innerHTML = html`
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Poll Name') }}<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name_poll" required>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Selectable Count') }}<span class="text-danger">*</span></label>
            <input type="number" min="1" class="form-control" name="selectableCount" required>
          </div>
        </div>
        <div class="col-12">
          <div class="form-inner">
            <label class="form-label">{{ translate('Values') }}<span class="text-danger">*</span></label>
            <div id="pollValues"></div>
            <button type="button" class="i-btn btn--sm btn--primary mt-2" id="addPollValue"><i class="ri-add-fill"></i> {{ translate('Add value') }}</button>
          </div>
        </div>`;
      setTimeout(() => setupPollValues(), 0);
      return;
    }

    if(type === 'list_buttons'){
      container.innerHTML = html`
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Title') }}<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" required>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Description') }}</label>
            <input type="text" class="form-control" name="description">
          </div>
        </div>
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Button Text') }}<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="buttonText" required>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Footer Text') }}</label>
            <input type="text" class="form-control" name="footerText">
          </div>
        </div>
        <div class="col-12">
          <div class="form-inner">
            <label class="form-label">{{ translate('Sections') }}<span class="text-danger">*</span></label>
            <div id="sections"></div>
            <button type="button" class="i-btn btn--sm btn--primary mt-2" id="addSection"><i class="ri-add-fill"></i> {{ translate('Add section') }}</button>
          </div>
        </div>`;
      setTimeout(() => setupSections(), 0);
      return;
    }
  }

  function setupPollValues(){
    const wrap = document.getElementById('pollValues');
    const addBtn = document.getElementById('addPollValue');
    function addRow(val=''){
      const div = document.createElement('div');
      div.className = 'd-flex align-items-center gap-2 mb-2';
      div.innerHTML = `
        <input type="text" class="form-control" name="values[]" value="${val}" required>
        <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle remove"><i class="ri-close-line"></i></button>`;
      div.querySelector('.remove').onclick = () => div.remove();
      wrap.appendChild(div);
    }
    addBtn.onclick = () => addRow('');
    if(!wrap.children.length) addRow('');
  }

  function setupSections(){
    const wrap = document.getElementById('sections');
    const addBtn = document.getElementById('addSection');

    function addRow(rowData={}){
      const rowDiv = document.createElement('div');
      rowDiv.className = 'border rounded p-3 mb-3';
      const uid = 'r'+Math.random().toString(36).slice(2);
      rowDiv.innerHTML = `
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <div class="form-inner">
              <label class="form-label">rowId<span class="text-danger">*</span></label>
              <input type="text" class="form-control" placeholder="rowId" name="rowId" value="${rowData.rowId||''}" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-inner">
              <label class="form-label">${'{{ translate('Title') }}'}</label>
              <input type="text" class="form-control" placeholder="title" name="rowTitle" value="${rowData.title||''}" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-inner">
              <label class="form-label">${'{{ translate('Description') }}'}</label>
              <input type="text" class="form-control" placeholder="description" name="rowDesc" value="${rowData.description||''}">
            </div>
          </div>
          <div class="col-md-1 text-end">
            <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle remove" title="${'{{ translate('Remove') }}'}"><i class="ri-close-line"></i></button>
          </div>
        </div>
        <div class="mt-3 border rounded p-2">
          <div class="d-flex justify-content-between align-items-center">
            <strong>${'{{ translate('HTTP Action') }}'}</strong>
            <div class="form-check form-switch m-0">
              <input class="form-check-input toggleActionSwitch" type="checkbox" name="actionEnabled" id="sw_${uid}">
              <label class="form-check-label" for="sw_${uid}">${'{{ translate('Enable') }}'}</label>
            </div>
          </div>
          <div class="actionWrap mt-2" style="display:none;">
            <div class="row g-2">
              <div class="col-md-2">
                <label class="form-label">${'{{ translate('Method') }}'}</label>
                <select class="form-select" name="actionMethod">
                  <option>GET</option>
                  <option>POST</option>
                  <option>PUT</option>
                  <option>PATCH</option>
                  <option>DELETE</option>
                </select>
              </div>
              <div class="col-md-10">
                <label class="form-label">URL</label>
                <input type="url" class="form-control" name="actionUrl" placeholder="https://example.com/webhook">
              </div>
              <div class="col-md-6">
                <label class="form-label mb-1">${'{{ translate('Headers (JSON)') }}'}</label>
                <textarea class="form-control" id="headers_${uid}" name="actionHeaders" rows="2" placeholder='{"Authorization":"Bearer ..."}'></textarea>
                <div class="text-danger small mt-1" data-err-for="headers_${uid}" style="display:none;">${'{{ translate('Invalid JSON') }}'}</div>
              </div>
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center">
                  <label class="form-label mb-1 m-0">${'{{ translate('Body (JSON)') }}'}</label>
                  <div class="d-flex align-items-center gap-2">
                    <label class="form-label m-0">${'{{ translate('Insert variable') }}'}:</label>
                    <select class="form-select form-select-sm" id="vars_${uid}" style="width:auto;">
                      <option value="">${'{{ translate('Choose') }}'}</option>
                      <option data-type="placeholder" data-path="selectedRowId">selectedRowId</option>
                      <option data-type="placeholder" data-path="sender">sender</option>
                      <option data-type="placeholder" data-path="customer">customer</option>
                      <option data-type="placeholder" data-path="instance">instance</option>
                      <option data-type="placeholder" data-path="gateway_id">gateway_id</option>
                      <option data-type="placeholder" data-path="user_id">user_id</option>
                      <option data-type="placeholder" data-path="webhook_payload.order_id">webhook_payload.order_id</option>
                      <option data-type="path" data-path="webhook_payload.order_id">$path: webhook_payload.order_id</option>
                    </select>
                  </div>
                </div>
                <textarea class="form-control" id="body_${uid}" name="actionBody" rows="2" placeholder='{"foo":"bar"}'></textarea>
                <div class="text-danger small mt-1" data-err-for="body_${uid}" style="display:none;">${'{{ translate('Invalid JSON') }}'}</div>
                <div class="text-muted small mt-2">
                  <strong>${'{{ translate('Mapping guide') }}'}:</strong>
                  <div>${'{{ translate('Use placeholders to pull values from context:') }}'} <code>@{{ webhook_payload.order_id }}</code>, <code>@{{ sender }}</code>, <code>@{{ customer }}</code></div>
                  <div>${'{{ translate('With default') }}'}: <code>@{{ webhook_payload.note || none }}</code></div>
                  <div>${'{{ translate('Path directive') }}'}: <code>{"$path":"webhook_payload.order_id"}</code></div>
                </div>
              </div>
            </div>
          </div>
        </div>`;
      rowDiv.querySelector('.remove').onclick = () => rowDiv.remove();
      const actionSwitch = rowDiv.querySelector('.toggleActionSwitch');
      const aw = rowDiv.querySelector('.actionWrap');
      actionSwitch.addEventListener('change', function(){
        aw.style.display = this.checked ? '' : 'none';
      });
      // JSON validation
      const headersEl = rowDiv.querySelector(`#headers_${uid}`);
      const bodyEl = rowDiv.querySelector(`#body_${uid}`);
      const hErr = rowDiv.querySelector(`[data-err-for="headers_${uid}"]`);
      const bErr = rowDiv.querySelector(`[data-err-for="body_${uid}"]`);
      function validateJson(el, errEl){
        const val = (el.value||'').trim();
        if(!val){ el.classList.remove('is-invalid'); errEl.style.display='none'; return; }
        try{ JSON.parse(val); el.classList.remove('is-invalid'); errEl.style.display='none'; }
        catch(e){ el.classList.add('is-invalid'); errEl.style.display=''; }
      }
      headersEl.addEventListener('input', ()=>validateJson(headersEl,hErr));
      bodyEl.addEventListener('input', ()=>validateJson(bodyEl,bErr));
      // Variable inserter
      const varsEl = rowDiv.querySelector(`#vars_${uid}`);
      varsEl.addEventListener('change', function(){
        const opt = this.options[this.selectedIndex];
        const type = opt.getAttribute('data-type');
        const path = opt.getAttribute('data-path');
        if(!type || !path) return;
        const v = type === 'path' ? '{"$path":"'+path+'"}' : '{{ '+path+' }}';
        const ta = bodyEl;
        const start = ta.selectionStart||0, end = ta.selectionEnd||0;
        ta.value = (ta.value||'').slice(0,start) + v + (ta.value||'').slice(end);
        ta.focus(); ta.selectionStart = ta.selectionEnd = start + v.length;
        this.value='';
        bodyEl.dispatchEvent(new Event('input'));
      });
      return rowDiv;
    }

    function addSection(secData={}){
      const sec = document.createElement('div');
      sec.className = 'border rounded p-3 mb-3';
      sec.innerHTML = `
        <div class="form-inner mb-2">
          <label class="form-label">{{ translate('Section Title') }}</label>
          <input type="text" class="form-control" name="sections_title[]" value="${secData.title||''}">
        </div>
        <div class="mb-2">
          <div class="d-flex justify-content-between align-items-center">
            <strong>{{ translate('Rows') }}</strong>
            <button type="button" class="i-btn btn--sm btn--primary addRow">{{ translate('Add row') }}</button>
          </div>
          <div class="rows"></div>
        </div>
        <div class="text-end">
          <button type="button" class="i-btn btn--sm btn--danger outline removeSection">{{ translate('Remove section') }}</button>
        </div>`;
      const rowsWrap = sec.querySelector('.rows');
      sec.querySelector('.addRow').onclick = () => {
        rowsWrap.appendChild(addRow({}));
        serialize();
      };
      sec.querySelector('.removeSection').onclick = () => { sec.remove(); serialize(); };
      wrap.appendChild(sec);
    }

    addBtn.onclick = () => { addSection({}); serialize(); };

    function serialize(){
      document.querySelectorAll('.sectionsHidden').forEach(e => e.remove());
      const sections = [];
      const rowActions = {};
      wrap.querySelectorAll('.border.rounded.p-3').forEach((secEl, i) => {
        const title = secEl.querySelector('input[name="sections_title[]"]').value || '';
        const rows = [];
        secEl.querySelectorAll('.rows > div').forEach(row => {
          const rowId = row.querySelector('input[name="rowId"]').value;
          const rowTitle = row.querySelector('input[name="rowTitle"]').value;
          const rowDesc  = row.querySelector('input[name="rowDesc"]').value;
          const actWrap = row.querySelector('.actionWrap');
          const enabledSwitch = row.querySelector('input[name="actionEnabled"]');
          const isEnabled = enabledSwitch ? !!enabledSwitch.checked : false;
          const method = (row.querySelector('select[name="actionMethod"]').value || 'GET').toUpperCase();
          const url    = (row.querySelector('input[name="actionUrl"]').value || '').trim();
          const headersTxt = row.querySelector('textarea[name="actionHeaders"]').value || '';
          const bodyTxt    = row.querySelector('textarea[name="actionBody"]').value || '';
          if(rowId && rowTitle){
            rows.push({ rowId: rowId, title: rowTitle, description: rowDesc || null });
            if(isEnabled && url){
              let headers = null, body = null;
              try { headers = headersTxt ? JSON.parse(headersTxt) : null; } catch(e) { headers = null; }
              try { body = bodyTxt ? JSON.parse(bodyTxt) : null; } catch(e) { body = null; }
              rowActions[rowId] = { enabled: true, method, url, headers, body };
            }
          }
        });
        if(rows.length) sections.push({ title: title || null, rows: rows });
      });
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'sections_json';
      input.className = 'sectionsHidden';
      input.value = JSON.stringify(sections);
      document.getElementById('evoTplForm').appendChild(input);

      const input2 = document.createElement('input');
      input2.type = 'hidden';
      input2.name = 'row_actions_json';
      input2.className = 'sectionsHidden';
      input2.value = JSON.stringify(rowActions);
      document.getElementById('evoTplForm').appendChild(input2);
    }

    wrap.addEventListener('input', serialize);
    wrap.addEventListener('click', serialize);
  }

  typeEl.addEventListener('change', function(){ render(this.value); });

  document.getElementById('evoTplForm').addEventListener('submit', function(){
    const hidden = this.querySelector('input[name="sections_json"]');
    if(hidden){
      try {
        const data = JSON.parse(hidden.value || '[]');
        data.forEach((s, i) => {
          const t = document.createElement('input');
          t.type = 'hidden';
          t.name = `sections[${i}][title]`;
          t.value = s.title ?? '';
          this.appendChild(t);
          s.rows.forEach((r, j) => {
            ['rowId','title','description'].forEach(k => {
              const inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = `sections[${i}][rows][${j}][${k}]`;
              inp.value = r[k] ?? '';
              this.appendChild(inp);
            });
          });
        });
        hidden.remove();
      } catch(e){}
    }
  });

})();
</script>
@endpush
@endsection



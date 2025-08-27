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
        <form action="{{ route('user.template.whatsapp.evolution.update', $template->uid) }}" method="POST" id="evoTplForm">
          @csrf
          @method('PATCH')
          <div class="row g-4">
            <div class="col-lg-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Template Name') }}<span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="{{ $template->name }}">
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-inner">
                <label class="form-label">{{ translate('Type') }}<span class="text-danger">*</span></label>
                <select class="form-select" name="type" id="tplType" required>
                  <option value="simple_txt" {{ $template->type->value == 'simple_txt' ? 'selected' : '' }}>{{ translate('Simple Text') }}</option>
                  <option value="image" {{ $template->type->value == 'image' ? 'selected' : '' }}>{{ translate('Image with text') }}</option>
                  <option value="poll" {{ $template->type->value == 'poll' ? 'selected' : '' }}>{{ translate('Poll') }}</option>
                  <option value="list_buttons" {{ $template->type->value == 'list_buttons' ? 'selected' : '' }}>{{ translate('List Buttons') }}</option>
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
  const preset = @json($template->payload);

  function html(strings){ return strings[0]; }

  function render(type){
    container.innerHTML = '';
    if(!type) return;

    if(type === 'simple_txt'){
      container.innerHTML = html`
        <div class="col-12">
          <div class="form-inner">
            <label class="form-label">{{ translate('Text') }}<span class="text-danger">*</span></label>
            <textarea class="form-control" name="text" rows="4" required></textarea>
          </div>
        </div>`;
      container.querySelector('textarea[name="text"]').value = preset?.text || '';
      return;
    }

    if(type === 'image'){
      container.innerHTML = html`
        <div class="col-lg-6">
          <div class="form-inner">
            <label class="form-label">{{ translate('Media URL') }}<span class="text-danger">*</span></label>
            <input type="url" class="form-control" name="media" required>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="form-inner">
            <label class="form-label">{{ translate('File Name') }}</label>
            <input type="text" class="form-control" name="fileName">
          </div>
        </div>
        <div class="col-lg-3">
          <div class="form-inner">
            <label class="form-label">{{ translate('Caption') }}</label>
            <input type="text" class="form-control" name="caption">
          </div>
        </div>`;
      container.querySelector('input[name="media"]').value = preset?.media || '';
      container.querySelector('input[name="fileName"]').value = preset?.fileName || '';
      container.querySelector('input[name="caption"]').value = preset?.caption || '';
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
      setTimeout(() => setupPollValues(preset?.values || []), 0);
      container.querySelector('input[name="name_poll"]').value = preset?.name || '';
      container.querySelector('input[name="selectableCount"]').value = preset?.selectableCount || 1;
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
      setTimeout(() => setupSections(preset?.sections || []), 0);
      container.querySelector('input[name="title"]').value = preset?.title || '';
      container.querySelector('input[name="description"]').value = preset?.description || '';
      container.querySelector('input[name="buttonText"]').value = preset?.buttonText || '';
      container.querySelector('input[name="footerText"]').value = preset?.footerText || '';
      return;
    }
  }

  function setupPollValues(values){
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
    if(values && values.length){ values.forEach(v => addRow(v)); } else { addRow(''); }
  }

  function setupSections(sections){
    const wrap = document.getElementById('sections');
    const addBtn = document.getElementById('addSection');

    function addRow(rowData={}){
      const rowDiv = document.createElement('div');
      rowDiv.className = 'd-flex align-items-center gap-2 mb-2';
      rowDiv.innerHTML = `
        <input type="text" class="form-control" placeholder="rowId" name="rowId" value="${rowData.rowId||''}" required>
        <input type="text" class="form-control" placeholder="title" name="rowTitle" value="${rowData.title||''}" required>
        <input type="text" class="form-control" placeholder="description" name="rowDesc" value="${rowData.description||''}">
        <button type="button" class="icon-btn btn-ghost btn-sm danger-soft circle remove"><i class="ri-close-line"></i></button>`;
      rowDiv.querySelector('.remove').onclick = () => rowDiv.remove();
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
      if(secData.rows){ secData.rows.forEach(r => rowsWrap.appendChild(addRow(r))); }
    }

    addBtn.onclick = () => { addSection({}); serialize(); };

    function serialize(){
      document.querySelectorAll('.sectionsHidden').forEach(e => e.remove());
      const sections = [];
      wrap.querySelectorAll('.border.rounded.p-3').forEach((secEl, i) => {
        const title = secEl.querySelector('input[name="sections_title[]"]').value || '';
        const rows = [];
        secEl.querySelectorAll('.rows > div').forEach(row => {
          const rowId = row.querySelector('input[name="rowId"]').value;
          const rowTitle = row.querySelector('input[name="rowTitle"]').value;
          const rowDesc  = row.querySelector('input[name="rowDesc"]').value;
          if(rowId && rowTitle){
            rows.push({ rowId: rowId, title: rowTitle, description: rowDesc || null });
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
    }

    wrap.addEventListener('input', serialize);
    wrap.addEventListener('click', serialize);

    if(sections && sections.length){ sections.forEach(s => addSection(s)); serialize(); }
  }

  typeEl.addEventListener('change', function(){ render(this.value); });
  render(@json($template->type->value));

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



<?php

namespace App\Http\Controllers\User\Template;

use App\Enums\Common\Status;
use App\Enums\System\EvolutionWhatsappTemplateTypeEnum as TType;
use App\Http\Controllers\Controller;
use App\Models\EvolutionWhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WhatsappEvolutionTemplateController extends Controller
{
    public function index()
    {
        $title = translate('WhatsApp (Evolution API) Templates');
        $templates = EvolutionWhatsappTemplate::where('user_id', auth()->id())
            ->latest()->paginate(paginateNumber());
        return view('user.template.whatsapp.evolution.index', compact('title', 'templates'));
    }

    public function create()
    {
        $title = translate('Create Evolution WhatsApp Template');
        $types = TType::values();
        return view('user.template.whatsapp.evolution.create', compact('title', 'types'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        EvolutionWhatsappTemplate::create([
            'user_id' => auth()->id(),
            'name'    => $data['name'],
            'type'    => $data['type'],
            'payload' => $this->buildPayload($data),
            'status'  => Status::ACTIVE->value,
        ]);
        $notify[] = ['success', translate('Template created successfully')];
        return redirect()->route('user.template.whatsapp.evolution.index')->withNotify($notify);
    }

    public function edit(string $uid)
    {
        $template = EvolutionWhatsappTemplate::where('uid', $uid)->where('user_id', auth()->id())->firstOrFail();
        $title = translate('Edit Evolution WhatsApp Template');
        $types = TType::values();
        return view('user.template.whatsapp.evolution.edit', compact('title', 'types', 'template'));
    }

    public function update(Request $request, string $uid)
    {
        $template = EvolutionWhatsappTemplate::where('uid', $uid)->where('user_id', auth()->id())->firstOrFail();
        $data = $this->validateData($request);
        $template->update([
            'name'    => $data['name'],
            'type'    => $data['type'],
            'payload' => $this->buildPayload($data),
        ]);
        $notify[] = ['success', translate('Template updated successfully')];
        return back()->withNotify($notify);
    }

    public function destroy(string $uid)
    {
        $template = EvolutionWhatsappTemplate::where('uid', $uid)->where('user_id', auth()->id())->firstOrFail();
        $template->delete();
        $notify[] = ['success', translate('Template deleted successfully')];
        return back()->withNotify($notify);
    }

    protected function validateData(Request $request): array
    {
        $typeValues = TType::values();

        $base = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', $typeValues)],
        ]);

        return match ($base['type']) {
            TType::SIMPLE_TXT->value => array_merge($base, $request->validate([
                'text' => ['required','string'],
            ])),
            TType::IMAGE->value => array_merge($base, $request->validate([
                'caption'   => ['nullable','string'],
                'fileName'  => ['nullable','string'],
                'media'     => ['required','url'],
            ])),
            TType::POLL->value => array_merge($base, $request->validate([
                'name_poll'        => ['required','string'],
                'selectableCount'  => ['required','integer','min:1'],
                'values'           => ['required','array','min:1'],
                'values.*'         => ['required','string'],
            ])),
            TType::LIST_BUTTONS->value => array_merge($base, $request->validate([
                'title'       => ['required','string'],
                'description' => ['nullable','string'],
                'buttonText'  => ['required','string'],
                'footerText'  => ['nullable','string'],
                'sections'    => ['required','array','min:1'],
                'sections.*.title' => ['nullable','string'],
                'sections.*.rows'  => ['required','array','min:1'],
                'sections.*.rows.*.rowId' => ['required','string'],
                'sections.*.rows.*.title' => ['required','string'],
                'sections.*.rows.*.description' => ['nullable','string'],
            ])),
            default => $base,
        };
    }

    protected function buildPayload(array $data): array
    {
        return match ($data['type']) {
            TType::SIMPLE_TXT->value => [
                'text' => $data['text'],
            ],
            TType::IMAGE->value => [
                'mediatype' => 'image',
                'caption'   => Arr::get($data, 'caption'),
                'fileName'  => Arr::get($data, 'fileName'),
                'media'     => $data['media'],
            ],
            TType::POLL->value => [
                'name'            => $data['name_poll'],
                'selectableCount' => (int) $data['selectableCount'],
                'values'          => array_values($data['values']),
            ],
            TType::LIST_BUTTONS->value => [
                'type'        => 'list',
                'title'       => $data['title'],
                'description' => Arr::get($data, 'description'),
                'buttonText'  => $data['buttonText'],
                'footerText'  => Arr::get($data, 'footerText'),
                'sections'    => array_values($data['sections']),
            ],
        };
    }
}



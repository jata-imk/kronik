<?php

namespace App\Http\Controllers;

use App\Http\Requests\Communications\SaveNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Services\Communications\NotificationContent;
use App\Services\Communications\NotificationTemplateService;
use App\Services\TemplateHtmlView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('read plantillas-notificaciones');
        $data = $request->validate([
            'plantilla_id' => 'nullable|integer|min:1', 'version_id' => 'nullable|integer|min:1',
            'buscar' => 'nullable|string|max:100', 'channel' => ['nullable', Rule::in(['sms', 'email'])],
            'event_type' => ['nullable', Rule::in(['payment_received', 'upcoming_due'])],
            'page' => 'nullable|integer|min:1', 'versiones_page' => 'nullable|integer|min:1',
            'modo' => ['nullable', Rule::in(['consulta', 'edicion'])],
        ]);
        $page = NotificationTemplate::with('activeVersion')->withCount('versions')
            ->when($data['buscar'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('key', 'like', '%'.$search.'%')))
            ->when($data['channel'] ?? null, fn ($q, $v) => $q->where('channel', $v))
            ->when($data['event_type'] ?? null, fn ($q, $v) => $q->where('event_type', $v))
            ->orderBy('name')->orderBy('id')->paginate(10)->withQueryString();
        $id = $data['plantilla_id'] ?? (isset($data['version_id']) ? NotificationTemplateVersion::findOrFail($data['version_id'])->notification_template_id : $page->first()?->id);
        $selected = $id ? NotificationTemplate::findOrFail($id) : null;
        $versions = $selected?->versions()->orderByDesc('number')->paginate(10, ['*'], 'versiones_page')->withQueryString();
        $version = $selected ? (isset($data['version_id']) ? $selected->versions()->findOrFail($data['version_id']) : $selected->versions()->orderByRaw("case when state = 'active' then 0 else 1 end")->orderByDesc('number')->first()) : null;
        if (isset($data['version_id']) && ! $selected) {
            abort(404);
        }
        if ($version) {
            $version->setAttribute('can_edit', $request->user()->can('update plantillas-notificaciones') && $version->state === 'draft' && ! $version->deliveries()->exists());
        }

        return Inertia::render('Communications/Notifications', [
            'templates' => $page, 'selected' => $selected, 'versions' => $versions, 'version' => $version,
            'filters' => $data, 'mode' => $data['modo'] ?? 'consulta',
            'permissions' => collect(['read', 'create', 'update', 'version', 'activate', 'retire'])->mapWithKeys(fn ($action) => [$action => $request->user()->can($action.' plantillas-notificaciones')]),
        ]);
    }

    public function store(SaveNotificationTemplateRequest $request, NotificationTemplateService $service)
    {
        $template = $service->create($request->validated());

        return to_route('notificaciones.index', ['plantilla_id' => $template->id])->with('success', 'Plantilla creada como borrador.');
    }

    public function update(SaveNotificationTemplateRequest $request, NotificationTemplate $template, NotificationTemplateVersion $version, NotificationTemplateService $service)
    {
        $data = $request->validated();
        foreach (['key', 'channel', 'event_type'] as $field) {
            if (isset($data[$field]) && $data[$field] !== $template->$field) {
                abort(422, 'La identidad de la plantilla no puede cambiarse.');
            }
        }
        $service->update($template, $version, $data);

        return back()->with('success', 'Borrador guardado.');
    }

    public function duplicate(NotificationTemplate $template, NotificationTemplateVersion $version, NotificationTemplateService $service)
    {
        Gate::authorize('version plantillas-notificaciones');
        $new = $service->duplicate($template, $version);

        return to_route('notificaciones.index', ['plantilla_id' => $template->id, 'version_id' => $new->id, 'modo' => 'edicion'])->with('success', 'Nueva versión creada como borrador.');
    }

    public function activate(NotificationTemplateVersion $version, NotificationTemplateService $service)
    {
        Gate::authorize('activate plantillas-notificaciones');
        $service->activate($version);

        return back()->with('success', 'Versión activada. Los pendientes de la versión sustituida fueron cancelados.');
    }

    public function retire(NotificationTemplateVersion $version, NotificationTemplateService $service)
    {
        Gate::authorize('retire plantillas-notificaciones');
        $service->retire($version);

        return back()->with('success', 'Versión retirada. Sus pendientes fueron cancelados.');
    }

    public function preview(NotificationTemplateVersion $version, NotificationContent $content, TemplateHtmlView $view)
    {
        Gate::authorize('read plantillas-notificaciones');

        return $this->previewResponse($content->preview($version->template->channel, $version->toArray()), $view);
    }

    public function previewDraft(Request $request, NotificationContent $content, TemplateHtmlView $view)
    {
        Gate::authorize('read plantillas-notificaciones');
        $data = $request->validate([
            'version_id' => 'nullable|integer|min:1', 'channel' => ['required', Rule::in(['sms', 'email'])],
            'format' => ['required', Rule::in(['text', 'html'])], 'subject' => 'nullable|string|max:160', 'body' => 'required|string|max:200000',
        ]);
        if (isset($data['version_id'])) {
            Gate::authorize('update plantillas-notificaciones');
            $version = NotificationTemplateVersion::findOrFail($data['version_id']);
            abort_unless($version->state === 'draft' && ! $version->deliveries()->exists(), 403);
            abort_unless($version->template->channel === $data['channel'], 422, 'El canal no corresponde a la versión.');
        } else {
            Gate::authorize('create plantillas-notificaciones');
        }

        return $this->previewResponse($content->preview($data['channel'], $data), $view);
    }

    private function previewResponse(array $data, TemplateHtmlView $view)
    {
        $data['html'] = $view->render($data['format'] === 'html' ? $data['body'] : '<p style="white-space:pre-wrap">'.e($data['body']).'</p>');

        return response()->json($data, 200, ['Cache-Control' => 'private, no-store']);
    }
}

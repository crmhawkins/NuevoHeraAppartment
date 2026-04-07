<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::orderBy('updated_at', 'desc')->get();
        return view('admin.whatsapp-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.whatsapp-templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|string|max:10',
            'category' => 'required|string',
            'header_text' => 'nullable|string|max:60',
            'body_text' => 'required|string',
            'footer_text' => 'nullable|string|max:60',
            'buttons' => 'nullable|array',
            'buttons.*.type' => 'nullable|string',
            'buttons.*.text' => 'nullable|string',
            'buttons.*.url' => 'nullable|string',
            'buttons.*.phone_number' => 'nullable|string',
        ]);

        // Build components array
        $components = [];

        if (!empty($data['header_text'])) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $data['header_text'],
            ];
        }

        $components[] = [
            'type' => 'BODY',
            'text' => $data['body_text'],
        ];

        if (!empty($data['footer_text'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $data['footer_text'],
            ];
        }

        if (!empty($data['buttons'])) {
            $buttons = collect($data['buttons'])->filter(fn($btn) => !empty($btn['text']))->map(function ($btn) {
                $button = [
                    'type' => $btn['type'] ?? 'URL',
                    'text' => $btn['text'],
                ];
                if (($btn['type'] ?? 'URL') === 'URL' && !empty($btn['url'])) {
                    $button['url'] = $btn['url'];
                }
                if (($btn['type'] ?? '') === 'PHONE_NUMBER' && !empty($btn['phone_number'])) {
                    $button['phone_number'] = $btn['phone_number'];
                }
                return $button;
            })->values()->toArray();

            if (!empty($buttons)) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $buttons,
                ];
            }
        }

        $template = WhatsappTemplate::create([
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'components' => $components,
            'status' => 'PENDING',
        ]);

        // Send template to WhatsApp API for approval
        $response = Http::withToken(Setting::whatsappToken())
            ->post("https://graph.facebook.com/v19.0/" . env('BUSINESS_ID') . "/message_templates", [
                'name' => $template->name,
                'language' => ['code' => $template->language],
                'category' => $template->category,
                'components' => $template->components,
            ]);

        if ($response->successful()) {
            $responseData = $response->json();
            if (!empty($responseData['id'])) {
                $template->update(['template_id' => $responseData['id']]);
            }
            return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Plantilla creada y enviada para aprobacion.');
        } else {
            $template->delete();
            return back()->withErrors(['error' => 'Error al enviar la plantilla a WhatsApp: ' . $response->body()])->withInput();
        }
    }

    public function edit(WhatsappTemplate $whatsappTemplate)
    {
        $template = $whatsappTemplate;

        // Extract component parts for the form
        $headerText = '';
        $bodyText = '';
        $footerText = '';
        $buttons = [];

        if (is_array($template->components)) {
            foreach ($template->components as $component) {
                switch ($component['type'] ?? '') {
                    case 'HEADER':
                        $headerText = $component['text'] ?? '';
                        break;
                    case 'BODY':
                        $bodyText = $component['text'] ?? '';
                        break;
                    case 'FOOTER':
                        $footerText = $component['text'] ?? '';
                        break;
                    case 'BUTTONS':
                        $buttons = $component['buttons'] ?? [];
                        break;
                }
            }
        }

        return view('admin.whatsapp-templates.edit', compact('template', 'headerText', 'bodyText', 'footerText', 'buttons'));
    }

    public function update(Request $request, WhatsappTemplate $whatsappTemplate)
    {
        $template = $whatsappTemplate;

        $data = $request->validate([
            'header_text' => 'nullable|string|max:60',
            'body_text' => 'required|string',
            'footer_text' => 'nullable|string|max:60',
            'buttons' => 'nullable|array',
            'buttons.*.type' => 'nullable|string',
            'buttons.*.text' => 'nullable|string',
            'buttons.*.url' => 'nullable|string',
            'buttons.*.phone_number' => 'nullable|string',
        ]);

        // Build components array
        $components = [];

        if (!empty($data['header_text'])) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $data['header_text'],
            ];
        }

        $components[] = [
            'type' => 'BODY',
            'text' => $data['body_text'],
        ];

        if (!empty($data['footer_text'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $data['footer_text'],
            ];
        }

        if (!empty($data['buttons'])) {
            $buttons = collect($data['buttons'])->filter(fn($btn) => !empty($btn['text']))->map(function ($btn) {
                $button = [
                    'type' => $btn['type'] ?? 'URL',
                    'text' => $btn['text'],
                ];
                if (($btn['type'] ?? 'URL') === 'URL' && !empty($btn['url'])) {
                    $button['url'] = $btn['url'];
                }
                if (($btn['type'] ?? '') === 'PHONE_NUMBER' && !empty($btn['phone_number'])) {
                    $button['phone_number'] = $btn['phone_number'];
                }
                return $button;
            })->values()->toArray();

            if (!empty($buttons)) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $buttons,
                ];
            }
        }

        // Delete old template on WhatsApp if it was approved
        if ($template->status !== 'PENDING') {
            Http::withToken(Setting::whatsappToken())->delete(
                'https://graph.facebook.com/v19.0/' . env('BUSINESS_ID') . '/message_templates',
                [
                    'name' => $template->name,
                    'language' => $template->language,
                ]
            );
        }

        // Resubmit as new template
        $apiResponse = Http::withToken(Setting::whatsappToken())->post(
            'https://graph.facebook.com/v19.0/' . env('BUSINESS_ID') . '/message_templates',
            [
                'name' => $template->name,
                'language' => ['code' => $template->language],
                'category' => $template->category,
                'components' => $components,
            ]
        );

        if (!$apiResponse->successful()) {
            return back()->withErrors(['error' => 'Error al reenviar a WhatsApp: ' . $apiResponse->body()])->withInput();
        }

        $template->update([
            'status' => 'PENDING',
            'components' => $components,
        ]);

        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Plantilla actualizada y reenviada a WhatsApp.');
    }

    public function destroy(WhatsappTemplate $whatsappTemplate)
    {
        $template = $whatsappTemplate;

        // Delete from WhatsApp API
        try {
            $response = Http::withToken(Setting::whatsappToken())->delete(
                'https://graph.facebook.com/v19.0/' . env('BUSINESS_ID') . '/message_templates',
                [
                    'name' => $template->name,
                ]
            );

            if (!$response->successful()) {
                Log::warning('Error eliminando plantilla de WhatsApp API', [
                    'template' => $template->name,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepcion al eliminar plantilla de WhatsApp API', [
                'template' => $template->name,
                'error' => $e->getMessage(),
            ]);
        }

        $template->delete();

        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Plantilla eliminada correctamente.');
    }

    public function test(Request $request, WhatsappTemplate $whatsappTemplate)
    {
        $template = $whatsappTemplate;

        $data = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);

        // Build template parameters from components
        $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
        $bodyText = $bodyComponent['text'] ?? '';

        // Count variables like {{1}}, {{2}}, etc.
        preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);
        $variableCount = count($matches[1]);

        $bodyParameters = [];
        for ($i = 1; $i <= $variableCount; $i++) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => $request->input("param_{$i}", "test_value_{$i}"),
            ];
        }

        $componentsPayload = [];
        if (!empty($bodyParameters)) {
            $componentsPayload[] = [
                'type' => 'body',
                'parameters' => $bodyParameters,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => [
                    'code' => $template->language,
                ],
                'components' => $componentsPayload,
            ],
        ];

        $url = Setting::whatsappUrl();
        $token = Setting::whatsappToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        if ($response->successful()) {
            return back()->with('success', "Mensaje de prueba enviado correctamente a {$phone}.");
        } else {
            return back()->withErrors(['error' => 'Error al enviar mensaje de prueba: ' . $response->body()]);
        }
    }

    public function sync()
    {
        $wabaId = env('BUSINESS_ID');
        $token = Setting::whatsappToken();

        $url = "https://graph.facebook.com/v19.0/{$wabaId}/message_templates";

        do {
            $response = Http::withToken($token)->get($url);

            if (!$response->ok()) {
                return back()->withErrors(['error' => 'Error al sincronizar: ' . $response->body()]);
            }

            $templates = $response->json('data');

            foreach ($templates as $tpl) {
                WhatsappTemplate::updateOrCreate(
                    ['name' => $tpl['name'], 'language' => $tpl['language']],
                    [
                        'template_id' => $tpl['id'] ?? null,
                        'status' => $tpl['status'] ?? 'unknown',
                        'category' => $tpl['category'] ?? null,
                        'parameter_format' => $tpl['parameter_format'] ?? null,
                        'components' => $tpl['components'] ?? [],
                    ]
                );
            }

            $url = $response->json('paging.next') ?? null;

        } while ($url);

        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Plantillas sincronizadas correctamente desde WhatsApp.');
    }

    public function checkStatus(WhatsappTemplate $whatsappTemplate)
    {
        $template = $whatsappTemplate;

        $response = Http::withToken(Setting::whatsappToken())
            ->get("https://graph.facebook.com/v19.0/" . env('BUSINESS_ID') . "/message_templates?name=" . $template->name);

        if ($response->successful()) {
            $data = $response->json()['data'][0] ?? null;

            if ($data) {
                $template->status = $data['status'];
                $template->save();

                return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Estado actualizado: ' . $template->status);
            } else {
                return back()->withErrors(['error' => 'Plantilla no encontrada en la API de WhatsApp.']);
            }
        } else {
            return back()->withErrors(['error' => 'Error al verificar el estado: ' . $response->body()]);
        }
    }
}

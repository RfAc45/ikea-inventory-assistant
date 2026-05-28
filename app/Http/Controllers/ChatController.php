<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function send(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $userMessage = $request->input('message');
        $history     = $request->input('history', []);

        $inventario   = $this->getInventario();
        $systemPrompt = $this->buildSystemPrompt($inventario);
        $reply        = $this->callClaude($systemPrompt, $history, $userMessage);

        return response()->json(['reply' => $reply]);
    }

    private function getInventario(): string
    {
        $token  = env('AIRTABLE_TOKEN');
        $baseId = env('AIRTABLE_BASE_ID');
        $table  = env('AIRTABLE_TABLE', 'Productos');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->timeout(10)->withoutVerifying()->get("https://api.airtable.com/v0/{$baseId}/{$table}");

        if ($response->failed()) {
            return 'Error al consultar el inventario de IKEA.';
        }

        $records = $response->json('records', []);
        $lineas  = [];

        foreach ($records as $record) {
            $f = $record['fields'] ?? [];

            $nombre    = $this->toString($f['Nombre del Producto'] ?? 'Sin nombre');
            $cat       = $this->toString($f['Categoría']           ?? '-');
            $cantidad  = $f['Cantidad']     ?? 0;
            $minimo    = $f['Stock Mínimo'] ?? 0;
            $ubicacion = $this->toString($f['Ubicación']       ?? '-');
            $precio    = $this->toString($f['Precio Unitario'] ?? '-');
            $incid     = $this->toString($f['Incidencias']     ?? '-');

            if ($cantidad <= $minimo) {
                $estado = 'CRÍTICO';
            } elseif ($cantidad <= $minimo * 2) {
                $estado = 'BAJO';
            } else {
                $estado = 'NORMAL';
            }

            $lineas[] = "- {$nombre} | {$cat} | {$cantidad} uds. | Mín:{$minimo} | "
                      . "{$estado} | \${$precio} | {$ubicacion} | Incidencia: {$incid}";
        }

        return implode("\n", $lineas) ?: 'No se encontraron productos.';
    }

    private function toString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }
        return (string) ($value ?? '-');
    }

    private function buildSystemPrompt(string $inventario): string
    {
        return <<<PROMPT
Eres el IKEA Smart Inventory Assistant, el asistente inteligente de gestión de inventario de IKEA.
Tu función es ayudar al equipo operativo de IKEA a consultar el estado del inventario, identificar
productos críticos, registrar incidencias y responder preguntas operativas sobre los productos de la tienda.

Los productos de IKEA están organizados en categorías como: Almacenamiento, Sillas y sillones,
Iluminación, Organización, Cocina, Dormitorio, Sala de estar, y Baño.

INVENTARIO ACTUAL (datos en tiempo real desde Airtable):
{$inventario}

CLASIFICACIÓN DE STOCK:
- CRÍTICO: cantidad <= stock mínimo → reposición inmediata necesaria
- BAJO: cantidad <= stock mínimo x2 → requiere atención pronto
- NORMAL: cantidad > stock mínimo x2 → sin problemas

REGLAS:
1. Responde siempre en español, de forma concisa y operativa.
2. Cuando presentes múltiples productos usa formato de lista clara.
3. Al registrar una incidencia solicita: equipo o área afectada, tipo de problema y descripción.
4. No inventes datos que no estén en el inventario.
5. Mantén un tono profesional y eficiente, acorde con los estándares de IKEA.
6. Valores monetarios en la moneda local (MXN si aplica).
7. Cuando identifiques productos críticos, sugiere siempre generar una solicitud de reposición.
PROMPT;
    }

    private function callClaude(string $systemPrompt, array $history, string $userMessage): string
    {
        $apiKey = env('ANTHROPIC_API_KEY');

        // Construir historial de conversación
        $messages = [];
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = [
                'role'    => $role,
                'content' => $msg['content'],
            ];
        }

        // Agregar mensaje actual
        $messages[] = [
            'role'    => 'user',
            'content' => $userMessage,
        ];

        $response = Http::withoutVerifying()
            ->timeout(30)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

        if ($response->failed()) {
            return 'Error al conectar con la IA: ' . $response->status() . ' - ' . $response->body();
        }

        return $response->json('content.0.text')
            ?? 'No se pudo generar una respuesta.';
    }
}

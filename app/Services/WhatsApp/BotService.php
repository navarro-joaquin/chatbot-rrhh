<?php

namespace App\Services\WhatsApp;

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\Vacacion;
use App\Models\WhatsappConversacion;

class BotService
{
    public function __construct(private EvolutionService $evolution) {}

    public function handle(string $telefono, string $mensaje): void
    {
        $telefonoLimpio = $this->normalizarTelefono($telefono);

        \Log::info('Whatsapp Bot', [
            'telefono_original' => $telefono,
            'telefono_limpio' => $telefonoLimpio,
            'mensaje' => $mensaje,
        ]);

        $empleado = Empleado::where('telefono', $telefonoLimpio)
            ->where('estado', true)
            ->first();

        \Log::info('Empleado encontrado', [
            'empleado' => $empleado?->nombre_completo ?? 'NO ENCONTRADO',
        ]);

        if (! $empleado) {
            $this->evolution->sendText(
                $telefono,
                "Tu número no está registrado en el sistema.\nContacta a Recursos Humanos"
            );

            return;
        }

        $conversacion = WhatsappConversacion::firstOrCreate(
            ['empleado_id' => $empleado->id],
            ['step' => 'menu']
        );

        $respuesta = $this->procesarPaso($conversacion, $empleado, trim($mensaje));

        $this->evolution->sendText($telefono, $respuesta);
    }

    private function procesarPaso(WhatsappConversacion $conversacion, Empleado $empleado, string $mensaje): string
    {
        return match ($conversacion->step) {
            'menu' => $this->handleMenu($conversacion, $empleado, $mensaje),
            default => $this->mostrarMenu($conversacion, $empleado)
        };
    }

    private function handleMenu(WhatsappConversacion $conversacion, Empleado $empleado, string $mensaje): string
    {
        return match ($mensaje) {
            '1' => $this->mostrarVacaciones($empleado),
            '2' => $this->mostrarCompensaciones($empleado),
            default => $this->mostrarMenu($conversacion, $empleado),
        };
    }

    private function mostrarMenu(WhatsappConversacion $conversacion, Empleado $empleado): string
    {
        $conversacion->update(['step' => 'menu']);

        return "*Bienvenido*\n".
            "Nombre: {$empleado->nombre_completo}\n".
            "Item: {$empleado->nro_item}\n\n".
            "Por favor, selecciona una opción:\n".
            "1. Días de vacaciones\n".
            '2. Horas de compensación';
    }

    private function mostrarVacaciones(Empleado $empleado): string
    {
        $vacaciones = Vacacion::with('gestion')
            ->where('empleado_id', $empleado->id)
            ->get();

        if ($vacaciones->isEmpty()) {
            return "No tienes registros de vacaciones\n\n".$this->menuOpciones();
        }

        $detalle = $vacaciones->map(function ($vacacion) {
            $dias = number_format($vacacion->dias_disponibles, 1);

            return "* {$vacacion->gestion->anio}: *{$dias} días*";
        })->join("\n");

        $total = number_format($vacaciones->sum('dias_disponibles'), 1);

        return "*Vacaciones disponibles*\n\n".
            "{$detalle}\n".
            "─────────────────\n".
            "Total: *{$total} días*\n\n".
            $this->menuOpciones();
    }

    private function mostrarCompensaciones(Empleado $empleado): string
    {
        $totalHoras = Compensacion::where('empleado_id', $empleado->id)
            ->where('estado', 'disponible')
            ->sum('cantidad_horas');

        $horas = number_format($totalHoras, 1);

        return "Horas de compensación:\n".
            "Horas disponibles: *{$horas} hrs*\n\n".
            $this->menuOpciones();
    }

    private function menuOpciones(): string
    {
        return "Selecciona otra opción:\n".
            "1. Días de vacaciones\n".
            '2. Horas de compensación';
    }

    private function normalizarTelefono(string $telefono): string
    {
        $numero = str_replace('@s.whatsapp.net', '', $telefono);

        if (str_starts_with($numero, '591')) {
            $numero = substr($numero, 3);
        }

        return $numero;
    }
}

<?php

use App\Support\VacacionesTiempo;

it('convierte dias horas y minutos a dias decimales', function () {
    expect(VacacionesTiempo::aDias(2, 5, 55))->toBe(2.7396)
        ->and(VacacionesTiempo::aDias(8, 5, 55))->toBe(8.7396)
        ->and(VacacionesTiempo::aDias(0, 2, 30))->toBe(0.3125);
});

it('descompone dias decimales en dias horas y minutos', function () {
    expect(VacacionesTiempo::aPartesDias(2.74))->toBe(['dias' => 2, 'horas' => 5, 'minutos' => 55])
        ->and(VacacionesTiempo::aPartesDias(0.3125))->toBe(['dias' => 0, 'horas' => 2, 'minutos' => 30]);
});

it('genera textos legibles de dias y horas', function () {
    expect(VacacionesTiempo::aTextoDias(2.74))->toBe('2 días, 5 horas y 55 minutos')
        ->and(VacacionesTiempo::aTextoDias(1.0))->toBe('1 día')
        ->and(VacacionesTiempo::aTextoHoras(5.5))->toBe('5 horas y 30 minutos')
        ->and(VacacionesTiempo::aTextoHoras(0))->toBeNull();
});

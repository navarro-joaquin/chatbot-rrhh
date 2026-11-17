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

it('genera textos de dias con signo para movimientos y saldos', function () {
    expect(VacacionesTiempo::aTextoDiasConSigno(15))->toBe('15 días')
        ->and(VacacionesTiempo::aTextoDiasConSigno(-3.5))->toBe('-3 días y 4 horas')
        ->and(VacacionesTiempo::aTextoDiasConSigno(0))->toBe('0 días')
        ->and(VacacionesTiempo::aTextoDiasConSigno(-0.00001))->toBe('0 días');
});

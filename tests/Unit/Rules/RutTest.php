<?php

use App\Rules\Rut;
use Illuminate\Support\Facades\Validator;

function validateRut(string $value): array
{
    $v = Validator::make(['rut' => $value], ['rut' => ['required', new Rut]]);
    return $v->errors()->get('rut');
}

it('acepta RUTs validos con DV calculado por reciprocidad', function () {
    // Usar reciprocidad: si computeDv(N) = D, entonces N-D es valido.
    $numbers = [12345678, 19876543, 11111111, 25000000];

    foreach ($numbers as $n) {
        $dv = Rut::computeDv($n);
        $rut = "{$n}-{$dv}";
        expect(validateRut($rut))->toBeEmpty("RUT generado {$rut} deberia ser valido");
    }
});

it('acepta distintos formatos del mismo RUT valido', function () {
    // 12345678 con su DV correcto.
    $dv = Rut::computeDv(12345678);

    expect(validateRut("12345678-{$dv}"))->toBeEmpty();
    expect(validateRut("12.345.678-{$dv}"))->toBeEmpty();
    expect(validateRut("12345678{$dv}"))->toBeEmpty();
});

it('rechaza RUTs con digito verificador incorrecto', function () {
    $wrongDv = Rut::computeDv(12345678) === '9' ? '0' : '9';
    expect(validateRut("12345678-{$wrongDv}"))->not->toBeEmpty();
});

it('rechaza RUTs con formato invalido', function (string $input) {
    expect(validateRut($input))->not->toBeEmpty();
})->with([
    'abc',
    '123-X',
    '999999999999',
]);

it('rechaza RUTs fuera de rango (muy bajos o muy altos)', function () {
    expect(validateRut('1-9'))->not->toBeEmpty();
    expect(validateRut('100000000-1'))->not->toBeEmpty();
});

it('computeDv genera un caracter unico valido (0-9 o K)', function () {
    foreach ([1234567, 12345678, 19876543, 11111111] as $n) {
        $dv = Rut::computeDv($n);
        expect(strlen($dv))->toBe(1);
        expect($dv)->toMatch('/^[0-9K]$/');
    }
});

it('normalize transforma a formato canonico', function () {
    $dv = Rut::computeDv(12345678);
    expect(Rut::normalize("12.345.678-{$dv}"))->toBe("12345678-{$dv}");
    expect(Rut::normalize("12345678{$dv}"))->toBe("12345678-{$dv}");
});

it('normalize convierte k minuscula a K mayuscula', function () {
    // Buscar un numero cuyo DV sea K (factor 11-1).
    $rutConK = null;
    for ($n = 10000000; $n < 10000200 && ! $rutConK; $n++) {
        if (Rut::computeDv($n) === 'K') {
            $rutConK = $n;
        }
    }
    expect($rutConK)->not->toBeNull('Se esperaba encontrar al menos un RUT con DV=K en el rango');

    expect(Rut::normalize("{$rutConK}-k"))->toBe("{$rutConK}-K");
});

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El saldo disponible del empleado no alcanza para cubrir la solicitud.
 */
class SaldoInsuficienteException extends RuntimeException {}

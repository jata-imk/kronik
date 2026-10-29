<?php

use App\Database\MariaDbConnection;
use App\Database\MySqlConnection;
use Illuminate\Database\QueryException;

function conflictoMariaDbPrueba(int $codigo = 1020): QueryException
{
    $pdo = new PDOException('Conflicto sintético sin texto reconocible');
    $pdo->errorInfo = ['HY000', $codigo, 'diagnóstico sintético'];

    return new QueryException('test', 'SELECT 1', [], $pdo);
}

test('conflicto snapshot anidado reintenta la transacción completa y revierte sus escrituras', function (string $clase) {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE prueba (valor INT)');
    $conexion = new $clase($pdo);
    $intentos = 0;
    $conexion->transaction(function () use ($conexion, $pdo, &$intentos) {
        $intentos++;
        $pdo->exec('INSERT INTO prueba VALUES (1)');
        $conexion->transaction(function () use ($pdo, &$intentos) {
            $pdo->exec('INSERT INTO prueba VALUES (2)');
            if ($intentos === 1) {
                // MariaDB aborts the whole transaction, removing savepoints.
                $pdo->rollBack();
                throw conflictoMariaDbPrueba();
            }
        });
    }, attempts: 5);

    expect($intentos)->toBe(2)->and($conexion->transactionLevel())->toBe(0)
        ->and((int) $pdo->query('SELECT COUNT(*) FROM prueba')->fetchColumn())->toBe(2);
})->with([[MySqlConnection::class], [MariaDbConnection::class]]);

test('conflicto snapshot respeta el máximo de intentos y conserva la causa original', function (string $clase) {
    $conexion = new $clase(new PDO('sqlite::memory:'));
    $intentos = 0;
    try {
        $conexion->transaction(function () use ($conexion, &$intentos) {
            $intentos++;
            $conexion->transaction(fn () => throw conflictoMariaDbPrueba());
        }, attempts: 3);
        test()->fail('El conflicto persistente debe propagarse.');
    } catch (\Illuminate\Database\DeadlockException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(QueryException::class)
            ->and($exception->getPrevious()->errorInfo[1])->toBe(1020);
    }
    expect($intentos)->toBe(3)->and($conexion->transactionLevel())->toBe(0);
})->with([[MySqlConnection::class], [MariaDbConnection::class]]);

test('otros errores SQL no se reintentan como conflictos de snapshot', function (string $clase) {
    $conexion = new $clase(new PDO('sqlite::memory:'));
    $intentos = 0;
    expect(function () use ($conexion, &$intentos) {
        $conexion->transaction(function () use (&$intentos) {
            $intentos++;
            throw conflictoMariaDbPrueba(1064);
        }, attempts: 5);
    })->toThrow(QueryException::class);
    expect($intentos)->toBe(1)->and($conexion->transactionLevel())->toBe(0);
})->with([[MySqlConnection::class], [MariaDbConnection::class]]);

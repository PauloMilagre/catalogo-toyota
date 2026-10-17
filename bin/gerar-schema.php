<?php

declare(strict_types=1);

/*
 * Gera database/schema.sql juntando todas as migrações num arquivo só,
 * para quem preferir importar o banco pelo phpMyAdmin (aba Importar).
 * Rode de novo sempre que criar uma migração nova.
 *
 *   php bin/gerar-schema.php
 */

use Toyota\Core\Migrador;

require dirname(__DIR__) . '/src/bootstrap.php';

$raiz = dirname(__DIR__);
$migrador = new Migrador(\Toyota\Core\Config::deArray([]), $raiz . '/database/migrations');

$saida = "CREATE DATABASE IF NOT EXISTS catalogo_toyota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$saida .= "USE catalogo_toyota;\n\n";
$saida .= "-- Script completo do banco do Catálogo Toyota (Trabalho Individual II, SCOM).\n";
$saida .= "-- Gerado por bin/gerar-schema.php a partir de database/migrations/. Não edite à mão.\n\n";
$saida .= Migrador::sqlTabelaMigracoes() . ";\n\n";

$nomes = [];
foreach ($migrador->arquivos() as $arquivo) {
    $nome = basename($arquivo);
    $nomes[] = $nome;
    $saida .= "-- ---------------------------------------------------------------\n";
    $saida .= "-- {$nome}\n";
    $saida .= "-- ---------------------------------------------------------------\n";
    $saida .= rtrim((string) file_get_contents($arquivo)) . "\n\n";
}

$saida .= "-- Registra as migrações como aplicadas, para o bin/migrar.php não repeti-las.\n";
$saida .= 'INSERT INTO migracoes (nome) VALUES ' . implode(', ', array_map(
    static fn (string $n): string => "('{$n}')",
    $nomes
)) . ";\n";

file_put_contents($raiz . '/database/schema.sql', $saida);
echo "database/schema.sql gerado com " . count($nomes) . " migrações.\n";

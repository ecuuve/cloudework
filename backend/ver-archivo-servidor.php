<?php
/**
 * Script para leer el contenido del archivo en el servidor
 * y verificar si tiene errores de sintaxis
 */

$file = __DIR__ . '/public/demo/athlete-dashboard.html';

if (!file_exists($file)) {
    echo "❌ El archivo NO EXISTE en: $file\n";
    exit(1);
}

$content = file_get_contents($file);

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DEL ARCHIVO EN EL SERVIDOR\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";
echo "📁 Archivo: $file\n";
echo "📏 Tamaño: " . number_format(strlen($content)) . " bytes\n";
echo "\n";

// Buscar errores de sintaxis específicos
echo "🔍 BUSCANDO ERRORES CONOCIDOS:\n";
echo "──────────────────────────────────────────────────────\n";

// Error 1: fetch sin paréntesis
$matches1 = [];
preg_match_all('/fetch`[^(]/', $content, $matches1, PREG_OFFSET_CAPTURE);
if (count($matches1[0]) > 0) {
    echo "❌ ENCONTRADO: fetch` sin paréntesis (" . count($matches1[0]) . " ocurrencias)\n";
    foreach ($matches1[0] as $match) {
        $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
        $snippet = substr($content, max(0, $match[1] - 30), 60);
        echo "   Línea $line: ...{$snippet}...\n";
    }
} else {
    echo "✅ No hay fetch` sin paréntesis\n";
}

// Error 2: auth-guard.js
if (strpos($content, 'auth-guard') !== false) {
    echo "⚠️  ENCONTRADO: referencia a auth-guard.js (debe eliminarse)\n";
} else {
    echo "✅ No hay auth-guard.js\n";
}

// Error 3: backdrop-filter
$backdropCount = substr_count($content, 'backdrop-filter');
if ($backdropCount > 0) {
    echo "⚠️  ENCONTRADO: $backdropCount backdrop-filter (puede causar problemas)\n";
} else {
    echo "✅ No hay backdrop-filter\n";
}

echo "\n";
echo "🔍 VERIFICANDO FETCH CALLS CORRECTOS:\n";
echo "──────────────────────────────────────────────────────\n";

// Contar fetch correctos
$correctFetch = substr_count($content, 'fetch(`');
$incorrectFetch = count($matches1[0]);

echo "✅ fetch(` correcto: $correctFetch\n";
echo ($incorrectFetch > 0 ? "❌" : "✅") . " fetch` incorrecto: $incorrectFetch\n";

echo "\n";
echo "📊 RESUMEN:\n";
echo "──────────────────────────────────────────────────────\n";

if ($incorrectFetch > 0 || strpos($content, 'auth-guard') !== false) {
    echo "❌ EL ARCHIVO TIENE ERRORES\n";
    echo "   → Necesitas reemplazarlo con el archivo correcto\n";
} else {
    echo "✅ EL ARCHIVO PARECE CORRECTO\n";
    echo "   → El problema puede estar en el cache del navegador\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

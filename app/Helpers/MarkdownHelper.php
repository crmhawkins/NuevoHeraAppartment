<?php

namespace App\Helpers;

class MarkdownHelper
{
    /**
     * Convierte texto Markdown básico a HTML
     * 
     * @param string $markdown
     * @return string
     */
    public static function toHtml($markdown)
    {
        if (empty($markdown)) {
            return '';
        }

        $html = $markdown;

        // Headers (# ## ### ####)
        $html = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);

        // Bold (**text** o __text__) - primero para evitar conflictos con italic
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $html);

        // Italic (*text* o _text_) - solo si no está entre asteriscos dobles
        $html = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $html);
        $html = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '<em>$1</em>', $html);

        // Links [text](url)
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $html);

        // Horizontal rules (--- o ***)
        $html = preg_replace('/^---+$/m', '<hr>', $html);
        $html = preg_replace('/^\*\*\*+$/m', '<hr>', $html);

        // Lists (- item o * item) - manejar múltiples líneas
        $lines = explode("\n", $html);
        $inList = false;
        $listType = null;
        $result = [];
        
        foreach ($lines as $line) {
            // Lista no ordenada
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if (!$inList || $listType !== 'ul') {
                    if ($inList) {
                        $result[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    }
                    $result[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $result[] = '<li>' . trim($matches[1]) . '</li>';
            }
            // Lista ordenada
            elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if (!$inList || $listType !== 'ol') {
                    if ($inList) {
                        $result[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    }
                    $result[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $result[] = '<li>' . trim($matches[1]) . '</li>';
            }
            // Línea vacía - cerrar lista si estaba abierta
            elseif (trim($line) === '' || trim($line) === '<hr>') {
                if ($inList) {
                    $result[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                    $listType = null;
                }
                if (trim($line) === '<hr>') {
                    $result[] = '<hr>';
                } else {
                    $result[] = '';
                }
            }
            // Cualquier otra línea
            else {
                if ($inList) {
                    $result[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                    $listType = null;
                }
                $result[] = $line;
            }
        }
        
        // Cerrar lista si quedó abierta
        if ($inList) {
            $result[] = $listType === 'ul' ? '</ul>' : '</ol>';
        }
        
        $html = implode("\n", $result);

        // Line breaks (doble espacio + nueva línea o dos saltos de línea)
        $html = preg_replace('/  \n/', '<br>', $html);
        
        // Párrafos (agrupar líneas consecutivas, respetando bloques existentes)
        $html = preg_replace('/\n\n+/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        // Limpiar párrafos vacíos y párrafos dentro de listas/headers
        $html = preg_replace('/<p>\s*(<(ul|ol|h[1-6]|hr))/', '$1', $html);
        $html = preg_replace('/(<\/(ul|ol|h[1-6])>)\s*<\/p>/', '$1', $html);
        $html = preg_replace('/<p><li>/', '<li>', $html);
        $html = preg_replace('/<\/li><\/p>/', '</li>', $html);
        $html = preg_replace('/<p>\s*<hr>\s*<\/p>/', '<hr>', $html);
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);
        $html = preg_replace('/<p>\s*(<strong>|<em>)/', '$1', $html);
        $html = preg_replace('/(<\/strong>|<\/em>)\s*<\/p>/', '$1', $html);

        return $html;
    }
}


<?php
declare(strict_types=1);

namespace App\Core;

/** Allow-list HTML sanitiser for rich text saved from the admin editor. */
final class Html
{
    private const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'span', 'img', 'hr', 'sub', 'sup', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption'];
    private const ATTRS = ['a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height', 'loading'], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'], '*' => ['class', 'dir']];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') return '';
        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('__root');
        if (!$root) return '';
        self::walk($root, $doc);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function walk(\DOMNode $node, \DOMDocument $doc): void
    {
        $children = [];
        foreach ($node->childNodes as $c) $children[] = $c;
        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) continue;
            $tag = strtolower($child->tagName);
            if (!in_array($tag, self::TAGS, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'textarea', 'button', 'link', 'meta'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                // unwrap unknown tags (div, section...) keeping their content
                self::walk($child, $doc);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowed = array_merge(self::ATTRS['*'], self::ATTRS[$tag] ?? []);
            $attrs = [];
            foreach ($child->attributes as $a) $attrs[] = $a->name;
            foreach ($attrs as $name) {
                $lname = strtolower($name);
                if (!in_array($lname, $allowed, true)) {
                    $child->removeAttribute($name);
                    continue;
                }
                if (in_array($lname, ['href', 'src'], true)) {
                    $val = trim($child->getAttribute($name));
                    if (!preg_match('#^(https?:|mailto:|tel:|/|\#)#i', $val) || preg_match('/^\s*javascript:/i', $val)) {
                        $child->removeAttribute($name);
                    }
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener');
            }
            self::walk($child, $doc);
        }
    }
}

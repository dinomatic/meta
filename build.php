<?php
/**
 * build_meta_live.php
 *
 * Crawls every URL in meta.php and saves fresh meta data
 * to meta_live.php (same structure, updated values).
 */

$meta = require 'meta.php';       // assumes the file returns the array
$out  = [];                       // will hold the rebuilt structure

/**
 * Recursively walk the meta tree, fetch each URL, and
 * return the same tree with fresh data.
 */
function walk_and_refresh(array $node): array
{
    $result = [];

    foreach ($node as $key => $item) {
        // Leaf node – has an url we need to crawl
        if (is_array($item) && isset($item['url'])) {
            [$title, $description, $h1, $intro] = crawl($item['url']);

            $result[$key] = [
                'url' => $item['url'],
                'title' => $title,
                'description' => $description,
                'h1' => $h1,
                'intro' => $intro,
            ];
        }
        // Nested branch – dive deeper
        elseif (is_array($item)) {
            $result[$key] = walk_and_refresh($item);
        }
    }

    return $result;
}

/**
 * Fetch a URL and extract the required pieces.
 *
 * @return array [title, description, h1, intro]
 */
function crawl(string $url): array
{
    // --- Fetch ------------------------------------------------------------
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'MetaCrawler/1.0 (+https://dinomatic.com)',
        CURLOPT_TIMEOUT => 15,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html === false) {
        return ['', '', '', '']; // fallback on error
    }

    // --- Parse ------------------------------------------------------------
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // meta title
    $titleNodes = $xpath->query('//title');
    $title      = $titleNodes->length ? trim($titleNodes[0]->textContent) : '';

    // meta description
    $descNodes   = $xpath->query('//meta[@name="description"]');
    $description = '';
    if ($descNodes->length && $descNodes[0]->hasAttribute('content')) {
        $description = trim($descNodes[0]->getAttribute('content'));
    }

    // first H1
    $h1Nodes = $xpath->query('//h1');
    $h1      = $h1Nodes->length ? trim($h1Nodes[0]->textContent) : '';

    // intro – either <p> or <h2> immediately after the first h1
    $intro = '';
    if ($h1Nodes->length) {
        $h1Node = $h1Nodes[0];
        for ($n = $h1Node->nextSibling; $n; $n = $n->nextSibling) {
            if ($n->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($n->nodeName);
                if ($tag === 'p' || $tag === 'h2') {
                    $intro = trim($n->textContent);
                }
                break;
            }
        }
    }

    return [$title, $description, $h1, $intro];
}

// -------------------------------------------------------------------------

$out = walk_and_refresh($meta);

// pretty-print and save
file_put_contents(
    'meta_live.php',
    "<?php\nreturn ".var_export($out, true).";\n"
);

echo "meta_live.php has been created.\n";

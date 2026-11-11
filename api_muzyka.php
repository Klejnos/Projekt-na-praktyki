<?php
// Łącznik między formularzem muzyki a API Discogs.
// Discogs wymaga nagłówka User-Agent. Klucz tokena jest opcjonalny.

require_once __DIR__ . '/assets/config/klucze.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] != true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Brak autoryzacji.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function odpowiedz(array $dane): void
{
    echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pobierzJson(string $url): ?array
{
    global $discogs_user_agent;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $discogs_user_agent,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $odpowiedz = curl_exec($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($odpowiedz === false || $kod < 200 || $kod >= 300) {
            return null;
        }

        $json = json_decode($odpowiedz, true);

        return is_array($json) ? $json : null;
    }

    $kontekst = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nUser-Agent: " . $discogs_user_agent . "\r\n",
            'timeout' => 20,
        ],
    ]);

    $odpowiedz = @file_get_contents($url, false, $kontekst);

    if ($odpowiedz === false) {
        return null;
    }

    $json = json_decode($odpowiedz, true);

    return is_array($json) ? $json : null;
}

$action = $_GET['action'] ?? '';

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q, 'UTF-8') < 2) {
        odpowiedz(['ok' => true, 'results' => []]);
    }

    $parametry = [
        'q' => $q,
        'type' => 'release',
        'per_page' => 8,
        'page' => 1,
    ];

    if (!empty($discogs_token)) {
        $parametry['token'] = $discogs_token;
    }

    $dane = pobierzJson('https://api.discogs.com/database/search?' . http_build_query($parametry));

    if (!$dane || !isset($dane['results'])) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać wyników z Discogs.']);
    }

    $wyniki = [];

    foreach (array_slice($dane['results'], 0, 8) as $album) {
        $okladka = $album['cover_image'] ?? '';

        if ($okladka === '') {
            $okladka = $album['thumb'] ?? '';
        }

        $gatunek = implode(', ', array_slice(array_merge($album['genre'] ?? [], $album['style'] ?? []), 0, 4));
        $format = implode(', ', array_slice($album['format'] ?? [], 0, 4));
        $label = implode(', ', array_slice($album['label'] ?? [], 0, 1));

        $wyniki[] = [
            'discogs_id' => (int) ($album['id'] ?? 0),
            'tytul' => $album['title'] ?? '',
            'rok' => $album['year'] ?? '',
            'gatunek' => $gatunek,
            'format' => $format,
            'label' => $label,
            'okladka' => $okladka,
            'discogs_uri' => !empty($album['uri'])
                ? 'https://www.discogs.com' . $album['uri']
                : '',
        ];
    }

    odpowiedz(['ok' => true, 'results' => $wyniki]);
}

if ($action === 'details') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        odpowiedz(['ok' => false, 'error' => 'Brak poprawnego ID albumu.']);
    }

    $parametry = [];

    if (!empty($discogs_token)) {
        $parametry['token'] = $discogs_token;
    }

    $url = 'https://api.discogs.com/releases/' . $id;

    if ($parametry) {
        $url .= '?' . http_build_query($parametry);
    }

    $dane = pobierzJson($url);

    if (!$dane || empty($dane['id'])) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać szczegółów albumu z Discogs.']);
    }

    $wykonawca = implode(', ', array_column($dane['artists'] ?? [], 'name'));
    $gatunek = implode(', ', array_slice(array_merge($dane['genres'] ?? [], $dane['styles'] ?? []), 0, 4));

    $formaty = [];

    foreach (($dane['formats'] ?? []) as $format) {
        if (!empty($format['name'])) {
            $formaty[] = $format['name'];
        }

        foreach (array_slice($format['descriptions'] ?? [], 0, 3) as $opis) {
            $formaty[] = $opis;
        }
    }

    $format = implode(', ', array_slice(array_unique($formaty), 0, 4));
    $label = implode(', ', array_column(array_slice($dane['labels'] ?? [], 0, 1), 'name'));

    $okladka = '';

    foreach (($dane['images'] ?? []) as $obraz) {
        if (($obraz['type'] ?? '') === 'primary') {
            $okladka = $obraz['uri'] ?? '';
            break;
        }
    }

    if ($okladka === '' && !empty($dane['images'][0]['uri'])) {
        $okladka = $dane['images'][0]['uri'];
    }

    $discogsUri = $dane['uri'] ?? '';

    if ($discogsUri !== '' && strpos($discogsUri, 'http') !== 0) {
        $discogsUri = 'https://www.discogs.com' . $discogsUri;
    }

    odpowiedz([
        'ok' => true,
        'album' => [
            'discogs_id' => (int) $dane['id'],
            'tytul' => $dane['title'] ?? '',
            'wykonawca' => $wykonawca,
            'rok' => $dane['year'] ?? null,
            'gatunek' => $gatunek,
            'format' => $format,
            'label' => $label,
            'okladka' => $okladka,
            'discogs_uri' => $discogsUri,
        ],
    ]);
}

odpowiedz(['ok' => false, 'error' => 'Nieznana akcja.']);

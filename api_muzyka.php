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
    // Usuwa ewentualne przypadkowe wyjście (np. whitespace/komentarz z dołączanego pliku),
    // żeby odpowiedź zawsze była poprawnym JSON-em.
    if (ob_get_length() !== false) {
        ob_clean();
    }

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

// Pobiera kilka adresów JSON równolegle, żeby dociąganie okładek nie spowalniało wyszukiwania.
function pobierzJsonWiele(array $adresy): array
{
    global $discogs_user_agent;

    if (!function_exists('curl_multi_init')) {
        $wynik = [];

        foreach ($adresy as $klucz => $url) {
            $wynik[$klucz] = pobierzJson($url);
        }

        return $wynik;
    }

    $multi = curl_multi_init();
    $uchwyty = [];

    foreach ($adresy as $klucz => $url) {
        $ch = curl_init($url);

        if ($ch === false) {
            $uchwyty[$klucz] = null;
            continue;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $discogs_user_agent,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        curl_multi_add_handle($multi, $ch);
        $uchwyty[$klucz] = $ch;
    }

    do {
        $status = curl_multi_exec($multi, $aktywne);

        if ($aktywne) {
            curl_multi_select($multi, 1.0);
        }
    } while ($aktywne && $status === CURLM_OK);

    $wynik = [];

    foreach ($uchwyty as $klucz => $ch) {
        if ($ch === null) {
            $wynik[$klucz] = null;
            continue;
        }

        $tresc = curl_multi_getcontent($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);

        $json = null;

        if ($tresc !== null && $kod >= 200 && $kod < 300) {
            $odczyt = json_decode($tresc, true);
            $json = is_array($odczyt) ? $odczyt : null;
        }

        $wynik[$klucz] = $json;
    }

    curl_multi_close($multi);

    return $wynik;
}

// Wyciąga adres okładki z danych wydania Discogs.
function znajdzOkladke(array $album): string
{
    foreach (($album['images'] ?? []) as $obraz) {
        if (($obraz['type'] ?? '') === 'primary' && !empty($obraz['uri'])) {
            return $obraz['uri'];
        }
    }

    if (!empty($album['images'][0]['uri'])) {
        return $album['images'][0]['uri'];
    }

    return $album['cover_image'] ?? ($album['thumb'] ?? '');
}

// Prosty cache okładek, żeby nie odpytywać Discogs o te same wydania przy każdym wyszukiwaniu.
function plikCacheOkladek(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wypozyczalnia_discogs_okladki.json';
}

function pobierzOkladkiDiscogs(array $ids): array
{
    global $discogs_token;

    $ttl = 60 * 60 * 24 * 30;
    $teraz = time();
    $cache = [];
    $plik = plikCacheOkladek();

    if (is_file($plik)) {
        $zapisany = json_decode((string) @file_get_contents($plik), true);

        if (is_array($zapisany)) {
            $cache = $zapisany;
        }
    }

    $wynik = [];
    $doPobrania = [];

    foreach (array_unique(array_map('intval', $ids)) as $id) {
        if ($id <= 0) {
            continue;
        }

        if (isset($cache[$id]['url'], $cache[$id]['ts']) && ($teraz - (int) $cache[$id]['ts']) < $ttl) {
            $wynik[$id] = (string) $cache[$id]['url'];
        } else {
            $doPobrania[$id] = $id;
        }
    }

    if (!$doPobrania) {
        return $wynik;
    }

    $adresy = [];

    foreach ($doPobrania as $id) {
        $url = 'https://api.discogs.com/releases/' . $id;

        if (!empty($discogs_token)) {
            $url .= '?token=' . urlencode($discogs_token);
        }

        $adresy[$id] = $url;
    }

    foreach (pobierzJsonWiele($adresy) as $id => $dane) {
        $okladka = is_array($dane) ? znajdzOkladke($dane) : '';
        $wynik[$id] = $okladka;

        if (is_array($dane)) {
            $cache[$id] = ['url' => $okladka, 'ts' => $teraz];
        }
    }

    if ($cache) {
        @file_put_contents($plik, json_encode($cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return $wynik;
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

    // Discogs nie zwraca już miniatur w wynikach wyszukiwania,
    // więc dociągamy okładkę z danych konkretnego wydania.
    $brakujaceOkladki = [];

    foreach ($wyniki as $indeks => $album) {
        if ($album['okladka'] === '' && $album['discogs_id'] > 0) {
            $brakujaceOkladki[$indeks] = $album['discogs_id'];
        }
    }

    if ($brakujaceOkladki) {
        $okladki = pobierzOkladkiDiscogs($brakujaceOkladki);

        foreach ($brakujaceOkladki as $indeks => $id) {
            if (!empty($okladki[$id])) {
                $wyniki[$indeks]['okladka'] = $okladki[$id];
            }
        }
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

    $okladka = znajdzOkladke($dane);

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
?>
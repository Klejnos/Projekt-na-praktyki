<?php
// Łącznik między formularzami muzyki/utworów a API Discogs.
// Discogs wymaga nagłówka User-Agent. Klucz tokena jest opcjonalny.
//
// Najważniejsze zasady działania:
// - odpowiedzi Discogs są cache'owane, żeby nie przekraczać limitu zapytań,
// - po błędzie 429/403 włączany jest krótki cooldown,
// - podpowiedzi utworów zawsze uzupełniamy danymi z lokalnej bazy,
//   więc formularz działa także wtedy, gdy Discogs nie odpowiada,
// - okładki albumów dociągamy tylko w granicach pozostałego limitu.

require_once __DIR__ . '/assets/config/klucze.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] != true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Brak autoryzacji.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Pozostały limit zapytań Discogs, odczytywany z nagłówka odpowiedzi.
$discogs_limit_remaining = null;

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

// Krótka przerwa po błędzie Discogs, żeby nie blokować formularzy kolejnymi zapytaniami.
function plikCooldownDiscogs(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wypozyczalnia_discogs_cooldown.txt';
}

function discogsWCoolownie(): bool
{
    $plik = plikCooldownDiscogs();

    return is_file($plik) && (int) @file_get_contents($plik) > time();
}

function ustawCooldownDiscogs(int $sekundy = 60): void
{
    @file_put_contents(plikCooldownDiscogs(), (string) (time() + $sekundy), LOCK_EX);
}

// Pobiera pojedynczy JSON z Discogs.
// Zwraca null, gdy Discogs jest w cooldownie, zwróci błąd lub nie odpowie.
function pobierzJson(string $url): ?array
{
    global $discogs_user_agent;

    // Po ostatnim 429/403 nie zamawiamy kolejnych zapytań przez jakiś czas.
    if (discogsWCoolownie()) {
        return null;
    }

    // Podstawowa ścieżka: cURL dostępny w XAMPP.
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $discogs_user_agent,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            // Zapamiętujemy pozostały limit, żeby nie dociągać zbyt wielu okładek.
            CURLOPT_HEADERFUNCTION => function ($ch, $naglowek) {
                if (stripos($naglowek, 'x-discogs-ratelimit-remaining:') === 0) {
                    $GLOBALS['discogs_limit_remaining'] = (int) trim(substr($naglowek, 30));
                }

                return strlen($naglowek);
            },
        ]);

        $odpowiedz = curl_exec($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($odpowiedz === false || $kod < 200 || $kod >= 300) {
            // 429/403 oznacza wyczerpany limit; przy błędzie sieci pauza jest krótsza.
            if ($kod === 429 || $kod === 403) {
                ustawCooldownDiscogs(60);
            } elseif ($odpowiedz === false) {
                ustawCooldownDiscogs(30);
            }

            return null;
        }

        $json = json_decode($odpowiedz, true);

        return is_array($json) ? $json : null;
    }

    // Wariant awaryjny dla serwerów bez cURL.
    $kontekst = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nUser-Agent: " . $discogs_user_agent . "\r\n",
            'timeout' => 8,
        ],
    ]);

    $odpowiedz = @file_get_contents($url, false, $kontekst);

    if ($odpowiedz === false) {
        if (isset($http_response_header)) {
            foreach ($http_response_header as $naglowek) {
                if (preg_match('#^HTTP/\S+\s+(429|403)#', $naglowek)) {
                    ustawCooldownDiscogs(60);
                }
            }
        } else {
            ustawCooldownDiscogs(30);
        }

        return null;
    }

    // W wariancie awaryjnym nagłówki są dostępne w tablicy $http_response_header.
    if (isset($http_response_header)) {
        foreach ($http_response_header as $naglowek) {
            if (stripos($naglowek, 'x-discogs-ratelimit-remaining:') === 0) {
                $GLOBALS['discogs_limit_remaining'] = (int) trim(substr($naglowek, 30));
            }
        }
    }

    $json = json_decode($odpowiedz, true);

    return is_array($json) ? $json : null;
}

// Pobiera kilka adresów JSON równolegle, żeby dociąganie okładek nie spowalniało wyszukiwania.
function pobierzJsonWiele(array $adresy): array
{
    global $discogs_user_agent;

    // W cooldownie nie wykonujemy żadnych zapytań — od razu same null-e.
    if (discogsWCoolownie()) {
        return array_map(static fn () => null, $adresy);
    }

    // Awaryjnie pobieramy po kolei, jeśli serwer nie ma curl_multi.
    if (!function_exists('curl_multi_init')) {
        $wynik = [];

        foreach ($adresy as $klucz => $url) {
            $wynik[$klucz] = pobierzJson($url);
        }

        return $wynik;
    }

    $multi = curl_multi_init();
    $uchwyty = [];

    // Każde wydanie to osobny uchwyt cURL dodany do wspólnej puli.
    foreach ($adresy as $klucz => $url) {
        $ch = curl_init($url);

        if ($ch === false) {
            $uchwyty[$klucz] = null;
            continue;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $discogs_user_agent,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        curl_multi_add_handle($multi, $ch);
        $uchwyty[$klucz] = $ch;
    }

    // Czekamy na zakończenie wszystkich równoległych zapytań.
    do {
        $status = curl_multi_exec($multi, $aktywne);

        if ($aktywne) {
            curl_multi_select($multi, 1.0);
        }
    } while ($aktywne && $status === CURLM_OK);

    $wynik = [];

    // Odczytujemy odpowiedzi i od razu wykrywamy wyczerpany limit Discogs.
    foreach ($uchwyty as $klucz => $ch) {
        if ($ch === null) {
            $wynik[$klucz] = null;
            continue;
        }

        $tresc = curl_multi_getcontent($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);

        if ($kod === 429 || $kod === 403) {
            ustawCooldownDiscogs(60);
        }

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

// Wyciąga listę utworów z danych wydania Discogs (także podutwory).
function pobierzUtwory(array $album): array
{
    $utwory = [];

    foreach (($album['tracklist'] ?? []) as $utwor) {
        if (($utwor['type_'] ?? '') === 'heading') {
            continue;
        }

        $tytul = trim((string) ($utwor['title'] ?? ''));

        if ($tytul === '') {
            continue;
        }

        $utwory[] = [
            'numer' => mb_substr(trim((string) ($utwor['position'] ?? '')), 0, 20, 'UTF-8'),
            'tytul' => mb_substr($tytul, 0, 200, 'UTF-8'),
            'czas' => mb_substr(trim((string) ($utwor['duration'] ?? '')), 0, 20, 'UTF-8'),
        ];

        foreach (($utwor['sub_tracks'] ?? []) as $podutwor) {
            $podTytul = trim((string) ($podutwor['title'] ?? ''));

            if ($podTytul === '') {
                continue;
            }

            $numer = trim(($utwor['position'] ?? '') . '.' . ($podutwor['position'] ?? ''));

            $utwory[] = [
                'numer' => mb_substr($numer, 0, 20, 'UTF-8'),
                'tytul' => mb_substr($podTytul, 0, 200, 'UTF-8'),
                'czas' => mb_substr(trim((string) ($podutwor['duration'] ?? '')), 0, 20, 'UTF-8'),
            ];
        }
    }

    return $utwory;
}

// Cache pełnych danych wydań Discogs (okładka, utwory, wykonawca itd.).
function plikCacheDiscogs(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wypozyczalnia_discogs.json';
}

// Buduje pełne dane albumu na podstawie odpowiedzi Discogs.
// Zwraca dokładnie taki format, jakiego oczekuje frontend (details).
function zbudujAlbumDiscogs(array $dane): array
{
    // Discogs podaje wykonawców i gatunki jako tablice.
    $wykonawca = implode(', ', array_column($dane['artists'] ?? [], 'name'));
    $gatunek = implode(', ', array_slice(array_merge($dane['genres'] ?? [], $dane['styles'] ?? []), 0, 4));

    // Format sklejamy z nazwy nośnika i jego opisów (np. CD, Album, Reissue).
    $formaty = [];

    foreach (($dane['formats'] ?? []) as $format) {
        if (!empty($format['name'])) {
            $formaty[] = $format['name'];
        }

        foreach (array_slice($format['descriptions'] ?? [], 0, 3) as $opis) {
            $formaty[] = $opis;
        }
    }

    // Discogs zwraca link względny, a aplikacja potrzebuje pełnego adresu.
    $discogsUri = $dane['uri'] ?? '';

    if ($discogsUri !== '' && strpos($discogsUri, 'http') !== 0) {
        $discogsUri = 'https://www.discogs.com' . $discogsUri;
    }

    return [
        'discogs_id' => (int) $dane['id'],
        'tytul' => $dane['title'] ?? '',
        'wykonawca' => $wykonawca,
        'rok' => !empty($dane['year']) ? (int) $dane['year'] : '',
        'gatunek' => $gatunek,
        'format' => implode(', ', array_slice(array_unique($formaty), 0, 4)),
        'okladka' => znajdzOkladke($dane),
        'utwory' => pobierzUtwory($dane),
        'discogs_uri' => $discogsUri,
    ];
}

// Pobiera pełne dane wydań Discogs, korzystając z cache i równoległych zapytań.
// Zwraca tablicę [id_wydania => dane_albumu|null].
function pobierzAlbumyDiscogs(array $ids): array
{
    global $discogs_token;

    $ttl = 60 * 60 * 24 * 30; // 30 dni
    $teraz = time();
    $plik = plikCacheDiscogs();
    $cache = [];

    // 1. Odczyt zapisanych wcześniej wydań.
    if (is_file($plik)) {
        $zapisany = json_decode((string) @file_get_contents($plik), true);

        if (is_array($zapisany)) {
            $cache = $zapisany;
        }
    }

    $wynik = [];
    $doPobrania = [];

    // 2. Rozdzielenie na wpisy z cache i te, które trzeba pobrać.
    foreach (array_unique(array_map('intval', $ids)) as $id) {
        if ($id <= 0) {
            continue;
        }

        if (isset($cache[$id]['ts'], $cache[$id]['album'])
            && ($teraz - (int) $cache[$id]['ts']) < $ttl
            && is_array($cache[$id]['album'])) {
            $wynik[$id] = $cache[$id]['album'];
        } else {
            $doPobrania[$id] = $id;
        }
    }

    if (!$doPobrania) {
        return $wynik;
    }

    $adresy = [];

    // 3. Przygotowanie adresów; token jest opcjonalny.
    foreach ($doPobrania as $id) {
        $url = 'https://api.discogs.com/releases/' . $id;

        if (!empty($discogs_token)) {
            $url .= '?token=' . urlencode($discogs_token);
        }

        $adresy[$id] = $url;
    }

    // 4. Równoległe pobranie brakujących wydań.
    foreach (pobierzJsonWiele($adresy) as $id => $dane) {
        if (!is_array($dane) || empty($dane['id'])) {
            $wynik[$id] = null;
            continue;
        }

        $album = zbudujAlbumDiscogs($dane);
        $wynik[$id] = $album;
        $cache[$id] = ['album' => $album, 'ts' => $teraz];
    }

    // 5. Zapis cache z limitem 200 wpisów, żeby plik nie rósł bez końca.
    if ($cache) {
        if (count($cache) > 200) {
            $cache = array_slice($cache, -200, null, true);
        }

        @file_put_contents($plik, json_encode($cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return $wynik;
}

// Zamienia wyniki wyszukiwania Discogs na podpowiedzi konkretnych utworów.
// W podpowiedzi zwracamy tytuł utworu oraz album, do którego należy.
function podpowiedziUtworow(array $wyniki, string $q): array
{
    $wydania = [];

    foreach ($wyniki as $album) {
        $id = (int) ($album['id'] ?? 0);

        if ($id <= 0) {
            continue;
        }

        // Discogs zwraca tytuł w formacie "Wykonawca - Album".
        // Formularz ma osobne pola, więc rozdzielamy go już tutaj.
        $albumTytul = $album['title'] ?? '';
        $wykonawca = '';

        if (strpos($albumTytul, ' - ') !== false) {
            [$wykonawca, $albumTytul] = explode(' - ', $albumTytul, 2);
        }

        $wydania[$id] = [
            'album_tytul' => $albumTytul,
            'wykonawca' => $wykonawca,
            'rok' => !empty($album['year']) ? (int) $album['year'] : '',
            'gatunek' => implode(', ', array_slice(array_merge($album['genre'] ?? [], $album['style'] ?? []), 0, 4)),
            'okladka' => $album['cover_image'] ?? ($album['thumb'] ?? ''),
            'discogs_uri' => !empty($album['uri'])
                ? 'https://www.discogs.com' . $album['uri']
                : '',
        ];
    }

    // Pełne dane wydań (w tym lista utworów) pobieramy równolegle i cache'ujemy.
    $daneWydan = $wydania ? pobierzAlbumyDiscogs(array_keys($wydania)) : [];
    $podpowiedzi = [];
    $widziane = [];

    foreach ($wydania as $id => $album) {
        // Jeżeli szczegóły mają lepszą okładkę, zastępujemy nią wynik z wyszukiwania.
        if (!empty($daneWydan[$id]['okladka'])) {
            $album['okladka'] = $daneWydan[$id]['okladka'];
        }

        foreach (($daneWydan[$id]['utwory'] ?? []) as $utwor) {
            // Pokazujemy tylko utwory pasujące do wpisanej frazy.
            if (mb_stripos($utwor['tytul'], $q, 0, 'UTF-8') === false) {
                continue;
            }

            // Ten sam tytuł w obrębie jednego wydania pokazujemy tylko raz.
            $klucz = mb_strtolower($utwor['tytul'], 'UTF-8') . '|' . $id;

            if (isset($widziane[$klucz])) {
                continue;
            }

            $widziane[$klucz] = true;

            $podpowiedzi[] = [
                'tytul' => $utwor['tytul'],
                'numer' => $utwor['numer'] ?? '',
                'czas' => $utwor['czas'] ?? '',
                'album_tytul' => $album['album_tytul'],
                'wykonawca' => $album['wykonawca'],
                'rok' => $album['rok'],
                'gatunek' => $album['gatunek'],
                'okladka' => $album['okladka'],
                'discogs_id' => $id,
                'discogs_uri' => $album['discogs_uri'],
            ];
        }
    }

    return $podpowiedzi;
}

// Podpowiedzi utworów z lokalnej bazy — działają także wtedy, gdy Discogs nie odpowiada.
// Format wyniku jest taki sam jak dla Discogs, plus album_id potrzebne do ponownego użycia albumu.
function pobierzLokalneUtwory(string $q): array
{
    require __DIR__ . '/polaczenie.php';

    try {
        $db = new mysqli($host, $db_user, $db_password, $db_name);

        if ($db->connect_errno) {
            return [];
        }

        $db->set_charset('utf8mb4');

        // Szukamy po tytule utworu, albumie i wykonawcy; najlepiej dopasowane tytuły są pierwsze.
        $stmt = $db->prepare(
            "SELECT u.album_id, u.tytul, u.numer, u.czas,
                    a.tytul AS album_tytul, a.wykonawca, a.rok, a.gatunek,
                    a.okladka, a.discogs_id, a.discogs_uri
             FROM utwory u
             INNER JOIN albumy a ON a.id = u.album_id
             WHERE u.tytul LIKE ? OR a.tytul LIKE ? OR a.wykonawca LIKE ?
             ORDER BY (u.tytul LIKE ?) DESC, u.id ASC
             LIMIT 5"
        );

        if (!$stmt) {
            $db->close();
            return [];
        }

        $parametr = '%' . $q . '%';
        $stmt->bind_param('ssss', $parametr, $parametr, $parametr, $parametr);
        $stmt->execute();
        $wynik = $stmt->get_result();
        $utwory = [];

        while ($row = $wynik->fetch_assoc()) {
            $utwory[] = [
                'album_id' => (int) $row['album_id'],
                'tytul' => $row['tytul'],
                'numer' => $row['numer'] ?? '',
                'czas' => $row['czas'] ?? '',
                'album_tytul' => $row['album_tytul'],
                'wykonawca' => $row['wykonawca'] ?? '',
                'rok' => !empty($row['rok']) ? (int) $row['rok'] : '',
                'gatunek' => $row['gatunek'] ?? '',
                'okladka' => $row['okladka'] ?? '',
                'discogs_id' => !empty($row['discogs_id']) ? (int) $row['discogs_id'] : null,
                'discogs_uri' => $row['discogs_uri'] ?? '',
            ];
        }

        $stmt->close();
        $db->close();

        return $utwory;
    } catch (Throwable $e) {
        // Baza może być chwilowo niedostępna — wtedy po prostu nie ma podpowiedzi lokalnych.
        return [];
    }
}

// Łączy podpowiedzi z Discogs i z bazy, usuwając duplikaty.
// Wyniki z Discogs mają pierwszeństwo, lokalne uzupełniają listę.
function scalPodpowiedziUtworow(array $zdalne, array $lokalne, int $limit = 5): array
{
    $wynik = [];
    $widziane = [];

    foreach (array_merge($zdalne, $lokalne) as $utwor) {
        // Klucz: tytuł utworu + album (bez wielkości liter).
        $tytul = mb_strtolower(trim((string) ($utwor['tytul'] ?? '')), 'UTF-8');
        $album = mb_strtolower(trim((string) ($utwor['album_tytul'] ?? '')), 'UTF-8');
        $klucz = $tytul . '|' . $album;

        if ($tytul === '' || isset($widziane[$klucz])) {
            continue;
        }

        $widziane[$klucz] = true;
        $wynik[] = $utwor;

        if (count($wynik) >= $limit) {
            break;
        }
    }

    return $wynik;
}

$action = $_GET['action'] ?? '';

// Endpointy API:
// - search       -> podpowiedzi albumów (formularz dodawania muzyki),
// - search_tracks-> podpowiedzi utworów (formularz dodawania utworu),
// - details      -> pełne dane wybranego albumu.
if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q, 'UTF-8') < 2) {
        odpowiedz(['ok' => true, 'results' => []]);
    }

    $parametry = [
        'q' => $q,
        'type' => 'release',
        'per_page' => 8,
    ];

    if (!empty($discogs_token)) {
        $parametry['token'] = $discogs_token;
    }

    $dane = pobierzJson('https://api.discogs.com/database/search?' . http_build_query($parametry));

    if (!$dane || !isset($dane['results'])) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać wyników z Discogs.']);
    }

    $wyniki = [];

    // Mapowanie wyników Discogs na pola używane przez formularz.
    foreach (array_slice($dane['results'], 0, 8) as $album) {
        $okladka = $album['cover_image'] ?? '';

        if ($okladka === '') {
            $okladka = $album['thumb'] ?? '';
        }

        $gatunek = implode(', ', array_slice(array_merge($album['genre'] ?? [], $album['style'] ?? []), 0, 4));
        $format = implode(', ', array_slice($album['format'] ?? [], 0, 4));

        $wyniki[] = [
            'discogs_id' => (int) ($album['id'] ?? 0),
            'tytul' => $album['title'] ?? '',
            'rok' => !empty($album['year']) ? (int) $album['year'] : '',
            'gatunek' => $gatunek,
            'format' => $format,
            'okladka' => $okladka,
            'discogs_uri' => !empty($album['uri'])
                ? 'https://www.discogs.com' . $album['uri']
                : '',
        ];
    }

    // Discogs często nie zwraca okładek w wynikach wyszukiwania.
    // Dociągamy je tylko w granicach pozostałego limitu zapytań,
    // zostawiając zapas na późniejsze wyszukiwania i wybór albumu.
    $pozostalo = $GLOBALS['discogs_limit_remaining'] ?? null;
    $ileOkladek = $pozostalo === null ? 4 : max(0, min(count($wyniki), $pozostalo - 2));

    if ($ileOkladek > 0) {
        $doOkladek = [];

        foreach (array_slice($wyniki, 0, $ileOkladek, true) as $indeks => $album) {
            if (empty($album['okladka']) && (int) $album['discogs_id'] > 0) {
                $doOkladek[$indeks] = (int) $album['discogs_id'];
            }
        }

        if ($doOkladek) {
            $daneWydan = pobierzAlbumyDiscogs(array_values($doOkladek));

            foreach ($doOkladek as $indeks => $id) {
                if (!empty($daneWydan[$id]['okladka'])) {
                    $wyniki[$indeks]['okladka'] = $daneWydan[$id]['okladka'];
                }
            }
        }
    }

    odpowiedz(['ok' => true, 'results' => $wyniki]);
}

if ($action === 'search_tracks') {
    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q, 'UTF-8') < 2) {
        odpowiedz(['ok' => true, 'results' => []]);
    }

    $bazowe = [
        'type' => 'release',
        'per_page' => 5,
    ];

    if (!empty($discogs_token)) {
        $bazowe['token'] = $discogs_token;
    }

    $wyniki = [];
    // Flaga dla frontendu: czy Discogs w ogóle odpowiedział.
    $discogsDostepny = false;

    // Najpierw szukamy wydań po tytule utworu (parametr track).
    $dane = pobierzJson('https://api.discogs.com/database/search?' . http_build_query(
        array_merge($bazowe, ['track' => $q])
    ));

    if ($dane !== null) {
        $discogsDostepny = true;

        if (!empty($dane['results'])) {
            $wyniki = podpowiedziUtworow($dane['results'], $q);
        }
    }

    // Gdy wyszukiwanie po utworze nic nie da, próbujemy zapytania ogólnego.
    if (!$wyniki) {
        $dane = pobierzJson('https://api.discogs.com/database/search?' . http_build_query(
            array_merge($bazowe, ['q' => $q])
        ));

        if ($dane !== null) {
            $discogsDostepny = true;

            if (!empty($dane['results'])) {
                $wyniki = podpowiedziUtworow($dane['results'], $q);
            }
        }
    }

    // Zawsze dokładamy podpowiedzi z bazy — mają okładki i działają bez Discogs.
    $lokalne = pobierzLokalneUtwory($q);
    $wyniki = scalPodpowiedziUtworow($wyniki, $lokalne, 5);

    odpowiedz([
        'ok' => true,
        'results' => $wyniki,
        'discogs_error' => !$discogsDostepny,
    ]);
}

if ($action === 'details') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        odpowiedz(['ok' => false, 'error' => 'Brak poprawnego ID albumu.']);
    }

    // Szczegóły są cache'owane, więc ponowny wybór tego samego albumu nie pyta Discogs.
    $album = pobierzAlbumyDiscogs([$id])[$id] ?? null;

    if ($album === null) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać szczegółów albumu z Discogs.']);
    }

    odpowiedz(['ok' => true, 'album' => $album]);
}

odpowiedz(['ok' => false, 'error' => 'Nieznana akcja.']);
?>
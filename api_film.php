<?php
// Łącznik między formularzem a TMDB (dane filmu) i OMDb (nagrody).
// Klucze API są po stronie serwera, więc przeglądarka ich nie widzi.

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
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
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
            'header' => "Accept: application/json\r\n",
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

    $url = 'https://api.themoviedb.org/3/search/movie?' . http_build_query([
        'api_key' => $tmdb_api_key,
        'query' => $q,
        'language' => 'pl-PL',
        'include_adult' => 'false',
        'page' => 1,
    ]);

    $dane = pobierzJson($url);

    if (!$dane || !isset($dane['results'])) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać wyników z TMDB.']);
    }

    $wyniki = [];

    foreach (array_slice($dane['results'], 0, 8) as $film) {
        $rok = !empty($film['release_date']) ? substr($film['release_date'], 0, 4) : '';

        $wyniki[] = [
            'tmdb_id' => (int) $film['id'],
            'tytul' => $film['title'] ?? '',
            'tytul_oryginalny' => $film['original_title'] ?? '',
            'rok' => $rok,
            'ocena' => $film['vote_average'] ?? null,
            'opis' => $film['overview'] ?? '',
            'okladka' => !empty($film['poster_path'])
                ? 'https://image.tmdb.org/t/p/w200' . $film['poster_path']
                : '',
        ];
    }

    odpowiedz(['ok' => true, 'results' => $wyniki]);
}

if ($action === 'details') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        odpowiedz(['ok' => false, 'error' => 'Brak poprawnego ID filmu.']);
    }

    $url = 'https://api.themoviedb.org/3/movie/' . $id . '?' . http_build_query([
        'api_key' => $tmdb_api_key,
        'language' => 'pl-PL',
        'append_to_response' => 'credits,external_ids',
    ]);

    $dane = pobierzJson($url);

    if (!$dane || empty($dane['id'])) {
        odpowiedz(['ok' => false, 'error' => 'Nie udało się pobrać szczegółów filmu z TMDB.']);
    }

    $imdbId = $dane['imdb_id'] ?? '';

    if ($imdbId === '' && !empty($dane['external_ids']['imdb_id'])) {
        $imdbId = $dane['external_ids']['imdb_id'];
    }

    $gatunek = implode(', ', array_column($dane['genres'] ?? [], 'name'));
    $jezyk = implode(', ', array_column($dane['spoken_languages'] ?? [], 'name'));
    $produkcja = implode(', ', array_column($dane['production_companies'] ?? [], 'name'));

    $rezyser = '';

    foreach (($dane['credits']['crew'] ?? []) as $osoba) {
        if (($osoba['job'] ?? '') === 'Director') {
            $rezyser = $osoba['name'] ?? '';
            break;
        }
    }

    $rok = !empty($dane['release_date']) ? (int) substr($dane['release_date'], 0, 4) : null;
    $ocena = $dane['vote_average'] ?? null;
    $nagrody = '';

    if ($imdbId !== '') {
        $omdb = pobierzJson('https://www.omdbapi.com/?' . http_build_query([
            'apikey' => $omdb_api_key,
            'i' => $imdbId,
            'plot' => 'full',
        ]));

        if ($omdb && ($omdb['Response'] ?? '') === 'True') {
            if (!empty($omdb['Awards']) && $omdb['Awards'] !== 'N/A') {
                $nagrody = $omdb['Awards'];
            }

            if (!empty($omdb['imdbRating']) && $omdb['imdbRating'] !== 'N/A') {
                $ocena = $omdb['imdbRating'];
            }

            if ($rezyser === '' && !empty($omdb['Director']) && $omdb['Director'] !== 'N/A') {
                $rezyser = $omdb['Director'];
            }

            if ($jezyk === '' && !empty($omdb['Language']) && $omdb['Language'] !== 'N/A') {
                $jezyk = $omdb['Language'];
            }

            if ($produkcja === '' && !empty($omdb['Production']) && $omdb['Production'] !== 'N/A') {
                $produkcja = $omdb['Production'];
            }

            if (empty($dane['runtime']) && !empty($omdb['Runtime']) && $omdb['Runtime'] !== 'N/A') {
                $dane['runtime'] = (int) $omdb['Runtime'];
            }
        }
    }

    $budzet = isset($dane['budget']) ? (int) $dane['budget'] : 0;

    odpowiedz([
        'ok' => true,
        'film' => [
            'tmdb_id' => (int) $dane['id'],
            'imdb_id' => $imdbId,
            'tytul' => $dane['title'] ?? '',
            'tytul_oryginalny' => $dane['original_title'] ?? '',
            'rok' => $rok,
            'gatunek' => $gatunek,
            'ocena' => $ocena,
            'rezyser' => $rezyser,
            'czas_trwania' => $dane['runtime'] ?? null,
            'jezyk' => $jezyk,
            'produkcja' => $produkcja,
            'budzet' => $budzet > 0 ? $budzet : null,
            'nagrody' => $nagrody,
            'opis' => $dane['overview'] ?? '',
            'okladka' => !empty($dane['poster_path'])
                ? 'https://image.tmdb.org/t/p/w500' . $dane['poster_path']
                : '',
        ],
    ]);
}

odpowiedz(['ok' => false, 'error' => 'Nieznana akcja.']);

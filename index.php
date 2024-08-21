<?php
$apiToken = getenv('GENIUS_API_TOKEN');
$songId = $_GET['song_id'] ?? null;
$searchQuery = $_GET['search'] ?? null;
$lyrics = "";
$albumCover = "";
$title = "";
$year = "";
$artist = "";
$album = "";
$suggestions = [];

function apiRequest($url, $apiToken) {
    $headers = ["Authorization: Bearer {$apiToken}"];
    $context = stream_context_create([
        'http' => ['header' => implode("\r\n", $headers)]
    ]);
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        throw new Exception("API request failed");
    }
    return json_decode($response, true);
}

function sanitizeLyrics($html) {
    $html = preg_replace('/class="[^"]*"/i', '', $html);
    $html = preg_replace('/id="[^"]*"/i', '', $html);
    $html = preg_replace('/<a[^>]*href=["\'][^"\']*["\'][^>]*>(.*?)<\/a>/i', '$1', $html);
    return strip_tags($html, '<br><p><div><span>');
}

try {
    if ($songId) {
        $apiUrl = "https://api.genius.com/songs/{$songId}";
        $data = apiRequest($apiUrl, $apiToken);

        $songData = $data['response']['song'];
        $lyricsPath = $songData['path'];
        $albumCover = $songData['song_art_image_url'];
        $title = $songData['title'];
        $year = $songData['release_date'] ? date('Y', strtotime($songData['release_date'])) : 'Unknown';
        $artist = $songData['primary_artist']['name'];
        $album = $songData['album']['name'] ?? 'Unknown';

        $lyricsPage = file_get_contents("https://genius.com{$lyricsPath}");
        preg_match('/<div data-lyrics-container="true" class="Lyrics__Container-sc-1ynbvzw-1 kUgSbL">(.*?)<\/div>/s', $lyricsPage, $matches);
        $lyrics = sanitizeLyrics($matches[1] ?? "Lyrics not found");
    }

    if ($searchQuery) {
        $apiUrl = "https://api.genius.com/search?q=" . urlencode($searchQuery);
        $data = apiRequest($apiUrl, $apiToken);

        foreach ($data['response']['hits'] as $hit) {
            $suggestions[] = [
                'id' => $hit['result']['id'],
                'title' => $hit['result']['title'],
                'artist' => $hit['result']['primary_artist']['name'],
            ];
        }
    }
} catch (Exception $e) {
    // Handle exception (e.g., log error, display message)
    echo 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeadSheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
</head>
<body>

    <div class="sidebar p-3">
        <form id="search-form" method="get" action="/">
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="search-box" name="search" placeholder="Search...">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <div id="suggestions" class="list-group">
            <?php foreach ($suggestions as $suggestion): ?>
                <a href="?song_id=<?php echo htmlspecialchars($suggestion['id']); ?>" class="list-group-item list-group-item-action">
                    <?php echo htmlspecialchars($suggestion['title']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($suggestion['artist']); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($songId): ?>
            <small class="muted">
                Genius ID:
                <u><a href="https://genius.com/songs/<?php echo htmlspecialchars($songId); ?>" target="_blank">
                        <?php echo htmlspecialchars($songId); ?>
                        <i class="fas fa-external-link-alt"></i>
                    </a></u>
            </small>
        <?php endif; ?>
    </div>

    <div class="preview flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="m-0">LeadSheet</h2>
            <div class="toolbar btn-group" role="group" aria-label="Basic outlined example">
                <button type="button" class="btn btn-outline-primary" id="print-btn">
                    <i class="fas fa-print"></i>
                    <label class="btn-label"> Print</label>
                </button>
                <button type="button" class="btn btn-outline-primary" id="save-btn">
                    <i class="fas fa-download"></i>
                    <label class="btn-label"> Save</label>
                </button>
                <button type="button" class="btn btn-outline-primary" id="back-btn">
                    <i class="fa fa-search"></i>
                    <label class="btn-label"> Back</label>
                </button>
                <button type="button" class="btn btn-outline-primary theme-toggler" id="toggle-dark-mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
        <div id='info' class="info container">
            <?php if ($songId): ?>
                <div class="card mb-3">
                    <div class="row g-0">
                        <img src="<?php echo htmlspecialchars($albumCover); ?>"
                            class="col-md-4 img-fluid rounded-start albumCover"
                            alt="<?php echo htmlspecialchars($title); ?>">
                        <div class="col-md-8 card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($title); ?></h5>
                            <p class="card-text"><strong>Artist:</strong> <?php echo htmlspecialchars($artist); ?>
                            </p>
                            <p class="card-text"><strong>Album:</strong> <?php echo htmlspecialchars($album); ?></p>
                            <p class="card-text"><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="lyrics-container"><?php echo $lyrics; ?></div>
        </div>
        <footer class="mastfoot text-center mt-4">
            <small>© 2024 &#169; <a class="sonoth" href="https://sonothamin.github.io">Sonoth Amin</a></small>
        </footer>
    </div>

    <script>
        document.getElementById('print-btn').addEventListener('click', function () {
            var songId = new URLSearchParams(window.location.search).get('song_id');
            if (songId) {
                window.location.href = 'print.php?song_id=' + encodeURIComponent(songId);
            } else {
                alert('No song selected for printing.');
            }
        });

        document.getElementById('save-btn').addEventListener('click', function () {
            const blob = new Blob([document.querySelector('.lyrics-container').innerText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lyrics.txt';
            a.click();
            URL.revokeObjectURL(url);
        });

        document.addEventListener('DOMContentLoaded', function () {
            const previewPane = document.querySelector('.preview');
            const sidebar = document.querySelector('.sidebar');
            const metaCard = document.querySelector('.lyrics-container');
            const backButton = document.getElementById('back-btn');

            if (metaCard && metaCard.innerHTML.trim() === '') {
                previewPane.classList.remove('active');
                sidebar.classList.remove('hidden');
            } else {
                if (window.innerWidth <= 767.98) {
                    previewPane.classList.add('active');
                    sidebar.classList.add('hidden');
                }
            }

            document.getElementById('suggestions').addEventListener('click', function (e) {
                if (e.target.closest('a')) {
                    if (window.innerWidth <= 767.98) {
                        previewPane.classList.add('active');
                        sidebar.classList.add('hidden');
                    }
                }
            });

            backButton.addEventListener('click', function () {
                previewPane.classList.remove('active');
                sidebar.classList.remove('hidden');
            });

            document.getElementById('toggle-dark-mode').addEventListener('click', function () {
                document.body.classList.toggle('dark-mode');
            });
        });
    </script>
</body>
</html>

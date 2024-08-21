<?php
$apiToken = "rNjA8km9C0oxBUkHjQ_wNfwB3CEPbsMyQyLIXfQK_Qj2bGSC6JqByIOtoaFxnTvX";
$songId = $_GET['song_id'] ?? null;
$lyrics = "";
$albumCover = "";
$title = "";
$year = "";
$artist = "";
$album = "";

function sanitizeLyrics($html)
{
    $html = preg_replace('/class="[^"]*"/i', '', $html);
    $html = preg_replace('/id="[^"]*"/i', '', $html);
    $html = preg_replace('/<a[^>]*href=["\'][^"\']*["\'][^>]*>(.*?)<\/a>/i', '$1', $html);
    return strip_tags($html, '<br><p><div><span>');
}

if ($songId) {
    $apiUrl = "https://api.genius.com/songs/{$songId}";
    $headers = ["Authorization: Bearer {$apiToken}"];
    $response = file_get_contents($apiUrl, false, stream_context_create([
        'http' => ['header' => implode("\r\n", $headers)]
    ]));
    $data = json_decode($response, true);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Lyrics</title>
    <link href="/print.css" rel="stylesheet">
</head>
<body>
    <div class="card">
        <div class="card-inner">
            <div class="card-image">
                <img src="<?php echo htmlspecialchars($albumCover); ?>" alt="<?php echo htmlspecialchars($title); ?>">
            </div>
            <div class="card-content">
                <h2 class="card-title"><?php echo htmlspecialchars($title); ?></h2>
                <p class="card-artist"><strong>Artist:</strong> <?php echo htmlspecialchars($artist); ?></p>
                <p class="card-album"><strong>Album:</strong> <?php echo htmlspecialchars($album); ?></p>
                <p class="card-year"><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
            </div>
        </div>
    </div>
    <div class="lyrics-container">
        <?php echo $lyrics; ?>
    </div>
    <script>
        window.print();
    </script>
</body>
</html>

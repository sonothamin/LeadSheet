<?php
$apiToken = "rNjA8km9C0oxBUkHjQ_wNfwB3CEPbsMyQyLIXfQK_Qj2bGSC6JqByIOtoaFxnTvX";
$songId = $_GET['song_id'] ?? null;
$lyrics = "";

if ($songId) {
    $apiUrl = "https://api.genius.com/songs/{$songId}";
    $headers = ["Authorization: Bearer {$apiToken}"];
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $lyricsPath = $data['response']['song']['path'];
    
    $lyricsPage = file_get_contents("https://genius.com{$lyricsPath}");
    preg_match('/<div data-lyrics-container="true" class="Lyrics__Container-sc-1ynbvzw-1 kUgSbL">(.*?)<\/div>/s', $lyricsPage, $matches);
    $lyrics = $matches[1] ?? "Lyrics not found";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genius Lyrics Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }
        .dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }
        .sidebar { width: 250px; }
        .preview { flex: 1; padding: 15px; }
        .sidebar-hidden { display: none; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .preview { flex: none; }
            .sidebar-hidden { display: block; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar bg-light p-3">
            <h4>Search Songs</h4>
            <form id="search-form">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="search-box" placeholder="Search..." aria-label="Search">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <div id="suggestions" class="list-group"></div>
            <?php if ($songId): ?>
            <small class="text-muted">Genius ID: <?php echo htmlspecialchars($songId); ?></small>
            <?php endif; ?>
        </div>
        <!-- Preview Section -->
        <div class="preview d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0">Lyrics Preview</h2>
                <div>
                    <button class="btn btn-secondary me-2" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-secondary" id="save-btn"><i class="fas fa-download"></i> Save</button>
                    <button class="btn btn-light ms-2" id="toggle-dark-mode"><i class="fas fa-moon"></i></button>
                </div>
            </div>
            <div class="lyrics-container">
                <?php echo $lyrics; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('search-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const query = document.getElementById('search-box').value;
            fetch(`https://api.genius.com/search?q=${encodeURIComponent(query)}&access_token=<?php echo $apiToken; ?>`)
                .then(response => response.json())
                .then(data => {
                    const suggestions = data.response.hits.map(hit => {
                        return `<a href="?song_id=${hit.result.id}" class="list-group-item list-group-item-action">${hit.result.title} by ${hit.result.primary_artist.name}</a>`;
                    }).join('');
                    document.getElementById('suggestions').innerHTML = suggestions;
                })
                .catch(error => console.error('Error fetching search results:', error));
        });

        document.getElementById('save-btn').addEventListener('click', function () {
            const lyrics = document.querySelector('.lyrics-container').innerText;
            const blob = new Blob([lyrics], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lyrics.txt';
            a.click();
            URL.revokeObjectURL(url);
        });

        document.getElementById('toggle-dark-mode').addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            this.innerHTML = document.body.classList.contains('dark-mode') ?
                '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    </script>
</body>
</html>

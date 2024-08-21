require('dotenv').config();
const express = require('express');
const axios = require('axios');
const cheerio = require('cheerio');
const path = require('path');

const app = express();
const port = process.env.PORT || 3000;

const apiToken = process.env.GENIUS_API_TOKEN;

if (!apiToken) {
    console.error("GENIUS_API_TOKEN environment variable is not set.");
    process.exit(1);
}

// Serve static files
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', async (req, res) => {
    const { song_id: songId, search: searchQuery } = req.query;
    let lyrics = "", albumCover = "", title = "", year = "", artist = "", album = "";
    let suggestions = [];

    try {
        if (songId) {
            const apiUrl = `https://api.genius.com/songs/${songId}`;
            const { data } = await axios.get(apiUrl, {
                headers: { Authorization: `Bearer ${apiToken}` }
            });

            const songData = data.response.song;
            const lyricsPath = songData.path;
            albumCover = songData.song_art_image_url;
            title = songData.title;
            year = songData.release_date ? new Date(songData.release_date).getFullYear() : 'Unknown';
            artist = songData.primary_artist.name;
            album = songData.album ? songData.album.name : 'Unknown';

            const lyricsPage = await axios.get(`https://genius.com${lyricsPath}`);
            const $ = cheerio.load(lyricsPage.data);
            const lyricsElement = $('div[data-lyrics-container="true"]').first();

            lyrics = sanitizeLyrics($.html(lyricsElement));
        }

        if (searchQuery) {
            const searchUrl = `https://api.genius.com/search?q=${encodeURIComponent(searchQuery)}`;
            const { data } = await axios.get(searchUrl, {
                headers: { Authorization: `Bearer ${apiToken}` }
            });

            suggestions = data.response.hits.map(hit => ({
                id: hit.result.id,
                title: hit.result.title,
                artist: hit.result.primary_artist.name,
            }));
        }

        res.send(renderPage({ songId, searchQuery, lyrics, albumCover, title, year, artist, album, suggestions }));
    } catch (error) {
        console.error(error);
        res.status(500).send('Error: ' + error.message);
    }
});

function sanitizeLyrics(html) {
    return html.replace(/<a[^>]*>(.*?)<\/a>/gi, '$1').replace(/(class|id)="[^"]*"/gi, '');
}

function renderPage({ songId, lyrics, albumCover, title, year, artist, album, suggestions }) {
    return `
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
                ${suggestions.map(s => `
                    <a href="?song_id=${s.id}" class="list-group-item list-group-item-action">
                        ${s.title}<br>
                        <small class="text-muted">${s.artist}</small>
                    </a>
                `).join('')}
            </div>
            ${songId ? `
            <small class="muted">
                Genius ID:
                <u><a href="https://genius.com/songs/${songId}" target="_blank">
                        ${songId}
                        <i class="fas fa-external-link-alt"></i>
                    </a></u>
            </small>` : ''}
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
                ${songId ? `
                <div class="card mb-3">
                    <div class="row g-0">
                        <img src="${albumCover}" class="col-md-4 img-fluid rounded-start albumCover" alt="${title}">
                        <div class="col-md-8 card-body">
                            <h5 class="card-title">${title}</h5>
                            <p class="card-text"><strong>Artist:</strong> ${artist}</p>
                            <p class="card-text"><strong>Album:</strong> ${album}</p>
                            <p class="card-text"><strong>Year:</strong> ${year}</p>
                        </div>
                    </div>
                </div>` : ''}
                <div class="lyrics-container">${lyrics}</div>
            </div>
            <footer class="mastfoot text-center mt-4">
                <small>© 2024 &#169; <a class="sonoth" href="https://sonothamin.github.io">Sonoth Amin</a></small>
            </footer>
        </div>

        <script src="/script.js"></script>
    </body>
    </html>`;
}

app.listen(port, () => {
    console.log(`LeadSheet app listening at http://localhost:${port}`);
});

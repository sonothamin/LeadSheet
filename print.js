const express = require('express');
const fetch = require('node-fetch');
const axios = require('axios');  // Added axios import
const cheerio = require('cheerio');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const GENIUS_API_TOKEN = process.env.GENIUS_API_TOKEN;

function sanitizeLyrics(html) {
    return html.replace(/<a[^>]*>(.*?)<\/a>/gi, '$1')
               .replace(/(class|id)="[^"]*"/gi, '')
               .replace(/\s+/g, ' ')
               .trim();
}

app.get('/print', async (req, res) => {
    const songId = req.query.song_id;
    if (!songId) {
        return res.status(400).send('Song ID is required');
    }

    try {
        // Fetch song details from Genius API
        const songResponse = await fetch(`https://api.genius.com/songs/${songId}`, {
            headers: {
                'Authorization': `Bearer ${GENIUS_API_TOKEN}`
            }
        });

        if (!songResponse.ok) {
            throw new Error('Failed to fetch song details');
        }

        const songData = await songResponse.json();
        const { song } = songData.response;
        const lyricsPath = song.path;
        const albumCover = song.song_art_image_url;
        const title = song.title;
        const year = song.release_date ? new Date(song.release_date).getFullYear() : 'Unknown';
        const artist = song.primary_artist.name;
        const album = song.album?.name ?? 'Unknown';

        // Fetch lyrics page
        const lyricsPage = await axios.get(`https://genius.com${lyricsPath}`);
        const $ = cheerio.load(lyricsPage.data);
        const lyricsElement = $('div[data-lyrics-container="true"]').first();
        const lyrics = sanitizeLyrics($.html(lyricsElement));

        // Send HTML response
        res.send(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Print | LeadSheet </title>
                <link href="/print.css" rel="stylesheet">
            </head>
            <body>
                <div class="card">
                    <div class="card-inner">
                        <div class="card-image">
                            <img src="${albumCover}" alt="${title}">
                        </div>
                        <div class="card-content">
                            <h2 class="card-title">${title}</h2>
                            <p class="card-artist"><strong>Artist:</strong> ${artist}</p>
                            <p class="card-album"><strong>Album:</strong> ${album}</p>
                            <p class="card-year"><strong>Year:</strong> ${year}</p>
                        </div>
                    </div>
                </div>
                <div class="lyrics-container">
                    ${lyrics}
                </div>
                <script>
                    window.print();
                </script>
            </body>
            </html>
        `);
    } catch (error) {
        res.status(500).send(`Error: ${error.message}`);
    }
});

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});

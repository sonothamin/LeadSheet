document.getElementById('print-btn').addEventListener('click', function () {
    var songId = new URLSearchParams(window.location.search).get('song_id');
    if (songId) {
        window.location.href = '/print/?song_id=' + encodeURIComponent(songId);
    } else {
        alert('No song selected for printing.');
    }
});

document.getElementById('save-btn').addEventListener('click', function () {
    const blob = new Blob([document.querySelector('.lyrics-container').innerText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '${title}.txt';
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
        } else {
            previewPane.classList.remove('hidden');
            sidebar.classList.remove('active');
        }
    }

    backButton.addEventListener('click', () => {
        previewPane.classList.remove('active');
        sidebar.classList.remove('hidden');
    });

    document.getElementById('toggle-dark-mode').addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
    });
});

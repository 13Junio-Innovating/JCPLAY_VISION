<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Vision Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: black; overflow: hidden; }
        /* Hide cursor if idle (optional, can be done with JS) */
        /* body { cursor: none; } */
        .error-msg { color: #ef4444; font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body class="h-screen w-screen flex items-center justify-center bg-black text-white">

    <!-- Status / Loading -->
    <div id="loading" class="text-center absolute z-50">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
        <h1 id="status-text" class="text-2xl font-bold">Iniciando Player...</h1>
        <p id="key-display" class="mt-2 text-gray-500 font-mono text-sm"></p>
    </div>

    <!-- Media Content -->
    <div id="media-content" class="w-full h-full absolute inset-0 hidden bg-black flex items-center justify-center">
        <!-- Media inserted here -->
    </div>

    <script>
    // Get Key from URL params
    const urlParams = new URLSearchParams(window.location.search);
    const playerKey = urlParams.get('key');

    let currentPlaylistItems = [];
    let mediaLibrary = {};
    let currentIndex = 0;
    let pollInterval = null;
    let mediaTimeout = null;

    function log(msg) { console.log(`[Player] ${msg}`); }

    if (!playerKey) {
        document.getElementById('status-text').innerText = 'Erro: Chave do player não fornecida.';
        document.getElementById('status-text').classList.add('error-msg');
        document.querySelector('.animate-spin').classList.add('hidden');
    } else {
        document.getElementById('key-display').innerText = `KEY: ${playerKey}`;
        initPlayer();
    }

    function initPlayer() {
        checkPlaylist();
        // Poll for updates every 60 seconds
        pollInterval = setInterval(checkPlaylist, 60000);
    }

    async function checkPlaylist() {
        try {
            const res = await fetch(`/api/player.php?key=${playerKey}`);
            const data = await res.json();

            if (data.error) {
                document.getElementById('status-text').innerText = data.error;
                return;
            }

            if (!data.playlist) {
                document.getElementById('status-text').innerText = 'Aguardando playlist...';
                document.getElementById('media-content').classList.add('hidden');
                document.getElementById('loading').classList.remove('hidden');
                currentPlaylistItems = [];
                if (mediaTimeout) clearTimeout(mediaTimeout);
                return;
            }

            // Store media library
            if (data.media) {
                data.media.forEach(m => {
                    mediaLibrary[m.id] = m;
                });
            }

            // Check if playlist changed
            const newItems = JSON.stringify(data.playlist.items);
            const currentItems = JSON.stringify(currentPlaylistItems);
            
            if (newItems !== currentItems) {
                log('Playlist updated');
                currentPlaylistItems = data.playlist.items;
                // Start playing if not already or if stopped
                if (document.getElementById('media-content').classList.contains('hidden') || currentPlaylistItems.length > 0) {
                    currentIndex = 0;
                    playNext();
                }
            }
        } catch (e) {
            console.error('Error checking playlist:', e);
        }
    }

    async function playNext() {
        if (currentPlaylistItems.length === 0) return;

        // Hide loading, show content
        document.getElementById('loading').classList.add('hidden');
        const container = document.getElementById('media-content');
        container.classList.remove('hidden');

        const item = currentPlaylistItems[currentIndex];
        
        // Render item
        renderMedia(item, container);

        // Schedule next
        const duration = (item.duration || 10) * 1000;
        currentIndex = (currentIndex + 1) % currentPlaylistItems.length;
        
        if (mediaTimeout) clearTimeout(mediaTimeout);
        mediaTimeout = setTimeout(playNext, duration);
    }

    function renderMedia(item, container) {
        container.innerHTML = ''; // Clear previous

        let mediaUrl = '';
        let mediaType = 'image';

        // Find media in library
        const media = mediaLibrary[item.mediaId];
        if (media) {
            mediaUrl = media.url;
            mediaType = media.type;
        }

        if (!mediaUrl) {
            container.innerHTML = '<div class="text-white text-center text-xl">Mídia não encontrada: ' + item.mediaId + '</div>';
            return;
        }

        if (mediaType === 'video') {
            const video = document.createElement('video');
            video.src = mediaUrl;
            video.autoplay = true;
            video.muted = true; // Required for autoplay
            video.playsInline = true;
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            
            // Handle video end to force next item immediately (optional, but good for sync)
            // video.onended = () => { clearTimeout(mediaTimeout); playNext(); };
            
            container.appendChild(video);
        } else {
            const img = document.createElement('img');
            img.src = mediaUrl;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            container.appendChild(img);
        }
    }
    </script>
</body>
</html>

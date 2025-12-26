<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Vision Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html { margin: 0; padding: 0; overflow: hidden; background: #000; height: 100%; width: 100%; }
        #player-container { width: 100%; height: 100%; position: relative; }
        .media-item { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            object-fit: contain; 
            opacity: 0; 
            transition: opacity 1s ease-in-out; 
            z-index: 1;
        }
        .media-item.active { opacity: 1; z-index: 2; }
        #loading { 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            color: white; font-family: sans-serif; text-align: center;
        }
        iframe { border: none; width: 100%; height: 100%; }
    </style>
</head>
<body>

<div id="loading">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
    <h2 id="status-text">Conectando...</h2>
</div>

<div id="player-container"></div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const key = urlParams.get('key');
    const container = document.getElementById('player-container');
    const statusText = document.getElementById('status-text');
    const loadingDiv = document.getElementById('loading');

    let playlist = [];
    let currentIndex = -1;
    let pollInterval = null;
    let playlistHash = '';

    if (!key) {
        statusText.innerText = "Chave de acesso não fornecida.";
    } else {
        startPlayer();
    }

    function startPlayer() {
        checkUpdates();
        setInterval(checkUpdates, 30000); // Check for playlist updates every 30s
    }

    async function checkUpdates() {
        try {
            const res = await fetch(`/api/player_content.php?key=${key}`);
            const data = await res.json();

            if (data.error) {
                statusText.innerText = data.error;
                return;
            }

            if (data.status === 'idle' || data.status === 'empty') {
                container.innerHTML = ''; // Clear player
                loadingDiv.style.display = 'block';
                statusText.innerText = data.message;
                playlist = [];
                return;
            }

            // Simple hash check to see if playlist changed
            const newHash = JSON.stringify(data.playlist);
            if (newHash !== playlistHash) {
                console.log("Playlist updated");
                playlistHash = newHash;
                playlist = data.playlist;
                
                // If not playing, start
                if (currentIndex === -1) {
                    loadingDiv.style.display = 'none';
                    playNext();
                }
            }

        } catch (e) {
            console.error("Connection error", e);
        }
    }

    function playNext() {
        if (playlist.length === 0) {
            currentIndex = -1;
            loadingDiv.style.display = 'block';
            statusText.innerText = "Aguardando conteúdo...";
            return;
        }

        currentIndex = (currentIndex + 1) % playlist.length;
        const item = playlist[currentIndex];
        
        renderMedia(item);
    }

    function renderMedia(item) {
        // Clear previous content immediately for hard cut or use double buffering for fade
        // For simplicity, let's just replace innerHTML for now, or append new and fade out old
        
        const el = document.createElement('div');
        el.className = 'media-item active'; // Start active
        
        // Remove old items after transition
        const oldItems = document.querySelectorAll('.media-item');
        oldItems.forEach(old => {
            old.classList.remove('active');
            setTimeout(() => old.remove(), 1000); // Remove after fade
        });

        if (item.type === 'image') {
            const img = document.createElement('img');
            img.src = item.url;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            el.appendChild(img);
            container.appendChild(el);
            
            setTimeout(playNext, item.duration * 1000);
            
        } else if (item.type === 'video') {
            const vid = document.createElement('video');
            vid.src = item.url;
            vid.style.width = '100%';
            vid.style.height = '100%';
            vid.autoplay = true;
            vid.muted = true; // Required for auto-play
            vid.playsInline = true;
            
            // If duration is 0 or not set, use video duration (event ended)
            // If duration is set manually by user, force it? usually video plays until end
            // Let's prefer 'ended' event unless duration is explicitly very short
            
            vid.onended = () => playNext();
            vid.onerror = () => { console.error("Video error"); playNext(); };
            
            el.appendChild(vid);
            container.appendChild(el);
            
            // Fallback timeout just in case
            // setTimeout(playNext, (item.duration + 5) * 1000); 

        } else if (item.type === 'link' || item.type.includes('youtube')) {
            // Basic YouTube handling
            let url = item.url;
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                 // Extract ID
                 let videoId = '';
                 if (url.includes('v=')) videoId = url.split('v=')[1].split('&')[0];
                 else if (url.includes('youtu.be/')) videoId = url.split('youtu.be/')[1];
                 
                 url = `https://www.youtube.com/embed/${videoId}?autoplay=1&controls=0&mute=1&loop=1&playlist=${videoId}`;
            }
            
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.allow = "autoplay; encrypted-media";
            el.appendChild(iframe);
            container.appendChild(el);
            
            setTimeout(playNext, item.duration * 1000);
        }
    }

</script>
</body>
</html>
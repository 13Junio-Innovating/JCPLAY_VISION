<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Playlists</h1>
        <p class="text-gray-600">Crie sequências de exibição</p>
    </div>
    <button onclick="openPlaylistModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition duration-300">
        <i class="fas fa-plus mr-2"></i> Nova Playlist
    </button>
</div>

<div id="playlists-list" class="space-y-4">
    <div class="text-center py-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-500">Carregando playlists...</p>
    </div>
</div>

<!-- Create/Edit Playlist Modal -->
<div id="playlist-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900 mb-4" id="modal-title">Nova Playlist</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[600px]">
            <!-- Left: Playlist Details & Items -->
            <div class="flex flex-col h-full">
                <input type="hidden" id="playlist-id">
                <input type="text" id="playlist-name" class="w-full px-3 py-2 border rounded-md mb-2" placeholder="Nome da Playlist" required>
                <textarea id="playlist-desc" class="w-full px-3 py-2 border rounded-md mb-4 text-sm" placeholder="Descrição (opcional)" rows="2"></textarea>
                
                <h4 class="font-bold text-gray-700 mb-2">Itens da Playlist</h4>
                <div id="playlist-items" class="flex-1 overflow-y-auto border rounded-md p-2 space-y-2 bg-gray-50" ondrop="drop(event)" ondragover="allowDrop(event)">
                    <p class="text-center text-gray-400 text-sm mt-10 pointer-events-none">Arraste mídias da direita para cá</p>
                </div>
            </div>

            <!-- Right: Media Library -->
            <div class="flex flex-col h-full">
                <h4 class="font-bold text-gray-700 mb-2">Biblioteca de Mídia</h4>
                <div id="media-library" class="flex-1 overflow-y-auto border rounded-md p-2 space-y-2">
                    <!-- Media items loaded here -->
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end space-x-2">
            <button onclick="closeModal('playlist-modal')" class="px-4 py-2 bg-gray-200 rounded-md">Cancelar</button>
            <button onclick="savePlaylist()" class="px-4 py-2 bg-blue-600 text-white rounded-md">Salvar</button>
        </div>
    </div>
</div>

<script>
    const userId = '<?php echo $_SESSION['user_id']; ?>';
    let currentPlaylistItems = [];
    let allMedia = [];

    function allowDrop(ev) {
        ev.preventDefault();
        const el = document.getElementById('playlist-items');
        el.classList.add('bg-blue-50', 'border-blue-300');
    }
    
    // Reset style on drag leave/drop
    function resetDropStyle() {
         const el = document.getElementById('playlist-items');
         el.classList.remove('bg-blue-50', 'border-blue-300');
    }

    function drag(ev, id) {
        ev.dataTransfer.setData("text", id);
    }

    function drop(ev) {
        ev.preventDefault();
        resetDropStyle();
        var data = ev.dataTransfer.getData("text");
        addToPlaylist(data);
    }

    // Add dragleave listener
    document.getElementById('playlist-items').addEventListener('dragleave', resetDropStyle);

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function loadPlaylists() {
        fetch(`/api/playlists.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('playlists-list');
                if (!data.data || data.data.length === 0) {
                    list.innerHTML = '<div class="text-center py-10 text-gray-500">Nenhuma playlist encontrada.</div>';
                    return;
                }

                list.innerHTML = data.data.map(pl => `
                    <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">${pl.name}</h3>
                            <p class="text-sm text-gray-500">${pl.description || 'Sem descrição'} • ${JSON.parse(pl.items).length} itens</p>
                        </div>
                        <div class="space-x-2">
                            <button onclick='editPlaylist(${JSON.stringify(pl)})' class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deletePlaylist('${pl.id}')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            });
    }

    function loadMediaLibrary() {
        fetch(`/api/media.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                allMedia = data.data || [];
                renderMediaLibrary();
            });
    }

    function renderMediaLibrary() {
        const lib = document.getElementById('media-library');
        lib.innerHTML = allMedia.map(m => `
            <div class="flex items-center justify-between p-2 bg-white border rounded shadow-sm hover:bg-gray-50 cursor-pointer" 
                 draggable="true" 
                 ondragstart="drag(event, '${m.id}')"
                 onclick="addToPlaylist('${m.id}')">
                <div class="flex items-center overflow-hidden pointer-events-none">
                    <div class="w-10 h-10 bg-gray-200 rounded mr-2 flex-shrink-0 flex items-center justify-center">
                        ${m.type === 'image' ? `<img src="${m.url}" class="w-full h-full object-cover">` : '<i class="fas fa-video"></i>'}
                    </div>
                    <div class="truncate">
                        <p class="text-sm font-medium truncate w-32">${m.name}</p>
                        <p class="text-xs text-gray-500">${m.duration}s</p>
                    </div>
                </div>
                <i class="fas fa-plus-circle text-blue-500"></i>
            </div>
        `).join('');
    }

    function addToPlaylist(mediaId) {
        const media = allMedia.find(m => m.id === mediaId);
        if (media) {
            currentPlaylistItems.push({
                mediaId: media.id,
                duration: media.duration,
                name: media.name // Cache name for display
            });
            renderPlaylistItems();
        }
    }

    function renderPlaylistItems() {
        const container = document.getElementById('playlist-items');
        if (currentPlaylistItems.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-400 text-sm mt-10">Adicione mídias da biblioteca</p>';
            return;
        }

        container.innerHTML = currentPlaylistItems.map((item, index) => `
            <div class="flex items-center justify-between p-2 bg-white border rounded shadow-sm">
                <span class="text-sm truncate w-40">${index + 1}. ${item.name || 'Mídia ' + item.mediaId}</span>
                <div class="flex items-center space-x-2">
                    <input type="number" value="${item.duration}" class="w-16 px-1 py-0.5 border rounded text-xs" onchange="updateDuration(${index}, this.value)">
                    <button onclick="removeFromPlaylist(${index})" class="text-red-500"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `).join('');
    }

    function updateDuration(index, val) {
        currentPlaylistItems[index].duration = parseInt(val);
    }

    function removeFromPlaylist(index) {
        currentPlaylistItems.splice(index, 1);
        renderPlaylistItems();
    }

    function openPlaylistModal() {
        document.getElementById('modal-title').innerText = 'Nova Playlist';
        document.getElementById('playlist-id').value = '';
        document.getElementById('playlist-name').value = '';
        document.getElementById('playlist-desc').value = '';
        currentPlaylistItems = [];
        renderPlaylistItems();
        loadMediaLibrary();
        openModal('playlist-modal');
    }

    window.editPlaylist = function(pl) {
        document.getElementById('modal-title').innerText = 'Editar Playlist';
        document.getElementById('playlist-id').value = pl.id;
        document.getElementById('playlist-name').value = pl.name;
        document.getElementById('playlist-desc').value = pl.description;
        
        // Reconstruct items with names if possible (would need join in backend, for now simple)
        const items = JSON.parse(pl.items);
        // Map names from allMedia if loaded, otherwise placeholder
        currentPlaylistItems = items.map(item => {
            const media = allMedia.find(m => m.id === item.mediaId);
            return { ...item, name: media ? media.name : 'Mídia Carregada' };
        });
        
        loadMediaLibrary(); // Ensure media loaded to map names
        renderPlaylistItems();
        openModal('playlist-modal');
    };

    window.savePlaylist = function() {
        const id = document.getElementById('playlist-id').value;
        const name = document.getElementById('playlist-name').value;
        const description = document.getElementById('playlist-desc').value;
        
        if (!name) return alert('Nome é obrigatório');

        const method = id ? 'PUT' : 'POST';
        const body = {
            id,
            name,
            description,
            items: currentPlaylistItems.map(i => ({ mediaId: i.mediaId, duration: i.duration })),
            user_id: userId
        };

        fetch('/api/playlists.php', {
            method,
            body: JSON.stringify(body),
            headers: { 'Content-Type': 'application/json' }
        }).then(() => {
            closeModal('playlist-modal');
            loadPlaylists();
        });
    };

    window.deletePlaylist = function(id) {
        if (confirm('Excluir esta playlist?')) {
            fetch(`/api/playlists.php?id=${id}`, { method: 'DELETE' })
                .then(() => loadPlaylists());
        }
    };

    loadPlaylists();
</script>

<?php require_once 'includes/footer.php'; ?>

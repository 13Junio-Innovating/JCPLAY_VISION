<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Telas</h1>
        <p class="text-gray-600">Gerencie seus dispositivos de exibição</p>
    </div>
    <button onclick="openModal('create-screen-modal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition duration-300">
        <i class="fas fa-plus mr-2"></i> Nova Tela
    </button>
</div>

<!-- Screens Grid -->
<div id="screens-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="col-span-full text-center py-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-500">Carregando telas...</p>
    </div>
</div>

<!-- Create Screen Modal -->
<div id="create-screen-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Adicionar Nova Tela</h3>
            <form id="create-screen-form">
                <div class="mt-2 py-3">
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nome da Tela (ex: Recepção)" required>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Criar
                    </button>
                    <button type="button" onclick="closeModal('create-screen-modal')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Player Key Modal -->
<div id="key-screen-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <i class="fas fa-key text-green-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Chave do Player</h3>
            <div class="mt-2 py-3">
                <p class="text-sm text-gray-500 mb-2">Use esta chave para conectar o player:</p>
                <div class="bg-gray-100 p-3 rounded border border-gray-200 font-mono text-lg select-all cursor-pointer hover:bg-gray-200 transition" id="player-key-display" title="Clique para copiar" onclick="copyKey()">
                    XXXX-XXXX
                </div>
                <p id="copy-feedback" class="text-xs text-green-600 mt-1 opacity-0 transition-opacity">Copiado!</p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeModal('key-screen-modal')" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const userId = '<?php echo $_SESSION['user_id']; ?>';
    let playlists = [];

    // Helper to open/close modals
    function openModal(id) { 
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }
    function closeModal(id) { 
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    function copyKey() {
        const key = document.getElementById('player-key-display').innerText;
        navigator.clipboard.writeText(key).then(() => {
            const feedback = document.getElementById('copy-feedback');
            feedback.classList.remove('opacity-0');
            setTimeout(() => feedback.classList.add('opacity-0'), 2000);
        });
    }

    // Load Data
    async function loadData() {
        // Load Playlists first for dropdown
        try {
            const plRes = await fetch(`/api/playlists.php?user_id=${userId}`);
            const plData = await plRes.json();
            playlists = plData.data || [];
        } catch (e) { console.error(e); }

        // Load Screens
        fetch(`/api/screens.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                const grid = document.getElementById('screens-grid');
                if (!data.data || data.data.length === 0) {
                    grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500">Nenhuma tela encontrada. Crie a primeira!</div>';
                    return;
                }

                grid.innerHTML = data.data.map(screen => {
                    const isOnline = screen.last_seen && (new Date() - new Date(screen.last_seen) < 300000); // 5 min
                    
                    const playlistOptions = playlists.map(pl => 
                        `<option value="${pl.id}" ${screen.assigned_playlist === pl.id ? 'selected' : ''}>${pl.name}</option>`
                    ).join('');

                    return `
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <h3 class="font-bold text-gray-800 truncate">${screen.name}</h3>
                                <span class="px-2 py-1 text-xs rounded-full ${isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${isOnline ? 'Online' : 'Offline'}
                                </span>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Playlist</label>
                                    <select onchange="updatePlaylist('${screen.id}', this.value)" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                                        <option value="">-- Nenhuma --</option>
                                        ${playlistOptions}
                                    </select>
                                </div>
                                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                                    <div class="flex space-x-2">
                                        <button onclick="showKey('${screen.player_key}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Ver Chave">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <a href="/player.php?key=${screen.player_key}" target="_blank" class="text-purple-600 hover:text-purple-800 text-sm font-medium" title="Abrir Player">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                    <button onclick="deleteScreen('${screen.id}')" class="text-red-500 hover:text-red-700 text-sm" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-2 text-xs text-gray-500 text-right">
                                Visto: ${screen.last_seen ? new Date(screen.last_seen).toLocaleString() : 'Nunca'}
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error(err);
                document.getElementById('screens-grid').innerHTML = '<div class="col-span-full text-center text-red-500">Erro ao carregar telas.</div>';
            });
    }

    function showKey(key) {
        document.getElementById('player-key-display').innerText = key;
        openModal('key-screen-modal');
    }

    async function updatePlaylist(screenId, playlistId) {
        try {
            const formData = new FormData();
            formData.append('id', screenId);
            formData.append('assigned_playlist', playlistId);
            formData.append('user_id', userId); // Auth check simulation

            // We need a specific endpoint or handle PUT in screens.php
            // Using a PUT request logic via POST/fetch
            const res = await fetch('/api/screens.php', {
                method: 'PUT', // Assuming PHP handles PUT input stream
                body: JSON.stringify({
                    id: screenId,
                    assigned_playlist: playlistId,
                    user_id: userId
                }),
                headers: { 'Content-Type': 'application/json' }
            });
            
            if(res.ok) {
                // Success notification?
            } else {
                alert('Erro ao atualizar playlist');
            }
        } catch(e) {
            console.error(e);
            alert('Erro de conexão');
        }
    }

    async function deleteScreen(id) {
        if(!confirm('Tem certeza que deseja excluir esta tela?')) return;

        try {
            const res = await fetch(`/api/screens.php?id=${id}&user_id=${userId}`, {
                method: 'DELETE'
            });
            if(res.ok) {
                loadData();
            } else {
                alert('Erro ao excluir tela');
            }
        } catch(e) {
            console.error(e);
            alert('Erro de conexão');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        loadData();

        document.getElementById('create-screen-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.querySelector('#create-screen-form button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = 'Criando...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.user_id = userId;

            try {
                const res = await fetch('/api/screens.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if(res.ok) {
                    closeModal('create-screen-modal');
                    e.target.reset();
                    loadData();
                } else {
                    const errorData = await res.json();
                    alert(errorData.error || 'Erro ao criar tela');
                }
            } catch(e) {
                console.error(e);
                alert('Erro de conexão');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>

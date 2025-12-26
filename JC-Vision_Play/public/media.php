<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Mídias</h1>
        <p class="text-gray-600">Gerencie seus arquivos de vídeo e imagem</p>
    </div>
    <div class="flex space-x-2">
        <button onclick="openModal('add-link-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow transition duration-300">
            <i class="fas fa-link mr-2"></i> Link
        </button>
        <button onclick="document.getElementById('file-upload').click()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition duration-300">
            <i class="fas fa-cloud-upload-alt mr-2"></i> Upload
        </button>
        <input type="file" id="file-upload" class="hidden" accept="image/*,video/*" onchange="uploadFile(this)">
    </div>
</div>

<!-- Media Grid -->
<div id="media-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <div class="col-span-full text-center py-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-500">Carregando mídias...</p>
    </div>
</div>

<!-- Add Link Modal -->
<div id="add-link-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Adicionar Link (YouTube/Vimeo)</h3>
        <form id="add-link-form">
            <div class="mt-2 space-y-3">
                <input type="text" name="name" class="w-full px-3 py-2 border rounded-md" placeholder="Nome da Mídia" required>
                <input type="url" name="url" class="w-full px-3 py-2 border rounded-md" placeholder="URL do Vídeo" required>
                <select name="type" class="w-full px-3 py-2 border rounded-md">
                    <option value="video">Vídeo</option>
                    <option value="image">Imagem (URL Externa)</option>
                </select>
                <input type="number" name="duration" class="w-full px-3 py-2 border rounded-md" placeholder="Duração (segundos)" value="10">
            </div>
            <div class="mt-4 flex space-x-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-md flex-1">Adicionar</button>
                <button type="button" onclick="closeModal('add-link-modal')" class="px-4 py-2 bg-gray-200 rounded-md flex-1">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const userId = '<?php echo $_SESSION['user_id']; ?>';

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function loadMedia() {
        fetch(`/api/media.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                const grid = document.getElementById('media-grid');
                if (!data.data || data.data.length === 0) {
                    grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500">Nenhuma mídia encontrada.</div>';
                    return;
                }

                grid.innerHTML = data.data.map(media => {
                    let preview = '';
                    if (media.type === 'image') {
                        preview = `<img src="${media.url}" class="w-full h-32 object-cover" onerror="this.src='https://placehold.co/400?text=Erro+Imagem'">`;
                    } else {
                        preview = `<div class="w-full h-32 bg-gray-900 flex items-center justify-center text-white"><i class="fas fa-video text-3xl"></i></div>`;
                    }

                    return `
                    <div class="bg-white rounded-lg shadow overflow-hidden group relative">
                        ${preview}
                        <div class="p-3">
                            <h4 class="font-bold text-gray-800 truncate" title="${media.name}">${media.name}</h4>
                            <p class="text-xs text-gray-500">${media.type} • ${media.duration}s</p>
                        </div>
                        <button onclick="deleteMedia('${media.id}')" class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition shadow hover:bg-red-600">
                            <i class="fas fa-trash text-xs w-5 h-5 flex items-center justify-center"></i>
                        </button>
                    </div>
                    `;
                }).join('');
            });
    }

    function uploadFile(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('file', input.files[0]);
            formData.append('uploaded_by', userId);
            
            // Determine type and default duration
            const file = input.files[0];
            const type = file.type.startsWith('image') ? 'image' : 'video';
            formData.append('type', type);
            formData.append('duration', type === 'image' ? 10 : 30); // Default durations

            // Show loading state
            const btn = document.querySelector('button[onclick="document.getElementById(\'file-upload\').click()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';
            btn.disabled = true;

            fetch('/api/media.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                if (res.ok) {
                    loadMedia();
                } else {
                    alert(data.error || 'Erro no upload');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ou arquivo muito grande.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                input.value = ''; // Reset input
            });
        }
    }

    document.getElementById('add-link-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]'); // Assuming first button is submit
        const originalText = btn ? btn.innerText : 'Adicionar';
        if(btn) {
             btn.innerText = 'Salvando...';
             btn.disabled = true;
        }

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.uploaded_by = userId;

        try {
            const res = await fetch('/api/media.php', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });
            
            const result = await res.json();
            
            if (res.ok) {
                closeModal('add-link-modal');
                e.target.reset();
                loadMedia();
            } else {
                alert(result.error || 'Erro ao adicionar link');
                console.error(result);
            }
        } catch (err) {
            console.error(err);
            alert('Erro de conexão');
        } finally {
            if(btn) {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    });

    function deleteMedia(id) {
        if (confirm('Excluir esta mídia?')) {
            fetch(`/api/media.php?id=${id}`, { method: 'DELETE' })
                .then(() => loadMedia());
        }
    }

    loadMedia();
</script>

<?php require_once 'includes/footer.php'; ?>

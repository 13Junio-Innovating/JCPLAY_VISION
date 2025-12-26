<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-600">Visão geral do sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card 1 -->
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-desktop text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Telas Ativas</p>
                <h2 class="text-2xl font-bold text-gray-800" id="active-screens-count">--</h2>
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-play-circle text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Playlists</p>
                <h2 class="text-2xl font-bold text-gray-800" id="playlists-count">--</h2>
            </div>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-photo-video text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Mídias</p>
                <h2 class="text-2xl font-bold text-gray-800" id="media-count">--</h2>
            </div>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Erros (Hoje)</p>
                <h2 class="text-2xl font-bold text-gray-800" id="errors-count">0</h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Status -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Status das Telas</h3>
            <a href="/screens.php" class="text-sm text-blue-500 hover:text-blue-700">Ver todas</a>
        </div>
        <div class="p-4">
            <div id="screens-list" class="space-y-4">
                <p class="text-center text-gray-500 py-4">Carregando...</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Atividade Recente</h3>
            <a href="/logs.php" class="text-sm text-blue-500 hover:text-blue-700">Ver todos</a>
        </div>
        <div class="p-4">
            <div id="activity-list" class="space-y-4">
                <p class="text-center text-gray-500 py-4">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Fetch dashboard data
    document.addEventListener('DOMContentLoaded', () => {
        const userId = '<?php echo $_SESSION['user_id']; ?>';

        // Fetch screens
        fetch(`/api/screens.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.data) {
                    document.getElementById('active-screens-count').textContent = data.count || 0;
                    
                    const screensList = document.getElementById('screens-list');
                    if (data.data.length > 0) {
                        screensList.innerHTML = data.data.slice(0, 5).map(screen => `
                            <div class="flex items-center justify-between border-b pb-2 last:border-0">
                                <div>
                                    <p class="font-medium text-gray-800">${screen.name}</p>
                                    <p class="text-xs text-gray-500">${screen.last_seen ? 'Online: ' + new Date(screen.last_seen).toLocaleString() : 'Nunca visto'}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full ${screen.last_seen && (new Date() - new Date(screen.last_seen) < 300000) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${screen.last_seen && (new Date() - new Date(screen.last_seen) < 300000) ? 'Online' : 'Offline'}
                                </span>
                            </div>
                        `).join('');
                    } else {
                        screensList.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhuma tela encontrada.</p>';
                    }
                }
            })
            .catch(err => console.error('Erro ao buscar telas:', err));

        // Fetch playlists count
        fetch(`/api/playlists.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.count !== undefined) {
                    document.getElementById('playlists-count').textContent = data.count;
                }
            })
            .catch(err => console.error('Erro ao buscar playlists:', err));

        // Fetch media count
        fetch(`/api/media.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.count !== undefined) {
                    document.getElementById('media-count').textContent = data.count;
                }
            })
            .catch(err => console.error('Erro ao buscar mídias:', err));
            
        // Fetch logs
        fetch(`/api/logs.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                const activityList = document.getElementById('activity-list');
                if (data.data && data.data.length > 0) {
                    activityList.innerHTML = data.data.slice(0, 5).map(log => `
                        <div class="flex items-start border-b pb-2 last:border-0">
                            <div class="mr-3 mt-1">
                                <i class="fas fa-info-circle text-blue-500"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">${log.action || 'Ação do sistema'}</p>
                                <p class="text-xs text-gray-500">${new Date(log.created_at).toLocaleString()}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    activityList.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhuma atividade recente.</p>';
                }
            })
            .catch(err => console.error('Erro ao buscar logs:', err));
    });
</script>

<?php require_once 'includes/footer.php'; ?>

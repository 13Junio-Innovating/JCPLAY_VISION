<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Logs do Sistema</h1>
    <p class="text-gray-600">Histórico de atividades e erros</p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Data/Hora
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Nível
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Mensagem
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Origem
                    </th>
                </tr>
            </thead>
            <tbody id="logs-table-body">
                <tr>
                    <td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Carregando logs...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const userId = '<?php echo $_SESSION['user_id']; ?>';

    fetch(`/api/logs.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('logs-table-body');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Nenhum log encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map(log => {
                let colorClass = 'text-gray-900';
                if (log.level === 'ERROR') colorClass = 'text-red-600 font-bold';
                if (log.level === 'WARNING') colorClass = 'text-orange-600';
                if (log.level === 'INFO') colorClass = 'text-blue-600';

                return `
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        ${new Date(log.created_at).toLocaleString()}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span class="${colorClass}">${log.level}</span>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        ${log.message}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        ${log.source || '-'}
                    </td>
                </tr>
                `;
            }).join('');
        });
</script>

<?php require_once 'includes/footer.php'; ?>

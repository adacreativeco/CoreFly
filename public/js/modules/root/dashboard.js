import { apiCall } from '../../api.js';
import { formatDate } from '../../utils.js';

let trafficChart = null;
let distributionChart = null;
let logStreamInterval = null;
let isStreamPaused = false;

export async function init() {
    window.toggleLogStream = toggleLogStream;
    window.clearLogViewer = clearLogViewer;
    window.toggleAIChat = toggleAIChat;

    try {
        const res = await apiCall('/root/dashboard');
        if (res && res.data) {
            renderMetrics(res.data.metrics);
            renderRecentTenants(res.data.recent_tenants);
            renderRecentUsers(res.data.recent_users);
            
            setTimeout(() => {
                initCharts();
            }, 100);

            startLogStream();
        }
    } catch (error) {
        console.error('Failed to load root dashboard:', error);
    }

    // AI Chat Handler
    const aiForm = document.getElementById('ai-chat-form');
    if (aiForm) {
        aiForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('ai-input');
            const query = input.value.trim();
            if (!query) return;

            appendAIMessage(query, 'user');
            input.value = '';

            try {
                const res = await apiCall('/root/ai/ask', 'POST', { query });
                if (res && res.data) {
                    appendAIMessage(res.data.response, 'ai');
                }
            } catch (error) {
                appendAIMessage('Sorry, I encountered an error processing your request.', 'ai');
            }
        });
    }
}

function toggleAIChat() {
    const chatWindow = document.getElementById('ai-chat-window');
    chatWindow.classList.toggle('hidden');
}

function appendAIMessage(text, sender) {
    const container = document.getElementById('ai-messages');
    const div = document.createElement('div');
    div.className = 'flex items-start gap-2 ' + (sender === 'user' ? 'flex-row-reverse' : '');
    
    const icon = sender === 'ai' 
        ? `<div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400"><i class="fas fa-robot text-sm"></i></div>`
        : `<div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300"><i class="fas fa-user text-sm"></i></div>`;

    const bubbleClass = sender === 'ai'
        ? 'bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none text-gray-700 dark:text-gray-300 border border-gray-100 dark:border-gray-700'
        : 'bg-indigo-600 text-white rounded-2xl rounded-tr-none';

    div.innerHTML = `
        ${icon}
        <div class="${bubbleClass} p-3 shadow-sm text-sm max-w-[80%]">
            ${text}
        </div>
    `;
    
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function renderMetrics(metrics) {
    document.getElementById('metric-total-tenants').textContent = metrics.total_tenants;
    document.getElementById('metric-active-tenants').textContent = metrics.active_tenants;
    document.getElementById('metric-total-users').textContent = metrics.total_users;
    document.getElementById('metric-active-users').textContent = metrics.active_users;
    document.getElementById('metric-api-requests').textContent = metrics.api_requests_today.toLocaleString();
    document.getElementById('metric-error-rate').textContent = metrics.error_rate_percent + '%';
    
    // System Health
    document.getElementById('metric-uptime').textContent = metrics.uptime || 'N/A';
    
    updateGauge('metric-cpu-bar', 'metric-cpu-text', metrics.cpu_usage_percent);
    updateGauge('metric-memory-bar', 'metric-memory-text', metrics.memory_usage.percentage, metrics.memory_usage.used + ' / ' + metrics.memory_usage.total);
    updateGauge('metric-disk-bar', 'metric-disk-text', metrics.disk_usage_percent, metrics.disk_usage_percent + '%');
}

function updateGauge(barId, textId, percent, textValue = null) {
    const bar = document.getElementById(barId);
    const text = document.getElementById(textId);
    if (bar) bar.style.width = percent + '%';
    if (text) text.textContent = textValue || (percent + '%');
    
    // Color coding based on load
    if (bar) {
        bar.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-orange-500', 'bg-blue-500', 'bg-purple-500');
        if (percent > 90) bar.classList.add('bg-red-500');
        else if (percent > 70) bar.classList.add('bg-yellow-500');
        else bar.classList.add('bg-green-500');
    }
}

function renderRecentTenants(tenants) {
    const table = document.getElementById('recent-tenants-table');
    if (!tenants.length) {
        table.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No tenants found</td></tr>';
        return;
    }

    table.innerHTML = tenants.map(t => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${t.name}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${t.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}">
                    ${t.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">${formatDate(t.created_at)}</td>
        </tr>
    `).join('');
}

function renderRecentUsers(users) {
    const table = document.getElementById('recent-users-table');
    if (!users.length) {
        table.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>';
        return;
    }

    table.innerHTML = users.map(u => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${u.username}</div>
                <div class="text-xs text-gray-500">${u.email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">${u.tenant_id.substring(0, 8)}...</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">${formatDate(u.created_at)}</td>
        </tr>
    `).join('');
}

function initCharts() {
    const ctxTraffic = document.getElementById('trafficChart');
    const ctxDistribution = document.getElementById('distributionChart');

    if (trafficChart) trafficChart.destroy();
    if (distributionChart) distributionChart.destroy();

    // Mock Data for Traffic
    if (ctxTraffic) {
        trafficChart = new Chart(ctxTraffic, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'API Requests',
                    data: [1200, 1900, 3000, 5000, 2300, 1800, 4200],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Mock Data for Distribution
    if (ctxDistribution) {
        distributionChart = new Chart(ctxDistribution, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Suspended', 'Pending'],
                datasets: [{
                    data: [85, 10, 5],
                    backgroundColor: [
                        '#10b981', // green
                        '#ef4444', // red
                        '#f59e0b'  // yellow
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    }
}

// --- Live Log Stream Logic ---

function startLogStream() {
    if (logStreamInterval) clearInterval(logStreamInterval);
    
    // Fetch immediately then poll
    fetchLogs();
    
    logStreamInterval = setInterval(() => {
        if (!isStreamPaused) fetchLogs();
    }, 2000); // Poll every 2 seconds
}

async function fetchLogs() {
    try {
        const res = await apiCall('/root/log-stream');
        if (res && res.data && res.data.logs) {
            appendLogs(res.data.logs);
        }
    } catch (e) {
        console.error('Log stream error:', e);
    }
}

function appendLogs(logs) {
    const viewer = document.getElementById('live-log-viewer');
    if (!viewer) return;

    // If first load or cleared, clear "Connecting..." message
    if (viewer.children.length === 1 && viewer.children[0].textContent.includes('Connecting')) {
        viewer.innerHTML = '';
    }

    // Avoid duplicates (simple check based on raw string)
    const existingText = viewer.innerText;

    logs.forEach(log => {
        if (existingText.includes(log.raw)) return; // Skip dupes (naive)

        const div = document.createElement('div');
        div.className = 'font-mono text-xs border-b border-gray-800 pb-1 mb-1 last:border-0';
        
        let levelColor = 'text-blue-400';
        if (log.level.includes('ERROR') || log.level.includes('CRITICAL')) levelColor = 'text-red-400';
        else if (log.level.includes('WARNING')) levelColor = 'text-yellow-400';

        div.innerHTML = `
            <span class="text-gray-500">[${log.timestamp}]</span>
            <span class="${levelColor} font-bold">${log.level}</span>
            <span class="text-gray-400">${log.source}:</span>
            <span class="text-gray-300">${log.message}</span>
        `;
        viewer.appendChild(div);
    });

    // Auto-scroll to bottom if not manually scrolled up
    // (Simple implementation: always scroll for now)
    viewer.scrollTop = viewer.scrollHeight;
}

function toggleLogStream() {
    isStreamPaused = !isStreamPaused;
    const btn = document.getElementById('log-stream-toggle');
    if (isStreamPaused) {
        btn.textContent = 'RESUME';
        btn.classList.replace('text-green-400', 'text-yellow-400');
    } else {
        btn.textContent = 'PAUSE';
        btn.classList.replace('text-yellow-400', 'text-green-400');
    }
}

function clearLogViewer() {
    const viewer = document.getElementById('live-log-viewer');
    if (viewer) viewer.innerHTML = '<div class="text-gray-500">> Logs cleared. Waiting for new events...</div>';
}

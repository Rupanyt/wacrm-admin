<div id="toast-container" class="fixed bottom-5 right-5 flex flex-col gap-3" style="z-index: 9999;"></div>

<style>
    .toast-fade-in { animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    .toast-fade-out { animation: slideOut 0.3s ease-in forwards; }

    @keyframes slideIn {
        from { transform: translateX(110%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOut {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(110%); opacity: 0; }
    }
</style>

<script>


function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');

    const config = {
        success: {
            bg: 'bg-green-50',
            text: 'text-green-900',
            border: 'border-green-600',
            icon: 'fa-check-circle',
            iconColor: 'text-green-600'
        },
        error: {
            bg: 'bg-red-50',
            text: 'text-red-900',
            border: 'border-red-600',
            icon: 'fa-exclamation-circle',
            iconColor: 'text-red-600'
        }
    };

    const style = config[type] || config.success;
    toast.className = `${style.bg} ${style.text} px-5 py-4 shadow-2xl flex items-start gap-4 toast-fade-in border-l-[6px] ${style.border}`;
    toast.style.minWidth = "320px";
    toast.style.borderRadius = "2px"; 

    toast.innerHTML = `
        <div class="mt-1">
            <i class="fas ${style.icon} ${style.iconColor} text-lg"></i>
        </div>
        <div class="flex-1">
            <p class="font-black uppercase text-[10px] tracking-widest opacity-50 mb-0.5">${type}</p>
            <p class="text-sm font-bold leading-tight">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-900 transition-colors ml-2">
            <i class="fas fa-times text-xs"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.replace('toast-fade-in', 'toast-fade-out');
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}



function closeToast(btn) {
    const toast = btn.closest('.toast-fade-in');
    clearTimeout(toast.dataset.timeout);
    removeToast(toast);
}

function removeToast(toast) {
    toast.classList.replace('toast-fade-in', 'toast-fade-out');
    setTimeout(() => toast.remove(), 400);
}
</script>
<div id="commonModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-md shadow-2xl transition-all transform scale-95 duration-300 relative" style="border-radius: 12px;">
        
        <button onclick="closeModal()" 
                class="absolute -top-3 -right-3 w-8 h-8 bg-white text-gray-400 hover:text-white hover:bg-red-500 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 z-[110] hover:rotate-180 border border-gray-100 group">
            <i class="fas fa-times text-xs"></i>
        </button>

        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
            <h3 id="modalTitle" class="text-sm font-bold text-gray-800 tracking-tight uppercase">Modal Title</h3>
        </div>
        
        <div id="modalBody" class="p-6">
            <div class="flex justify-center py-4">
                <i class="fas fa-spinner fa-spin text-<?= get_config('theme_color'); ?>-500 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(title, url) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('commonModal').classList.remove('hidden');
    document.getElementById('commonModal').querySelector('div').classList.add('scale-100');
    
    // AJAX to load form
    $.get(url, function(data) {
        $('#modalBody').html(data);
    });
}

function closeModal() {
    document.getElementById('commonModal').classList.add('hidden');
    document.getElementById('commonModal').querySelector('div').classList.remove('scale-100');
    $('#modalBody').html('<div class="flex justify-center py-4"><i class="fas fa-spinner fa-spin text-<?= get_config('theme_color'); ?>-500 text-2xl"></i></div>');
}
</script>
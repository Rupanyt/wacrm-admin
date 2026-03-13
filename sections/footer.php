<?php include 'include/message_toast.php'; ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('collapseIcon');
    const fullLogo = document.getElementById('fullLogo');
    const circleLogo = document.getElementById('circleLogo');
    const navTexts = document.querySelectorAll('.nav-text');
    
    if(sidebar.classList.contains('w-64')) {
        // Collapsing
        sidebar.classList.replace('w-64', 'w-20');
        icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
        fullLogo.classList.add('hidden');
        circleLogo.classList.remove('hidden');
        circleLogo.classList.add('flex');
        navTexts.forEach(t => t.classList.add('hidden'));
    } else {
        // Expanding
        sidebar.classList.replace('w-20', 'w-64');
        icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
        circleLogo.classList.add('hidden');
        circleLogo.classList.remove('flex');
        fullLogo.classList.remove('hidden');
        navTexts.forEach(t => t.classList.remove('hidden'));
    }
}



function openModal(title, modalFileName, data = {}) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('commonModal').classList.remove('hidden');
    
    $.ajax({
        url: 'modals/' + modalFileName + '.php',
        type: 'GET',
        data: data,
        success: function(response) {
            $('#modalBody').html(response);
        },
        error: function() {
            showToast("Modal load karne mein error aaya", "error");
        }
    });
}


</script>

</body>
</html>
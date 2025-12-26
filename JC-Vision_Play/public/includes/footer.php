        </main>
    </div>
</div>
<script>
    // Simple mobile menu toggle
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('aside');
    
    if (btn && sidebar) {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('absolute');
            sidebar.classList.toggle('z-50');
            sidebar.classList.toggle('h-full');
        });
    }
</script>
</body>
</html>

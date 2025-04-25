            </div>
        </div>
    </div>
    
    <!-- AlpineJS para interactividad -->
    <script defer src="https://unpkg.com/alpinejs@3.10.5/dist/cdn.min.js"></script>
    
    <!-- Scripts globales -->
    <script>
        // Inicialización de componentes
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar dropdowns (para el perfil de usuario)
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = userMenuButton?.nextElementSibling;
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function() {
                    const isHidden = userMenu.style.display === 'none';
                    userMenu.style.display = isHidden ? 'block' : 'none';
                });
                
                // Cerrar el menú al hacer clic fuera
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.style.display = 'none';
                    }
                });
            }
            
            // Inicializar tooltips
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', function() {
                    const text = this.getAttribute('data-tooltip');
                    const tooltipEl = document.createElement('div');
                    tooltipEl.classList.add('tooltip');
                    tooltipEl.textContent = text;
                    tooltipEl.style.position = 'absolute';
                    tooltipEl.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                    tooltipEl.style.color = 'white';
                    tooltipEl.style.padding = '5px 10px';
                    tooltipEl.style.borderRadius = '4px';
                    tooltipEl.style.fontSize = '12px';
                    tooltipEl.style.zIndex = '1000';
                    tooltipEl.style.top = `${this.offsetTop - 30}px`;
                    tooltipEl.style.left = `${this.offsetLeft + this.offsetWidth / 2}px`;
                    tooltipEl.style.transform = 'translateX(-50%)';
                    document.body.appendChild(tooltipEl);
                    
                    this.addEventListener('mouseleave', function() {
                        document.body.removeChild(tooltipEl);
                    }, { once: true });
                });
            });
        });
    </script>
</body>
</html> 
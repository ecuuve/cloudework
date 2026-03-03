// athlete-components.js - Componentes reutilizables para el portal del atleta

const AthleteComponents = {
    // Renderizar sidebar
    renderSidebar(activePage = '') {
        return `
            <aside class="sidebar">
                <div class="logo">
                    <h1>COACHING</h1>
                    <span>PORTAL DEL ATLETA</span>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="athlete-dashboard.html" class="nav-link ${activePage === 'dashboard' ? 'active' : ''}">
                                <span class="nav-icon">🏠</span> Mi Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="athlete-calendar.html" class="nav-link ${activePage === 'calendar' ? 'active' : ''}">
                                <span class="nav-icon">📅</span> Calendario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="athlete-history.html" class="nav-link ${activePage === 'history' ? 'active' : ''}">
                                <span class="nav-icon">📊</span> Mi Historial
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="athlete-mood.html" class="nav-link ${activePage === 'mood' ? 'active' : ''}">
                                <span class="nav-icon">🎭</span> Mi Estado de Ánimo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="athlete-profile.html" class="nav-link ${activePage === 'profile' ? 'active' : ''}">
                                <span class="nav-icon">👤</span> Mi Perfil
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
        `;
    },

    // Renderizar header con dropdown de usuario
    renderHeader(title = '', userName = '') {
        const user = JSON.parse(localStorage.getItem('cloudework_user') || '{}');
        const displayName = userName || user.full_name || user.first_name || 'Atleta';
        const initials = this.getInitials(displayName);
        
        return `
            <header class="header">
                <div class="header-left">
                    <h2>${title}</h2>
                    <div class="header-date" id="headerDate"></div>
                </div>
                <div class="header-right" style="display:flex;align-items:center;gap:1rem;">
                    <div class="user-menu" style="position:relative;">
                        <div class="user-menu-trigger" onclick="AthleteComponents.toggleUserMenu()" style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;padding:0.5rem 1rem;border-radius:8px;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                            <div style="text-align:right;">
                                <div id="headerName" style="font-size:0.88rem;font-weight:600;color:#e2e8f0;">${displayName}</div>
                                <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Atleta</div>
                            </div>
                            <div id="headerAvatar" style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#FF6B35,#e04a1e);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.95rem;color:#fff;">${initials}</div>
                        </div>
                        <div id="userDropdown" class="user-dropdown" style="display:none;position:absolute;top:100%;right:0;margin-top:0.5rem;background:#252936;border:1px solid rgba(255,255,255,0.1);border-radius:12px;min-width:200px;box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;z-index:1000;">
                            <a href="athlete-profile.html" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1.25rem;color:#e2e8f0;text-decoration:none;transition:all 0.2s;border-bottom:1px solid rgba(255,255,255,0.05);" onmouseover="this.style.background='rgba(255,107,53,0.08)'" onmouseout="this.style.background='transparent'">
                                <span style="font-size:1.2rem;">👤</span>
                                <span>Mi Perfil</span>
                            </a>
                            <a href="#" onclick="AthleteComponents.logout(); return false;" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1.25rem;color:#ef4444;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                                <span style="font-size:1.2rem;">🚪</span>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
        `;
    },

    // Toggle del dropdown
    toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    },

    // Cerrar dropdown al hacer click fuera
    setupDropdownClose() {
        document.addEventListener('click', (e) => {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (dropdown && !userMenu?.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    },

    // Obtener iniciales del nombre
    getInitials(name) {
        if (!name) return 'A';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    },

    // Actualizar fecha en el header
    updateHeaderDate() {
        const dateEl = document.getElementById('headerDate');
        if (dateEl) {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.textContent = now.toLocaleDateString('es-ES', options);
        }
    },

    // Logout
    logout() {
        localStorage.removeItem('authToken');
        localStorage.removeItem('cloudework_user');
        window.location.href = 'login-connected.html';
    },

    // Inicializar componentes en una página
    init(activePage = '', pageTitle = '') {
        // Insertar sidebar si no existe
        if (!document.querySelector('.sidebar')) {
            document.body.insertAdjacentHTML('afterbegin', this.renderSidebar(activePage));
        }

        // Insertar header si no existe
        if (!document.querySelector('.header')) {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.insertAdjacentHTML('afterend', this.renderHeader(pageTitle));
            } else {
                document.body.insertAdjacentHTML('afterbegin', this.renderHeader(pageTitle));
            }
        }

        // Setup
        this.updateHeaderDate();
        this.setupDropdownClose();
    }
};

// Cerrar dropdown con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
});

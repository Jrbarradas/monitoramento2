document.addEventListener('DOMContentLoaded', () => {
    // Elementos da UI
    const botaoMenu = document.getElementById('botao-menu');
    const menu = document.getElementById('menu');
    const conteudo = document.querySelector('.conteudo');
    
    // Criar overlay
    const overlay = document.createElement('div');
    overlay.className = 'menu-overlay';
    document.body.appendChild(overlay);

    // Estado do menu
    let menuAberto = false;

    // Função para alternar menu
    const toggleMenu = () => {
        menuAberto = !menuAberto;
        menu.style.left = menuAberto ? '0px' : '-250px';
        overlay.style.display = menuAberto ? 'block' : 'none';
        document.body.style.overflow = menuAberto ? 'hidden' : 'auto';
    };

    // Event Listeners
    botaoMenu.addEventListener('click', toggleMenu);
    
    overlay.addEventListener('click', () => {
        if (menuAberto) toggleMenu();
    });

    // Fechar menu ao redimensionar para desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && menuAberto) {
            toggleMenu();
        }
    });

    // Fechar menu com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menuAberto) {
            toggleMenu();
        }
    });

    // Melhoria de acessibilidade
    menu.addEventListener('keydown', (e) => {
        if (e.key === 'Tab' && menuAberto) {
            const focusableElements = menu.querySelectorAll('a, button');
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
    });
});

// Função global para fechar popups
function fecharPopup(elemento) {
    elemento.parentElement.style.display = 'none';
}

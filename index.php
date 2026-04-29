<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FluxoCars | Vehículos de Importación</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="icon" type="image/png" href="src/logos/mini-logo.png">
</head>
<body>

    <header>
        <div class="nav-container">
            <div class="logo">
                <a><img src="src/logos/Logo.png" alt="FluxoCars Logo"></a>
            </div>
            <nav>
                <ul>
                    <li><a href="#catalogo">Catálogo</a></li>
                    
                    <?php if (isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] === 'admin' || $_SESSION['user_rol'] === 'sup-admin')): ?>
                        <li>
                            <a href="admin_inventario.php">
                                Inventario
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li><a href="#contacto">Cotizaciones</a></li>
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#contacto">Contáctanos</a></li>
                </ul>
            </nav>
            <div class="user-profile">
                <div class="profile-icon" id="btnPerfil">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="dropdown-content" id="menuPerfil">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-info">
                            <p class="user-name">Hola, <?php echo $_SESSION['user_name']; ?></p>
                            <p class="user-role" style="font-size: 0.7rem; color: #3ebd60; opacity: 0.8;">
                                <?php echo ucfirst($_SESSION['user_rol']); ?>
                            </p>
                        </div>
                        <hr>    
                        <a href="perfil.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Mi Perfil
                        </a>
                        <a href="mis-cotizaciones.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Mis Cotizaciones
                        </a>
                        <a href="favoritos.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                            Mis Favoritos
                        </a>
                        <a href="historial.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            Mis Compras
                        </a>

                        <?php if($_SESSION['user_rol'] === 'sup-admin'): ?>
                            <hr>
                            <a href="gestion_admins.php" style="color: #00d2ff;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Gestión de Admins
                            </a>
                        <?php endif; ?>
                        <hr>
                        <a href="logout.php" class="logout-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            Cerrar Sesión
                        </a>
                    <?php else: ?>
                        <a href="login.php">Iniciar Sesión</a>
                        <a href="signup.php">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div> 
    </header>
    <section class="hero">
        <div class="hero-content">
            <h1>Encuentra tu próximo auto en <span class="highlight">FluxoCars</span></h1>
            <p>Importación directa de vehículos con garantía y seguridad. Cotiza el auto de tus sueños hoy mismo.</p>
            <a href="#cotizar" class="btn-primary">Solicitar cotización</a>
        </div>
    </section>

    <section class="categorias-section">
        <h2 class="section-title">¿Qué estás buscando?</h2>
        <div class="grid-categorias">
            <a href="#catalogo-suv" class="categoria-card">
                <h3>SUV & Vagonetas</h3>
                <p>Explorar modelos</p>
            </a>
            <a href="#catalogo-pickup" class="categoria-card">
                <h3>Camionetas 4x4</h3>
                <p>Explorar modelos</p>
            </a>
            <a href="#catalogo-eco" class="categoria-card">
                <h3>100% Eléctricos</h3>
                <p>Explorar modelos</p>
            </a>
        </div>
    </section>

    <section class="destacados-section">
        <h2 class="section-title">Nuevos Ingresos</h2>
        <div class="grid-autos">
            <div class="auto-card">
                <div class="img-placeholder">Foto BYD aquí</div>
                <div class="auto-info">
                    <h3>BYD Dolphin 2024</h3>
                    <p class="precio">$us 28,500</p>
                    <a href="#" class="btn-outline">Ver detalles</a>
                </div>
            </div>
            <div class="auto-card">
                <div class="img-placeholder">Foto Maxus aquí</div>
                <div class="auto-info">
                    <h3>Maxus T90 4x4</h3>
                    <p class="precio">$us 35,000</p>
                    <a href="#" class="btn-outline">Ver detalles</a>
                </div>
            </div>
            <div class="auto-card">
                <div class="img-placeholder">Foto Toyota aquí</div>
                <div class="auto-info">
                    <h3>Toyota Hilux 2024</h3>
                    <p class="precio">$us 42,000</p>
                    <a href="#" class="btn-outline">Ver detalles</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-col brand-col">
                <img src="src/logos/Logo.png" alt="FluxoCars Logo" class="footer-logo">
                <p>Importación directa de vehículos con garantía y seguridad para toda Bolivia.</p>
            </div>
            <div class="footer-col">
                <h4>Enlaces Rápidos</h4>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="#">Catálogo Completo</a></li>
                    <li><a href="#">Cotizaciones</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contacto</h4>
                <p>Av. Principal #123, La Paz</p>
                <p>info@fluxocars.com</p>
                <p>+591 70000000</p>
            </div>
            <div class="footer-col">
                <h4>Síguenos</h4>
                <div class="social-links">
                    <a href="#" class="social-btn"> WhatsApp</a>
                    <a href="#" class="social-btn"> Instagram</a>
                    <a href="#" class="social-btn"> TikTok</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 FluxoCars. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        const btnPerfil = document.getElementById('btnPerfil');
        const menuPerfil = document.getElementById('menuPerfil');

        btnPerfil.addEventListener('click', function(evento) {
            menuPerfil.classList.toggle('show');
            evento.stopPropagation();
        });

        window.addEventListener('click', function() {
            if (menuPerfil.classList.contains('show')) {
                menuPerfil.classList.remove('show');
            }
        });
    </script>
</body>
</html>
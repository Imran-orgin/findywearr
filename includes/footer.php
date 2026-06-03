<?php if (session_status() == PHP_SESSION_NONE)session_start();?>

<!-- Bootstrap JS -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <!-- Custom JS -->
 <script src="/findywearce/public/js/main.js"></script>

 <script>
    // Navbar scroll effect
    window.addEventListner('scroll', function() {
        const navbar = document.getElementById('mainNavbarr');
        if (window.scrollY > 50){
            navbar.style.background = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
        }
        else {
             navbar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                navbar.style.boxShadow = 'none';
        }
    });
 </script>
 </body>
 </html>
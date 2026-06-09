<?php include '../includes/header.php'; ?>

<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;">
    <div class="text-center">
        <div style="font-size:8rem;font-weight:900;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            line-height:1;">
            404
        </div>
        <h3 class="fw-bold mb-3">Page Not Found!</h3>
        <p class="text-muted mb-4">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="/findywearce/" class="btn btn-primary-custom px-4">
                <i class="fas fa-home me-2"></i>Go Home
            </a>
            <button onclick="history.back()"
                class="btn btn-outline-secondary px-4">
                <i class="fas fa-arrow-left me-2"></i>Go Back
            </button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
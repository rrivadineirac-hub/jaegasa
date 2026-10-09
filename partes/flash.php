<?php
/**
 * flash.php — Notificación flotante emergente al lado derecho con temporizador de 5s (Global)
 * Sistema Académico JAEGASA
 */
$msgFlash = $flash ?? $_SESSION['flash'] ?? null;
if (!empty($msgFlash)):
    $tipoRaw = $msgFlash['tipo'] ?? 'success';
    // Si es un aviso modal centralizado especial (ej. aviso de asistencia), se ignora aquí y lo maneja la vista
    if ($tipoRaw !== 'warning_centro'):
        $tipo = htmlspecialchars((string) $tipoRaw, ENT_QUOTES, 'UTF-8');
        $mensaje = htmlspecialchars((string) ($msgFlash['mensaje'] ?? ($msgFlash['msg'] ?? '')), ENT_QUOTES, 'UTF-8');
        $icon = match($tipoRaw) {
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-octagon-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            default   => 'bi-info-circle-fill'
        };
?>
        <div class="toast-flash-derecho alert alert-<?= $tipo ?> alert-dismissible fade show" id="toastFlashGlobalSystem" role="alert">
            <div class="d-flex align-items-center gap-2 me-3 flex-wrap">
                <i class="bi <?= $icon ?> fs-5"></i>
                <span class="fw-semibold"><?= $mensaje ?></span>
                <?php if (!empty($msgFlash['wa_url'])): ?>
                    <a href="<?= htmlspecialchars((string)$msgFlash['wa_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-success text-white ms-2 fw-bold" style="text-decoration:none;">
                        <i class="bi bi-whatsapp me-1"></i> Enviar credenciales por WhatsApp
                    </a>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <script>
            <?php if (!empty($msgFlash['wa_url'])): ?>
                window.open(<?= json_encode($msgFlash['wa_url']) ?>, '_blank');
            <?php endif; ?>
            setTimeout(function() {
                const toast = document.getElementById('toastFlashGlobalSystem');
                if (toast) {
                    toast.classList.remove('show');
                    setTimeout(function() { toast.remove(); }, 350);
                }
            }, 8000);
        </script>
<?php 
    endif;
endif; 
?>

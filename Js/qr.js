/**
 * qr.js — Lógica para interactuar con la vista de códigos QR
 * Sistema Académico JAEGASA
 */

document.querySelectorAll('.qr-preview').forEach(img => {
    img.addEventListener('click', function () {
        document.getElementById('modalQrImg').src = this.dataset.src;
        document.getElementById('modalQrEstudiante').textContent = this.dataset.name;
        document.getElementById('modalQrRude').textContent = this.dataset.rude;
        document.getElementById('modalQrDownload').href = this.dataset.src;
        document.getElementById('modalQrDownload').download = 'QR_' + this.dataset.rude + '.png';
        new bootstrap.Modal(document.getElementById('modalVerQr')).show();
    });
});

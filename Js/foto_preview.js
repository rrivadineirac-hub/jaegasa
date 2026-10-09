/* ============================================================
   foto_preview.js  —  Vista previa de imagen elegida en .foto-uploader
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('change', (e) => {
        if (e.target && e.target.matches('.foto-uploader input[type="file"]')) {
            const fileInput = e.target;
            const file = fileInput.files[0];
            if (file && file.type.startsWith('image/')) {
                const container = fileInput.closest('.foto-uploader');
                const img = container ? container.querySelector('img') : null;
                if (img) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        img.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    });
});

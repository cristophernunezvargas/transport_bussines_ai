/*
 * "Compartir" button of the dashboard PDFs. Fetches the PDF and hands the file
 * to the device's share sheet (WhatsApp, mail, ...) through the Web Share API.
 * Where files can't be shared (most desktop browsers) it falls back to a
 * normal download, so the button always does something useful.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('sharePdf', (url, text) => ({
        busy: false,

        async share() {
            this.busy = true;

            try {
                const response = await fetch(url, { credentials: 'same-origin' });

                if (! response.ok) {
                    throw new Error('The PDF could not be generated.');
                }

                const disposition = response.headers.get('content-disposition') || '';
                const name = (disposition.match(/filename="?([^";]+)"?/) || [])[1] || 'resumen.pdf';
                const file = new File([await response.blob()], name, { type: 'application/pdf' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], text });
                } else {
                    window.location.href = url;
                }
            } catch (error) {
                // Closing the share sheet is not an error.
                if (error.name !== 'AbortError') {
                    window.location.href = url;
                }
            } finally {
                this.busy = false;
            }
        },
    }));
});

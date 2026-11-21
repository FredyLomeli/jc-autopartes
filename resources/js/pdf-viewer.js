import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

async function renderPdfContainer(container) {
    const pdfUrl = container.dataset.pdfUrl;
    const pagesHost = container.querySelector('[data-pdf-pages]');
    const loadingEl = container.querySelector('[data-pdf-loading]');
    const errorEl = container.querySelector('[data-pdf-error]');
    const countEl = container.querySelector('[data-pdf-count]');

    if (!pdfUrl || !pagesHost) {
        return;
    }

    try {
        const loadingTask = pdfjsLib.getDocument({
            url: pdfUrl,
            withCredentials: false,
        });

        const pdf = await loadingTask.promise;

        if (countEl) {
            countEl.textContent = `${pdf.numPages} ${pdf.numPages === 1 ? 'página' : 'páginas'}`;
            countEl.classList.remove('hidden');
        }

        const containerWidth = Math.max(container.clientWidth - 24, 320);
        const renderWidth = Math.max(containerWidth, 720);
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
            const page = await pdf.getPage(pageNumber);
            const baseViewport = page.getViewport({ scale: 1 });
            const scale = (renderWidth / baseViewport.width) * pixelRatio;
            const viewport = page.getViewport({ scale });

            const pageWrapper = document.createElement('div');
            pageWrapper.className = 'bg-white rounded-base shadow-sm border border-gray-200 overflow-hidden';

            const canvas = document.createElement('canvas');
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.width = '100%';
            canvas.style.height = 'auto';
            canvas.style.display = 'block';

            const context = canvas.getContext('2d', { alpha: false });
            await page.render({
                canvasContext: context,
                viewport,
            }).promise;

            pageWrapper.appendChild(canvas);

            if (pdf.numPages > 1) {
                const footer = document.createElement('div');
                footer.className = 'px-3 py-1.5 bg-gray-50 border-t border-gray-100 text-[11px] font-semibold text-gray-500 text-right';
                footer.textContent = `Página ${pageNumber} de ${pdf.numPages}`;
                pageWrapper.appendChild(footer);
            }

            pagesHost.appendChild(pageWrapper);
        }

        if (loadingEl) {
            loadingEl.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error al renderizar PDF:', error);
        if (loadingEl) {
            loadingEl.classList.add('hidden');
        }
        if (errorEl) {
            errorEl.classList.remove('hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-pdf-viewer]').forEach((container) => {
        renderPdfContainer(container);
    });
});

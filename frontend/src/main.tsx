import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import './index.css';
import { ToastProvider } from './components/common/SimpleToast';

const MIN_LOADER_TIME = 2200; // ⏳ 2.5 segundos (puedes poner 3000)
const loaderStartTime = Date.now();

const rootElement = document.getElementById('root')!;
const root = createRoot(rootElement);

root.render(
    <StrictMode>
        <ToastProvider>
            <App />
        </ToastProvider>
    </StrictMode>
);

function hideLoader() {
    const loader = document.getElementById('initial-loader');
    if (!loader) return;

    loader.style.transition = 'opacity 0.6s ease, transform 0.6s ease, filter 0.6s ease';
    loader.style.opacity = '0';
    loader.style.transform = 'scale(1.05)';
    loader.style.filter = 'blur(8px)';

    setTimeout(() => {
        loader.remove();
    }, 600);
}


window.addEventListener('load', () => {
    const elapsed = Date.now() - loaderStartTime;
    const remaining = MIN_LOADER_TIME - elapsed;

    if (remaining > 0) {
        setTimeout(hideLoader, remaining);
    } else {
        hideLoader();
    }
});

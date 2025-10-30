// src/components/common/SimpleToast.tsx
import { createContext, useContext, useState, ReactNode, useCallback } from 'react';

type ToastType = 'success' | 'error' | 'info' | 'warning';
type Toast = { id: number; message: string; type: ToastType };

const ToastContext = createContext<{ showToast: (msg: string, type?: ToastType) => void } | null>(null);

export const ToastProvider = ({ children }: { children: ReactNode }) => {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const show = useCallback((message: string, type: ToastType = 'info') => {
        const id = Date.now();
        setToasts((t) => [...t, { id, message, type }]);
        setTimeout(() => setToasts((t) => t.filter(x => x.id !== id)), 4500);
    }, []);

    return (
        <ToastContext.Provider value={{ showToast: show }}>
            {children}
            <div className="fixed top-6 right-6 z-50 flex flex-col gap-4">
                {toasts.map((t) => (
                    <div
                        key={t.id}
                        className={`w-[380px] px-6 py-4 rounded-xl shadow-2xl text-base font-semibold break-words transition-all duration-300 transform animate-fadeIn
                            ${
                            t.type === 'error'
                                ? 'bg-red-600 text-white'
                                : t.type === 'success'
                                    ? 'bg-green-600 text-white'
                                    : t.type === 'warning'
                                        ? 'bg-yellow-400 text-black'
                                        : 'bg-gray-800 text-white'
                        }`}
                    >
                        {t.message}
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
};

export const useToast = () => {
    const ctx = useContext(ToastContext);
    if (!ctx) throw new Error('useToast must be used within ToastProvider');
    return ctx;
};

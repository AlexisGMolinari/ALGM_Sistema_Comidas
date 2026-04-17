type ConfirmModalProps = {
    open: boolean;
    title: string;
    message: React.ReactNode;
    confirmText?: string;
    cancelText?: string;
    confirmColor?: 'red' | 'green' | 'blue' | 'orange';
    onConfirm: () => void;
    onCancel: () => void;
};

const colorMap = {
    red: 'bg-red-500 hover:bg-red-600',
    green: 'bg-green-500 hover:bg-green-600',
    blue: 'bg-blue-500 hover:bg-blue-600',
    orange: 'bg-[#FF6B35] hover:bg-[#D6492C]',
};

export const ConfirmModal = ({
                                 open,
                                 title,
                                 message,
                                 confirmText = 'Confirmar',
                                 cancelText = 'Cancelar',
                                 confirmColor = 'red',
                                 onConfirm,
                                 onCancel,
                             }: ConfirmModalProps) => {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="bg-white rounded-xl shadow-lg w-full max-w-md p-6 animate-fadeIn">

                <h2 className="text-lg font-semibold text-gray-800">
                    {title}
                </h2>

                <div className="mt-2 text-sm text-gray-600">
                    {message}
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <button
                        onClick={onCancel}
                        className="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700"
                    >
                        {cancelText}
                    </button>

                    <button
                        onClick={onConfirm}
                        className={`px-4 py-2 rounded-lg text-white font-medium ${colorMap[confirmColor]}`}
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
};
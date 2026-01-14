import { useEffect, useState } from 'react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { useParams, Link } from 'react-router-dom';
import { superAdmin, auth } from '../contexts/api';
import { useToast } from '../components/common/SimpleToast';
import Modal from "../components/common/Modal.tsx";

interface Usuario {
    id: number;
    nombre: string;
    email: string;
    roles: string;
    activo: number;
}

export default function SuperAdminEmpresaUsuarios() {
    const { empresaId } = useParams();
    const [usuarios, setUsuarios] = useState<Usuario[]>([]);
    const [loading, setLoading] = useState(true);
    const [editUsuario, setEditUsuario] = useState<Usuario | null>(null);
    const { showToast } = useToast();

    const cargarUsuarios = async () => {
        try {
            const data = await superAdmin.getEmpresaUsuarios(Number(empresaId));
            setUsuarios(data);
        } catch {
            showToast('Error al cargar usuarios', 'error');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        cargarUsuarios();
    }, [empresaId]);

    const guardarUsuario = async () => {
        if (!editUsuario) return;

        try {
            await auth.updateUsuario(editUsuario.id, editUsuario);
            showToast('Usuario actualizado', 'success');
            setEditUsuario(null);
            cargarUsuarios();
        } catch {
            showToast('No se pudo actualizar el usuario', 'error');
        }
    };

    if (loading) {
        return <div className="text-white">Cargando usuarios...</div>;
    }

    return (
        <div className="space-y-6">

            {/* HEADER */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-white">
                        Usuarios de la Empresa
                    </h1>
                    <p className="mt-2 text-orange-400">
                        Gestión de accesos y estados
                    </p>
                </div>

                <Link
                    to="/superadmin/empresas"
                    className="inline-flex items-center px-4 py-2 bg-white text-gray-800 rounded-lg shadow hover:bg-gray-100 transition"
                >
                    <ArrowLeft size={18} className="mr-2" /> Volver
                </Link>
            </div>

            {/* TABLA */}
            <div className="bg-white rounded-xl shadow-md">
                {/* 👇 ESTE CONTENEDOR HABILITA SCROLL HORIZONTAL */}
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 whitespace-nowrap">
                        <thead className="bg-gray-600">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">Nombre</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">Email</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">Rol</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">Activo</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase">Acciones</th>
                        </tr>
                        </thead>

                        <tbody className="divide-y divide-gray-200">
                        {usuarios.map(u => (
                            <tr key={u.id}>
                                <td className="px-6 py-4 text-gray-800">{u.id}</td>
                                <td className="px-6 py-4 text-gray-800">{u.nombre}</td>
                                <td className="px-6 py-4 text-gray-800">{u.email}</td>
                                <td className="px-6 py-4 text-gray-800">{u.roles}</td>
                                <td className="px-6 py-4 text-gray-800">
                            <span
                                className={`px-2 py-1 rounded-full text-xs ${
                                    u.activo === 1
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                }`}
                            >
                                {u.activo === 1 ? 'Activo' : 'Bloqueado'}
                            </span>
                                </td>
                                <td className="px-6 py-4">
                                    <button
                                        onClick={() => setEditUsuario(u)}
                                        className="px-3 py-1 bg-blue-600 text-white rounded-lg flex items-center whitespace-nowrap"
                                    >
                                        <Pencil size={14} className="mr-1" />
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>


            {/* MODAL EDICIÓN */}
            {editUsuario && (
                <Modal onClose={() => setEditUsuario(null)}>
                    <Input
                        label="Nombre"
                        value={editUsuario.nombre}
                        onChange={v => setEditUsuario({ ...editUsuario, nombre: v })}
                    />
                    <Input
                        label="Email"
                        value={editUsuario.email}
                        onChange={v => setEditUsuario({ ...editUsuario, email: v })}
                    />

                    <Switch
                        label="Usuario activo"
                        checked={editUsuario.activo === 1}
                        onChange={v =>
                            setEditUsuario({ ...editUsuario, activo: v ? 1 : 0 })
                        }
                    />

                    <ModalActions
                        onConfirm={guardarUsuario}
                        onCancel={() => setEditUsuario(null)}
                    />
                </Modal>
            )}
        </div>
    );
}

const Switch = ({ label, checked, onChange }: any) => (
    <div className="flex items-center justify-between mb-4">
        <span className="text-sm font-medium text-gray-700">{label}</span>
        <button
            type="button"
            onClick={() => onChange(!checked)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition ${
                checked ? 'bg-emerald-600' : 'bg-gray-300'
            }`}
        >
            <span
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition ${
                    checked ? 'translate-x-6' : 'translate-x-1'
                }`}
            />
        </button>
    </div>
);

const ModalActions = ({ onConfirm, onCancel }: any) => (
    <div className="flex justify-end space-x-2 mt-4">
        <button
            onClick={onCancel}
            className="px-4 py-2 bg-gray-200 rounded-lg"
        >
            Cancelar
        </button>
        <button
            onClick={onConfirm}
            className="px-4 py-2 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
        >
            Guardar
        </button>
    </div>
);
const Input = ({ label, type = 'text', onChange }: any) => (
    <div className="mb-3">
        <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
        <input
            type={type}
            className="form-input w-full text-gray-700"
            onChange={e => onChange(e.target.value)}
        />
    </div>
);
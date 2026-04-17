import { useState, useEffect } from 'react';
import { Pencil, Trash2, ArrowLeft, MapPin} from 'lucide-react';
import { Link } from 'react-router-dom';
import { superAdmin } from '../contexts/api';
import { useToast } from '../components/common/SimpleToast';
import {getApiErrorMessage} from "../utils/apiErrors.ts";

interface Localidad {
    id: number;
    nombre: string;
    activo: number | null;
}

export default function SuperAdminLocalidades() {
    const [localidades, setLocalidad] = useState<Localidad[]>([]);
    const [isCreating, setIsCreating] = useState(false);
    const [loading, setLoading] = useState(true);
    const { showToast } = useToast();
    const [showEditModal, setShowEditModal] = useState(false);
    const [localidadEdit, setLocalidadEdit] = useState<any>(null);

    useEffect(() => {
        cargarLocalidad();
    }, []);

    const cargarLocalidad = async () => {
        try {
            const data = await superAdmin.getLocalidades();
            setLocalidad(data);
        } catch (error) {
            showToast(getApiErrorMessage(error,'Error al cargar Localidades'), 'error');
        } finally {
            setLoading(false);
        }
    };

    const editarLocalidad = async (localidadId: number) => {
        try {
            const data = await superAdmin.getLocalidadesById(localidadId);

            setLocalidadEdit({
                id: data.id,
                nombre: data.nombre ?? '',
                activo: data.activo ?? 1,
            });

            setShowEditModal(true);
        } catch (error) {
            showToast(getApiErrorMessage(error,'No se pudo cargar la localidad'), 'error');
        }
    };

    const guardarEdicionLocalidad = async () => {
        try {
            await superAdmin.updateLocalidad(localidadEdit.id, localidadEdit);
            showToast('Localidad actualizada', 'success');
            setShowEditModal(false);
            cargarLocalidad();
        } catch (error) {
            showToast(getApiErrorMessage(error,'Error al actualizar Localidad'), 'error');
        }
    };

    const guardarNuevaLocalidad = async () => {
        try {
            await superAdmin.createLocalidad({
                id: 0, // IMPORTANTE
                nombre: localidadEdit.nombre,
                activo: localidadEdit.activo
            });

            showToast('Localidad creada correctamente', 'success');
            setShowEditModal(false);
            setIsCreating(false);
            cargarLocalidad();

        } catch (error) {
            showToast(getApiErrorMessage(error,'Error al crear Localidad'), 'error');
        }
    };



    const eliminarLocalidad = async (id: number) => {
        if (!window.confirm('¿Seguro que deseas eliminar esta Localidad?')) return;

        try {
            await superAdmin.deleteLocalidad(id);

            setLocalidad(prev => prev.filter(e => e.id !== id));
            showToast('Localidad eliminada correctamente', 'success');

        } catch (error: any) {
            if (error.response?.status === 400) {
                showToast(error.response.data.message, 'warning');
            } else {
                showToast(getApiErrorMessage(error,'Error inesperado al eliminar la Localidad'), 'error');
            }
        }
    };

    if (loading) {
        return <div className="text-white">Cargando Localidades...</div>;
    }

    return (
        <div className="space-y-6">

            {/* HEADER */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white flex items-center">
                        <MapPin className="mr-2" /> Localidades
                    </h1>
                    <p className="mt-2 text-orange-400">
                        Listado y gestión de Localidades
                    </p>
                </div>
                <button
                    onClick={() => {
                        setLocalidadEdit({ nombre: '', activo: 1 });
                        setIsCreating(true);
                        setShowEditModal(true);
                    }}
                    className="inline-flex items-left px-4 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 transition"
                >
                    + Nueva Localidad
                </button>

                <Link
                    to="/superadmin"
                    className="inline-flex items-center px-4 py-2 bg-white text-gray-800 rounded-lg shadow hover:bg-gray-100 transition"
                >
                    <ArrowLeft size={18} className="mr-2" /> Volver
                </Link>
            </div>

            {/* TABLA EMPRESAS */}
            <div className="bg-white rounded-xl shadow-md overflow-hidden">
                <div className="p-5 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Localidades Registradas
                    </h2>
                </div>

                <div className="grid grid-cols-1 gap-4 md:hidden">
                    {localidades.map((e) => (
                        <div key={e.id} className="bg-white rounded-xl shadow-md p-4 space-y-3">

                            {/* HEADER */}
                            <div className="flex justify-between items-center">
                                <div>
                                    <p className="text-sm text-gray-500">ID #{e.id}</p>
                                    <h3 className="font-semibold text-gray-800">{e.nombre}</h3>
                                </div>

                                <span
                                    className={`px-2 py-0.5 rounded text-xs font-medium ${
                                        e.activo === 1
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-200 text-gray-700'
                                    }`}
                                >
                    {e.activo === 1 ? 'Activa' : 'Inactiva'}
                </span>
                            </div>

                            {/* ACCIONES */}
                            <div className="grid grid-cols-2 gap-2 pt-2">
                                <button
                                    onClick={() => editarLocalidad(e.id)}
                                    className="bg-blue-600 text-white py-2 rounded-lg text-sm flex items-center justify-center"
                                >
                                    <Pencil size={14} className="mr-1" />
                                    Editar
                                </button>

                                <button
                                    onClick={() => eliminarLocalidad(e.id)}
                                    className="bg-red-600 text-white py-2 rounded-lg text-sm flex items-center justify-center"
                                >
                                    <Trash2 size={14} className="mr-1" />
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
            {showEditModal && localidadEdit && (
                <Modal title={isCreating ? "Nueva Localidad" : "Editar Localidad"}
                       onClose={() => setShowEditModal(false)}>

                    <Input
                        label="Nombre"
                        value={localidadEdit.nombre}
                        onChange={(v: string) =>
                            setLocalidadEdit({ ...localidadEdit, nombre: v })
                        }
                    />

                    <Switch
                        label="Empresa activa"
                        checked={localidadEdit.activo === 1}
                        onChange={(v: boolean) =>
                            setLocalidadEdit({ ...localidadEdit, activo: v ? 1 : 0 })
                        }
                    />

                    <ModalActions
                        onConfirm={isCreating ? guardarNuevaLocalidad : guardarEdicionLocalidad}
                        onCancel={() => {
                            setShowEditModal(false);
                            setIsCreating(false);
                        }}
                    />

                </Modal>
            )}
        </div>
    );

}
const Modal = ({ title, children }: any) => (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
        <div className="bg-white rounded-lg w-full max-w-lg p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">{title}</h3>
            {children}
        </div>
    </div>
);

const Input = ({ label, type = 'text', value, onChange }: any) => (
    <div className="mb-3">
        <label className="block text-sm font-medium text-gray-700 mb-1">
            {label}
        </label>
        <input
            type={type}
            value={value ?? ''}
            className="form-input w-full text-gray-700"
            onChange={e => onChange(e.target.value)}
        />
    </div>
);


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

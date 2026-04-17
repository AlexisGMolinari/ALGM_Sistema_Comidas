import { useEffect, useState } from 'react';
import { Building2, Users, Pencil, Trash2, ArrowLeft } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { superAdmin } from '../contexts/api';
import { useToast } from '../components/common/SimpleToast';
import {getApiErrorMessage} from "../utils/apiErrors.ts";
import {ConfirmModal} from "../components/common/ConfirmModal.tsx";

interface Empresa {
    id: number;
    nombre: string;
    url_sitioweb: string | null;
    nombre_usuario: string | null;
    activa: number | null;
}

export default function SuperAdminEmpresas() {
    const [empresas, setEmpresas] = useState<Empresa[]>([]);
    const [loading, setLoading] = useState(true);
    const { showToast } = useToast();
    const navigate = useNavigate();
    const [showEditModal, setShowEditModal] = useState(false);
    const [empresaEdit, setEmpresaEdit] = useState<any>(null);
    const [localidades, setLocalidades] = useState<any[]>([]);
    const [empresaToDelete, setEmpresaToDelete] = useState<number | null>(null);

    const cargarEmpresas = async () => {
        try {
            const data = await superAdmin.getEmpresas();
            setEmpresas(data);
        } catch (error) {
            showToast(getApiErrorMessage(error,'Error al cargar empresas'), 'error');
        } finally {
            setLoading(false);
        }
    };

    // Empresas
    useEffect(() => {
        cargarEmpresas();
    }, []);

    // Localidades
    useEffect(() => {
        superAdmin.getLocalidades().then(setLocalidades);
    }, []);

    const editarEmpresa = async (empresaId: number) => {
        try {
            const data = await superAdmin.getEmpresaById(empresaId);

            setEmpresaEdit({
                id: data.id,
                nombre: data.nombre ?? '',
                cuit: data.cuit ?? '',
                direccion: data.direccion ?? '',
                telefono: data.telefono ?? '',
                email: data.email ?? '',
                cbu_alias: data.cbu_alias ?? '',
                url_sitioweb: data.url_sitioweb ?? '',
                activa: data.activa ?? 1,
                localidad_id: data.localidad_id,
            });

            setShowEditModal(true);
        } catch (error) {
            showToast(getApiErrorMessage(error,'No se pudo cargar la empresa'), 'error');
        }
    };


    // Edicion de la empresa
    const guardarEdicionEmpresa = async () => {
        try {
            await superAdmin.updateEmpresa(empresaEdit.id, empresaEdit);
            showToast('Empresa actualizada', 'success');
            setShowEditModal(false);
            cargarEmpresas();
        } catch (error) {
            showToast(getApiErrorMessage(error,'Error al actualizar empresa'), 'error');
        }
    };

    // Elimina la empresa si no tiene usuario asignado
    const eliminarEmpresa = (id: number) => {
        setEmpresaToDelete(id);
    };

    const confirmEliminarEmpresa = async () => {
        if (!empresaToDelete) return;

        try {
            await superAdmin.deleteEmpresa(empresaToDelete);

            setEmpresas(prev => prev.filter(e => e.id !== empresaToDelete));
            showToast('Empresa eliminada correctamente', 'success');

        } catch (error: any) {
            if (error.response?.status === 400) {
                showToast(error.response.data.message, 'warning');
            } else {
                showToast(getApiErrorMessage(error,'Error inesperado al eliminar la empresa'), 'error');
            }
        } finally {
            setEmpresaToDelete(null);
        }
    };

    if (loading) {
        return <div className="text-white">Cargando empresas...</div>;
    }

    return (
        <div className="space-y-6">

            {/* HEADER */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white flex items-center">
                        <Building2 className="mr-2" /> Empresas
                    </h1>
                    <p className="mt-2 text-orange-400">
                        Listado y gestión de empresas
                    </p>
                </div>

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
                        Empresas registradas
                    </h2>
                </div>

                <div className="overflow-x-auto">

                    {/* 📱 MOBILE */}
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {empresas.map((e) => (
                            <div key={e.id} className="bg-white rounded-xl shadow-md p-4 space-y-3">

                                {/* HEADER */}
                                <div className="flex justify-between items-center">
                                    <div>
                                        <p className="text-sm text-gray-500">ID #{e.id}</p>
                                        <h3 className="font-semibold text-gray-800">{e.nombre}</h3>
                                    </div>

                                    <span
                                        className={`px-2 py-0.5 rounded text-xs font-medium ${
                                            e.activa === 1
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-200 text-gray-700'
                                        }`}
                                    >
            {e.activa === 1 ? 'Activa' : 'Inactiva'}
          </span>
                                </div>

                                {/* INFO */}
                                <div className="text-sm text-gray-700 space-y-1">
                                    <p>
                                        <strong>Web:</strong>{' '}
                                        {e.url_sitioweb ? (
                                            <a
                                                href={e.url_sitioweb}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 underline break-all"
                                            >
                                                {e.url_sitioweb}
                                            </a>
                                        ) : (
                                            '-'
                                        )}
                                    </p>

                                    <p>
                                        <strong>Admin:</strong> {e.nombre_usuario || 'Sin asignar'}
                                    </p>
                                </div>

                                {/* ACCIONES */}
                                <div className="grid grid-cols-3 gap-2 pt-2">
                                    <button
                                        onClick={() => navigate(`/superadmin/empresas/${e.id}/usuarios`)}
                                        className="bg-cyan-600 text-white py-2 rounded-lg text-sm flex items-center justify-center"
                                    >
                                        <Users size={14} className="mr-1" />
                                        Ver
                                    </button>

                                    <button
                                        onClick={() => editarEmpresa(e.id)}
                                        className="bg-blue-600 text-white py-2 rounded-lg text-sm flex items-center justify-center"
                                    >
                                        <Pencil size={14} className="mr-1" />
                                        Editar
                                    </button>

                                    <button
                                        onClick={() => eliminarEmpresa(e.id)}
                                        className="bg-red-600 text-white py-2 rounded-lg text-sm flex items-center justify-center"
                                    >
                                        <Trash2 size={14} className="mr-1" />
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* 💻 DESKTOP */}
                    {empresas.length > 0 ? (
                        <table className="hidden md:table min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sitio Web</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario Admin</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                            </thead>

                            <tbody className="bg-white divide-y divide-gray-200">
                            {empresas.map(e => (
                                <tr key={e.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 text-sm text-gray-800">{e.id}</td>
                                    <td className="px-6 py-4 text-sm text-gray-800">{e.nombre}</td>

                                    <td className="px-6 py-4 text-sm text-gray-700">
                                        {e.url_sitioweb ? (
                                            <a
                                                href={e.url_sitioweb}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 underline"
                                            >
                                                {e.url_sitioweb}
                                            </a>
                                        ) : (
                                            '-'
                                        )}
                                    </td>

                                    <td className="px-6 py-4 text-sm text-gray-700">
                                        {e.nombre_usuario || 'Sin asignar'}
                                    </td>

                                    <td className="px-6 py-4 text-sm">
              <span className={`inline-flex px-2 py-1 rounded-full text-xs font-medium ${
                  e.activa === 1
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-200 text-gray-700'
              }`}>
                {e.activa === 1 ? 'Activa' : 'Inactiva'}
              </span>
                                    </td>

                                    <td className="px-6 py-4 text-sm flex gap-2">
                                        <button
                                            onClick={() => navigate(`/superadmin/empresas/${e.id}/usuarios`)}
                                            className="px-3 py-1 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 flex items-center"
                                        >
                                            <Users size={14} className="mr-1" /> Ver
                                        </button>

                                        <button
                                            onClick={() => editarEmpresa(e.id)}
                                            className="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"
                                        >
                                            <Pencil size={14} className="mr-1" /> Editar
                                        </button>

                                        <button
                                            onClick={() => eliminarEmpresa(e.id)}
                                            className="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center"
                                        >
                                            <Trash2 size={14} className="mr-1" /> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="p-6 text-center text-gray-500">
                            No hay empresas registradas
                        </div>
                    )}

                </div>
            </div>
            {showEditModal && empresaEdit && (
                <Modal title="Editar Empresa" onClose={() => setShowEditModal(false)}>

                    <Input
                        label="Nombre"
                        value={empresaEdit.nombre}
                        onChange={(v: string) =>
                            setEmpresaEdit({ ...empresaEdit, nombre: v })
                        }
                    />

                    <Input
                        label="Dirección"
                        value={empresaEdit.direccion}
                        onChange={(v: string) =>
                            setEmpresaEdit({ ...empresaEdit, direccion: v })
                        }
                    />

                    <Input
                        label="Teléfono"
                        value={empresaEdit.telefono}
                        onChange={(v: string) =>
                            setEmpresaEdit({ ...empresaEdit, telefono: v })
                        }
                    />

                    <Input
                        label="Sitio Web"
                        value={empresaEdit.url_sitioweb}
                        onChange={(v: string) =>
                            setEmpresaEdit({ ...empresaEdit, url_sitioweb: v })
                        }
                    />

                    <Select
                        label="Localidad"
                        options={localidades}
                        value={empresaEdit.localidad_id}
                        onChange={(v: string) =>
                            setEmpresaEdit({ ...empresaEdit, localidad_id: Number(v) })
                        }
                    />

                    <Switch
                        label="Empresa activa"
                        checked={empresaEdit.activa === 1}
                        onChange={(v: boolean) =>
                            setEmpresaEdit({ ...empresaEdit, activa: v ? 1 : 0 })
                        }
                    />

                    <ModalActions
                        onConfirm={guardarEdicionEmpresa}
                        onCancel={() => setShowEditModal(false)}
                    />
                </Modal>
            )}
            <ConfirmModal
                open={empresaToDelete !== null}
                title="Eliminar empresa"
                message={
                    <>
                        ¿Seguro que deseas eliminar esta empresa?
                        <br />
                        <span className="text-red-500 font-medium">
                Esta acción no se puede deshacer.
            </span>
                    </>
                }
                confirmText="Sí, eliminar"
                confirmColor="red"
                onConfirm={confirmEliminarEmpresa}
                onCancel={() => setEmpresaToDelete(null)}
            />
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

const Select = ({ label, options, value, onChange }: any) => (
    <div className="mb-3">
        <label className="block text-sm font-medium text-gray-700 mb-1">
            {label}
        </label>
        <select
            value={value ?? ''}
            className="form-input w-full text-gray-700"
            onChange={e => onChange(e.target.value)}
        >
            <option value="">Seleccione</option>
            {options.map((o: any) => (
                <option key={o.id} value={o.id}>
                    {o.nombre}
                </option>
            ))}
        </select>
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

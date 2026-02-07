import { useEffect, useState } from 'react';
import {Plus, Building2, Users, Link2, MapPin} from 'lucide-react';
import { superAdmin } from "../contexts/api.ts";
import {useToast} from "../components/common/SimpleToast.tsx";
import { Link } from 'react-router-dom';



export default function SuperAdmin() {
    const [localidades, setLocalidades] = useState<any[]>([]);
    const [empresaId, setEmpresaId] = useState<number | null>(null);
    const [usuarioId, setUsuarioId] = useState<number | null>(null);
    const [empresas, setEmpresas] = useState<any[]>([]);
    const [usuariosSinEmpresa, setUsuariosSinEmpresa] = useState<any[]>([]);


    const { showToast } = useToast();

    const [showEmpresaModal, setShowEmpresaModal] = useState(false);
    const [showUsuarioModal, setShowUsuarioModal] = useState(false);

    const cargarEmpresas = async () => {
        const data = await superAdmin.getEmpresasSinUsuario();
        setEmpresas(data);
    };

    const cargarUsuariosSinEmpresa = async () => {
        const data = await superAdmin.getUsuariosSinEmpresa();
        setUsuariosSinEmpresa(data);
    };


    // Empresa
    const [empresa, setEmpresa] = useState({
        nombre: '',
        direccion: '',
        cuit: '',
        url_sitioweb: '',
        nombre_usuario: '',
        telefono: '',
        localidad_id: '',
    });

    // Usuario
    const [usuario, setUsuario] = useState({
        nombre: '',
        email: '',
        password: '',
        activo: 1,
        roles: 'ROLE_ADMIN',
    });

    // users admin sin empresa
    useEffect(() => {
        superAdmin.getUsuariosSinEmpresa().then(setUsuariosSinEmpresa);
    }, []);

    // Empresas
    useEffect(() => {
        cargarEmpresas();
    }, []);

    // Localidades
    useEffect(() => {
        superAdmin.getLocalidades().then(setLocalidades);
    }, []);

    // Crear empresa
    const crearEmpresa = async () => {
        const resp = await superAdmin.createEmpresa({
            id: 0,
            nombre: empresa.nombre,
            direccion: empresa.direccion,
            cuit: empresa.cuit,
            telefono: Number(empresa.telefono),
            url_sitioweb: empresa.url_sitioweb,
           // nombre_usuario: empresa.nombre_usuario,
            activa: 1,
            localidad_id: Number(empresa.localidad_id),
        });

        setEmpresaId(resp.id);
        setShowEmpresaModal(false);
        await Promise.all([
            cargarEmpresas()
        ]);
        showToast('Empresa creada', 'success' );
    };

    // Crear usuario
    const crearUsuario = async () => {
        const payload = {
            usuario: {
                id: 0,
                nombre: usuario.nombre,
                email: usuario.email,
                password: usuario.password,
                roles: 'ROLE_ADMIN',
                activo: usuario.activo,
            },
            accesos: [], // preparado para futuro
        };

        const resp = await superAdmin.createUsuario(payload);

        setUsuarioId(resp.usuario?.id ?? resp.id);
        setShowUsuarioModal(false);
        await Promise.all([
            cargarEmpresas(),
            cargarUsuariosSinEmpresa()
        ]);
        showToast('Usuario creado', 'success');
    };


    // Asignar empresa
    const asignarEmpresa = async () => {
        if (!empresaId  || !usuarioId) return;

        await superAdmin.asignarEmpresa(usuarioId, empresaId );

        showToast('Empresa asignada al usuario', 'success');

        await Promise.all([
            cargarEmpresas(),
            cargarUsuariosSinEmpresa()
        ]);

        setEmpresas(prev => prev.filter(e => e.id !== empresaId ));
        setUsuariosSinEmpresa(prev => prev.filter(u => u.id !== usuarioId));

        setUsuarioId(null);
    };



    return (
        <div className="space-y-6">

            {/* HEADER */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white">Super Administrador</h1>
                    <p className="mt-2 text-orange-400">
                        Alta de empresas y usuarios
                    </p>
                </div>

                <Link
                    to="/superadmin/empresas"
                    className="inline-flex items-center px-4 py-2 bg-white text-gray-800 rounded-lg shadow hover:bg-gray-100 transition"
                >
                    <Building2 size={18} className="mr-2" />
                    Ver listado de empresas
                </Link>
                <Link
                    to="/superadmin/localidades"
                    className="inline-flex items-center px-4 py-2 bg-white text-gray-800 rounded-lg shadow hover:bg-gray-100 transition"
                >
                    <MapPin size={18} className="mr-2" />
                    Ver listado de Localidades
                </Link>
            </div>


            {/* CARDS */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                {/* EMPRESA */}
                <div className="bg-white rounded-xl shadow-md p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-semibold text-gray-800 flex items-center">
                            <Building2 size={18} className="mr-2" /> Empresa
                        </h2>
                        <button
                            onClick={() => setShowEmpresaModal(true)}
                            className="flex items-center px-3 py-2 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
                        >
                            <Plus size={16} className="mr-1" /> Nueva
                        </button>
                    </div>

                    <p className="text-sm text-gray-600">
                        {empresaId
                            ? `Empresa seleccionada ID => ${empresaId}`
                            : 'No hay empresa seleccionada'}
                    </p>
                </div>

                {/* USUARIO */}
                <div className="bg-white rounded-xl shadow-md p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-semibold text-gray-800 flex items-center">
                            <Users size={18} className="mr-2" /> Usuario
                        </h2>
                        <button
                            onClick={() => setShowUsuarioModal(true)}
                            className="flex items-center px-3 py-2 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
                        >

                        <Plus size={16} className="mr-1" /> Nuevo
                        </button>
                    </div>

                    <p className="text-sm text-gray-600">
                        {usuarioId
                            ? `Usuario seleccionado ID => ${usuarioId}`
                            : 'No hay usuario seleccionado'}
                    </p>

                </div>

                {/* ASIGNACIÓN */}
                <div className="bg-white rounded-xl shadow-md p-6">
                    <div className="flex items-center mb-4">
                        <h2 className="text-lg font-semibold text-gray-800 flex items-center">
                            <Link2 size={18} className="mr-2" /> Asignación
                        </h2>
                    </div>

                    <button
                        disabled={!empresaId || !usuarioId}
                        onClick={asignarEmpresa}
                        className="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                    >
                        Asignar Empresa al Usuario
                    </button>
                </div>

            </div>


            {/* TABLA EMPRESAS */}
            <div className="bg-white rounded-xl shadow-md overflow-hidden">
                <div className="p-5 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Empresas sin Usuario Asignado
                    </h2>
                </div>

                <div className="overflow-x-auto">
                    {empresas.length > 0 ? (
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL Sitio</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                            </tr>
                            </thead>

                            <tbody className="bg-white divide-y divide-gray-200">
                            {empresas.map((e) => (
                                <tr
                                    key={e.id}
                                    className={`hover:bg-gray-50 ${
                                        empresaId === e.id ? 'bg-orange-50' : ''
                                    }`}
                                >
                                    <td className="px-6 py-4 text-sm text-gray-900">{e.id}</td>
                                    <td className="px-6 py-4 text-sm text-gray-700">{e.nombre}</td>
                                    <td className="px-6 py-4 text-sm text-gray-700">{e.url_sitioweb}</td>
                                    <td className="px-6 py-4 text-sm text-gray-700">{e.nombre_usuario}</td>
                                    <td className="px-6 py-4 text-sm">
                                        <button
                                            onClick={() => setEmpresaId(e.id)}
                                            className="px-3 py-1 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
                                        >
                                            Seleccionar
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="p-6 text-center text-gray-500">
                            No hay empresas pendientes de asignación
                        </div>
                    )}
                </div>
            </div>

            {/* TABLA USUARIOS SIN EMPRESA */}
            <div className="bg-white rounded-xl shadow-md overflow-hidden mt-6">
                <div className="p-5 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Usuarios sin Empresa Asignada
                    </h2>
                </div>

                <div className="overflow-x-auto">
                    {usuariosSinEmpresa.length > 0 ? (
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Nombre</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Rol</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Activo</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Acción</th>
                            </tr>
                            </thead>

                            <tbody className="bg-white divide-y divide-gray-200">
                            {usuariosSinEmpresa.map((u) => (
                                <tr
                                    key={u.id}
                                    className={`hover:bg-gray-50 ${
                                        usuarioId === u.id ? 'bg-orange-50' : ''
                                    }`}
                                >
                                    <td className="px-6 py-4 text-sm text-gray-800">{u.id}</td>
                                    <td className="px-6 py-4 text-sm text-gray-800">{u.nombre}</td>
                                    <td className="px-6 py-4 text-sm text-gray-800">{u.email}</td>
                                    <td className="px-6 py-4 text-sm text-gray-800">{u.roles}</td>
                                    <td className="px-6 py-4 text-sm text-gray-800">
                                        {u.activo === 1 ? 'Sí' : 'No'}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        <button
                                            onClick={() => setUsuarioId(u.id)}
                                            className="px-3 py-1 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
                                        >
                                            Seleccionar
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="p-6 text-center text-gray-500">
                            No hay usuarios pendientes de asignación
                        </div>
                    )}
                </div>
            </div>


            {/* MODAL EMPRESA */}
            {showEmpresaModal && (
                <Modal title="Nueva Empresa" onClose={() => setShowEmpresaModal(false)}>
                    <Input label="Nombre" onChange={v => setEmpresa({ ...empresa, nombre: v })} />
                    <Input label="Dirección" onChange={v => setEmpresa({ ...empresa, direccion: v })} />
                    <Input label="Teléfono" onChange={v => setEmpresa({ ...empresa, telefono: v })} />
                    <Input label="Sitio Web Link" onChange={v => setEmpresa({ ...empresa, url_sitioweb: v })} />

                    <Select
                        label="Localidad"
                        options={localidades}
                        onChange={v => setEmpresa({ ...empresa, localidad_id: v })}
                    />
                    <ModalActions onConfirm={crearEmpresa} onCancel={() => setShowEmpresaModal(false)} />
                </Modal>
            )}

            {/* MODAL USUARIO */}
            {showUsuarioModal && (
                <Modal title="Nuevo Usuario" onClose={() => setShowUsuarioModal(false)}>
                    <Input
                        label="Nombre"
                        onChange={v => setUsuario({ ...usuario, nombre: v })}
                    />
                    <Input
                        label="Email"
                        onChange={v => setUsuario({ ...usuario, email: v })}
                    />
                    <Input
                        label="Password"
                        type="password"
                        onChange={v => setUsuario({ ...usuario, password: v })}
                    />

                    <Switch
                        label="Usuario Activo"
                        checked={usuario.activo === 1}
                        onChange={(v: boolean) =>
                            setUsuario({ ...usuario, activo: v ? 1 : 0 })
                        }
                    />

                    <ReadOnlyField
                        label="Rol asignado"
                        value="ROLE_ADMIN"
                    />

                    <ModalActions
                        onConfirm={crearUsuario}
                        onCancel={() => setShowUsuarioModal(false)}
                    />
                </Modal>
            )}
        </div>
    );
}

const Modal = ({ title, children, onClose }: any) => (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
        <div className="bg-white rounded-lg w-full max-w-lg p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">{title}</h3>
            {children}
        </div>
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

const ReadOnlyField = ({ label, value }: any) => (
    <div className="mb-3">
        <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
        <input
            value={value}
            disabled
            className="form-input w-full bg-gray-100 text-gray-600 cursor-not-allowed"
        />
    </div>
);


const Select = ({ label, options, onChange }: any) => (
    <div className="mb-3">
        <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
        <select
            className="form-input w-full text-gray-700"
            onChange={e => onChange(e.target.value)}
        >
            <option value="">Seleccione</option>
            {options.map((o: any) => (
                <option key={o.id} value={o.id}>{o.nombre}</option>
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


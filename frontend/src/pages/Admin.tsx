// src/pages/Admin.tsx
import React, { useState, useEffect } from 'react';
import { Search, X, Check, Pencil } from 'lucide-react';
import { useToast } from '../components/common/SimpleToast';
import { auth } from "../contexts/api.ts";

// Modal simple inline (puedes luego reemplazar por tu componente real)
const Modal: React.FC<{ onClose: () => void; children: React.ReactNode }> = ({ onClose, children }) => (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6 w-full max-w-md relative">
            <button onClick={onClose} className="absolute top-2 right-2 text-gray-500 hover:text-gray-700">X</button>
            {children}
        </div>
    </div>
);

export interface User {
    id: number;
    nombre: string;
    email: string;
    roles: string;
    activo: number; // 1 = activo, 0 = inactivo
    status: 'active' | 'inactive';
    password?: string;
}

type SortKey = 'id' | 'nombre' | 'email' | 'roles';
interface SortConfig {
    key: SortKey;
    direction: 'asc' | 'desc';
}

const AdminPage: React.FC = () => {
    const { showToast } = useToast();
    const [editingUser, setEditingUser] = useState<User | null>(null);
    const [newUserPassword, setNewUserPassword] = useState('');
    const [users, setUsers] = useState<User[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterStatus, setFilterStatus] = useState<'all' | 'active' | 'inactive'>('all');
    const [sortConfig, setSortConfig] = useState<SortConfig | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    // Modal
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [newUserName, setNewUserName] = useState('');
    const [newUserEmail, setNewUserEmail] = useState('');
    const [newUserRole, setNewUserRole] = useState('ROLE_USER');

    const [newUserActivo, setNewUserActivo] = useState(1); // 1 = activo

    // ----------- Fetch Usuarios -------------
    const getUsuarios = async () => {
        try {
            const res = await auth.getUsuarios();
            const mapped: User[] = res.map((u: any) => ({
                ...u,
                status: u.activo === 1 ? 'active' : 'inactive',
            }));
            setUsers(mapped);
        } catch (error) {
            showToast('No se pudieron cargar los usuarios', 'error');
        }
    };

    useEffect(() => {
        getUsuarios();
    }, []);

    const openEditModal = (user: User) => {
        setEditingUser(user);
        setNewUserName(user.nombre);
        setNewUserEmail(user.email);
        setNewUserRole(user.roles);
        setNewUserActivo(user.activo);
        setNewUserPassword(''); // vacía al editar
        setIsModalOpen(true);
    };

    // ----------- Sorting -------------------
    const handleSort = (key: SortKey) => {
        if (sortConfig?.key === key) {
            setSortConfig({ key, direction: sortConfig.direction === 'asc' ? 'desc' : 'asc' });
        } else {
            setSortConfig({ key, direction: 'asc' });
        }
    };

    // ----------- Filtering -----------------
    const filteredUsers = React.useMemo(() => {
        return users.filter(user => {
            const matchesStatus = filterStatus === 'all' || user.status === filterStatus;
            const matchesSearch =
                (user.nombre?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    user.email?.toLowerCase().includes(searchTerm.toLowerCase()));
            return matchesStatus && matchesSearch;
        });
    }, [users, filterStatus, searchTerm]);

    // ----------- Sorted Users --------------
    const sortedUsers = React.useMemo(() => {
        if (!sortConfig) return filteredUsers;
        return [...filteredUsers].sort((a, b) => {
            const aValue = (a[sortConfig.key] ?? '').toString().toLowerCase();
            const bValue = (b[sortConfig.key] ?? '').toString().toLowerCase();
            if (aValue < bValue) return sortConfig.direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortConfig.direction === 'asc' ? 1 : -1;
            return 0;
        });
    }, [filteredUsers, sortConfig]);

    // ---------- Guardar usuario (crear o editar) -----
    const handleSaveUser = async () => {
        if (!newUserName || !newUserEmail || (!editingUser && !newUserPassword)) {
            showToast('Nombre, email y contraseña son obligatorios', 'warning');
            return;
        }

        try {
            const payload = {
                usuario: {
                    id: editingUser?.id ?? 0,
                    nombre: newUserName,
                    email: newUserEmail,
                    roles: newUserRole, // STRING
                    activo: newUserActivo,
                    password: editingUser ? (newUserPassword || undefined) : newUserPassword
                }
            };

            if (editingUser) {
                // EDITAR
                await auth.updateUsuario(editingUser.id, payload.usuario);

                setUsers(users.map(u =>
                    u.id === editingUser.id
                        ? {
                            ...u,
                            nombre: payload.usuario.nombre,
                            email: payload.usuario.email,
                            roles: payload.usuario.roles,
                            activo: payload.usuario.activo,
                            status: payload.usuario.activo === 1 ? 'active' : 'inactive',
                        }
                        : u
                ));

                showToast('Usuario actualizado correctamente', 'success');
            } else {
                // CREAR
                const createdUser = await auth.createUsuario(payload.usuario);

                setUsers([
                    {
                        id: createdUser.id || Date.now(),
                        nombre: payload.usuario.nombre,
                        email: payload.usuario.email,
                        roles: payload.usuario.roles,
                        activo: payload.usuario.activo,
                        status: 'active',
                        password: payload.usuario.password
                    },
                    ...users
                ]);

                showToast('Usuario agregado correctamente', 'success');
            }

            // Reset modal
            setIsModalOpen(false);
            setEditingUser(null);
            setNewUserName('');
            setNewUserEmail('');
            setNewUserRole('ROLE_USER');
            setNewUserPassword('');
            setNewUserActivo(1);

        } catch (error: any) {
            console.error(error);
            showToast(error.response?.data?.message || 'Error al guardar usuario', 'error');
        }
    };

    // ----------- Delete User ----------------
    const handleDelete = async (userId: number) => {
        const confirmDelete = window.confirm('¿Estás seguro de eliminar este usuario?');
        if (!confirmDelete) return;

        try {
            // await auth.deleteUsuario(userId);
            setUsers(users.filter(u => u.id !== userId));
            showToast('Usuario eliminado correctamente', 'success');
        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            showToast('No se pudo eliminar el usuario', 'error');
        }
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                <h1 className="text-2xl font-bold text-gray-800">Administración de Usuarios</h1>
                <button
                    className="btn bg-[#FF6B35] text-white flex items-center space-x-2 mt-4 md:mt-0"
                    onClick={() => { setEditingUser(null); setIsModalOpen(true); }}
                >
                    <Check size={18} />
                    <span>Agregar Usuario</span>
                </button>
            </div>

            {/* Filters & Search */}
            <div className="bg-white rounded-xl shadow-md overflow-hidden mt-4">
                <div className="p-5 border-b border-gray-200 flex flex-col md:flex-row justify-between space-y-4 md:space-y-0">
                    <div className="relative max-w-md">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search size={18} className="text-gray-400" />
                        </div>
                        <input
                            type="text"
                            className="pl-10 form-input w-full max-w-xs"
                            placeholder="Buscar por nombre o email"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                    <div className="relative">
                        <button
                            onClick={() => setIsFilterOpen(!isFilterOpen)}
                            className="flex items-center space-x-1 bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <span>Estado: {filterStatus}</span>
                        </button>
                        {isFilterOpen && (
                            <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                <div className="py-1">
                                    <button onClick={() => { setFilterStatus('all'); setIsFilterOpen(false); }} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Todos</button>
                                    <button onClick={() => { setFilterStatus('active'); setIsFilterOpen(false); }} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Activos</button>
                                    <button onClick={() => { setFilterStatus('inactive'); setIsFilterOpen(false); }} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Inactivos</button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Tabla de usuarios */}
                <div className="overflow-x-auto">
                    {sortedUsers.length > 0 ? (
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th onClick={() => handleSort('id')} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID {sortConfig?.key === 'id' ? (sortConfig.direction === 'asc' ? ' 🔼' : ' 🔽') : null}</th>
                                <th onClick={() => handleSort('nombre')} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre {sortConfig?.key === 'nombre' ? (sortConfig.direction === 'asc' ? ' 🔼' : ' 🔽') : null}</th>
                                <th onClick={() => handleSort('email')} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email {sortConfig?.key === 'email' ? (sortConfig.direction === 'asc' ? ' 🔼' : ' 🔽') : null}</th>
                                <th onClick={() => handleSort('roles')} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol {sortConfig?.key === 'roles' ? (sortConfig.direction === 'asc' ? ' 🔼' : ' 🔽') : null}</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {sortedUsers.map(user => (
                                <tr key={user.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap">{user.id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{user.nombre}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{user.email}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{user.roles}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                {user.status === 'active' ? 'Activo' : 'Inactivo'}
                                            </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap space-x-2">
                                        <button className="text-blue-500 hover:text-blue-700" onClick={() => openEditModal(user)}>
                                            <Pencil size={16} />
                                        </button>
                                        <button className="text-red-500 hover:text-red-700" onClick={() => handleDelete(user.id)}><X size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="text-center py-10">
                            <X size={48} className="mx-auto text-gray-400" />
                            <h3 className="mt-4 text-lg font-medium text-gray-900">No hay usuarios</h3>
                            <p className="mt-1 text-sm text-gray-500">No se encontraron usuarios con los filtros seleccionados.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal completo con TODOS los campos */}
            {isModalOpen && (
                <Modal onClose={() => setIsModalOpen(false)}>
                    <h2 className="text-xl font-bold mb-4">{editingUser ? 'Editar Usuario' : 'Agregar Usuario'}</h2>
                    <p className="text-gray-600 mb-4">Nombre:</p>
                    <input
                        type="text"
                        placeholder="Nombre"
                        value={newUserName}
                        onChange={(e) => setNewUserName(e.target.value)}
                        className="mb-2 w-full border border-gray-300 rounded px-3 py-2"
                    />
                    <p className="text-gray-600 mb-4">Correo:</p>
                    <input
                        type="email"
                        placeholder="Email"
                        value={newUserEmail}
                        onChange={(e) => setNewUserEmail(e.target.value)}
                        className="mb-2 w-full border border-gray-300 rounded px-3 py-2"
                    />
                    <p className="text-gray-600 mb-4">Clave:</p>
                    <input
                        type="password"
                        placeholder="Contraseña"
                        value={newUserPassword}
                        onChange={(e) => setNewUserPassword(e.target.value)}
                        className="mb-2 w-full border border-gray-300 rounded px-3 py-2"
                    />
                    <p className="text-gray-600 mb-4">Rol:</p>
                    <select
                        value={newUserRole}
                        onChange={(e) => setNewUserRole(e.target.value)}
                        className="mb-2 w-full border border-gray-300 rounded px-3 py-2"
                    >
                        <option value="ROLE_USER">Usuario</option>
                        <option value="ROLE_ADMIN">Admin</option>
                    </select>
                    <p className="text-gray-600 mb-4">Activo/Inactivo:</p>
                    <select
                        value={newUserActivo}
                        onChange={(e) => setNewUserActivo(Number(e.target.value))}
                        className="mb-2 w-full border border-gray-300 rounded px-3 py-2"
                    >
                        <option value={1}>Activo</option>
                        <option value={0}>Inactivo</option>
                    </select>

                    <div className="flex justify-end space-x-2">
                        <button onClick={() => setIsModalOpen(false)} className="px-4 py-2 border rounded">Cancelar</button>
                        <button onClick={handleSaveUser} className="px-4 py-2 bg-[#FF6B35] text-white rounded">
                            {editingUser ? 'Guardar Cambios' : 'Agregar'}
                        </button>
                    </div>
                </Modal>
            )}
        </div>
    );
};

export default AdminPage;

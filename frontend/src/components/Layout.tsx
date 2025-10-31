import React, { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import {
    Menu,
    X,
    Home,
    ShoppingCart,
    DollarSign,
    FileText,
    Package,
    PieChart,
    LogOut,
    User,
    NotebookTextIcon,
    UserCog,
} from 'lucide-react';

interface LayoutProps {
    children: React.ReactNode;
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const isAdmin = user?.roles === 'ROLE_ADMIN';

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    const toggleMobileMenu = () => setIsMobileMenuOpen(!isMobileMenuOpen);
    const closeMobileMenu = () => setIsMobileMenuOpen(false);

    interface MenuItem {
        to: string;
        icon: React.ReactNode;
        text: string;
        access: 'all' | 'ROLE_ADMIN';
        isGreen?: boolean;
    }

    const menuItems: MenuItem[] = [
        { to: '/dashboard', icon: <Home size={20} />, text: 'Inicio', access: 'all' },
        { to: '/orders', icon: <ShoppingCart size={20} />, text: 'Pedidos', access: 'all' },
        { to: '/cash-register', icon: <DollarSign size={20} />, text: 'Caja', access: 'all' },
        { to: '/products', icon: <Package size={20} />, text: 'Productos', access: 'all' },
        { to: '/expenses', icon: <FileText size={20} />, text: 'Egresos', access: 'ROLE_ADMIN' },
        { to: '/reports', icon: <PieChart size={20} />, text: 'Reportes', access: 'ROLE_ADMIN' },
        { to: '/comprobantes', icon: <NotebookTextIcon size={20} />, text: 'Comprobantes', access: 'ROLE_ADMIN' },
        { to: '/admin', icon: <UserCog size={20} />, text: 'Administrador', access: 'ROLE_ADMIN', isGreen: true },
    ];

    const filteredMenuItems = menuItems.filter(
        (item) => item.access === 'all' || (item.access === 'ROLE_ADMIN' && isAdmin)
    );

    return (
        <div className="flex h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 text-white">
            {/* Sidebar (desktop) */}
            <div className="hidden md:flex md:flex-shrink-0">
                <div className="flex flex-col w-64 backdrop-blur-md bg-white/10 border-r border-white/10 shadow-xl">
                    <div className="flex items-center justify-center h-16 px-4 bg-blue-600/80 text-white shadow-md">
                        <h2 className="text-lg font-bold tracking-wide">Sistema de Comidas</h2>
                    </div>
                    <div className="flex flex-col flex-grow px-4 py-4">
                        <nav className="flex-1 space-y-2">
                            {filteredMenuItems.map((item) => (
                                <NavLink
                                    key={item.to}
                                    to={item.to}
                                    className={({ isActive }) => {
                                        if (item.isGreen) {
                                            return `flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all bg-emerald-600/30 text-emerald-200 hover:bg-emerald-500/40`;
                                        } else {
                                            return `flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all ${
                                                isActive
                                                    ? 'bg-blue-600 text-white shadow-md'
                                                    : 'text-blue-100 hover:bg-blue-500/30 hover:text-white'
                                            }`;
                                        }
                                    }}
                                >
                                    {item.icon}
                                    <span className="ml-3">{item.text}</span>
                                </NavLink>
                            ))}
                        </nav>

                        {/* Usuario */}
                        <div className="pt-4 mt-auto border-t border-white/10">
                            <div className="flex items-center px-4 py-3 mb-2">
                                <div className="flex-shrink-0">
                                    <div className="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                        <User size={20} />
                                    </div>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium">{user?.nombre}</p>
                                </div>
                            </div>
                            <button
                                onClick={handleLogout}
                                className="flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg text-blue-200 hover:bg-red-500/20 hover:text-red-200 transition-colors"
                            >
                                <LogOut size={18} />
                                <span className="ml-3">Cerrar Sesión</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Contenedor principal */}
            <div className="flex flex-col flex-1 overflow-hidden">
                {/* Header móvil */}
                <div className="md:hidden bg-blue-700/90 px-4 py-3 flex items-center justify-between shadow-md backdrop-blur-md">
                    <div className="flex items-center">
                        <button onClick={toggleMobileMenu} className="text-blue-100 focus:outline-none">
                            <Menu size={24} />
                        </button>
                        <h1 className="ml-4 text-lg font-semibold text-white">Sistema de Comidas</h1>
                    </div>
                    <div className="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                        <User size={18} />
                    </div>
                </div>

                {/* Menú móvil */}
                {isMobileMenuOpen && (
                    <div className="fixed inset-0 z-40 flex md:hidden">
                        <div className="fixed inset-0 bg-black bg-opacity-60" onClick={closeMobileMenu}></div>
                        <div className="relative flex flex-col w-full max-w-xs pb-4 bg-blue-800/95 backdrop-blur-lg">
                            <div className="flex items-center justify-between px-4 pt-5 pb-3 border-b border-blue-600/50">
                                <h2 className="text-lg font-bold text-white">Menú</h2>
                                <button onClick={closeMobileMenu} className="text-blue-100">
                                    <X size={24} />
                                </button>
                            </div>

                            <div className="flex-1 px-2 mt-3">
                                <nav className="flex flex-col space-y-2">
                                    {filteredMenuItems.map((item) => (
                                        <NavLink
                                            key={item.to}
                                            to={item.to}
                                            onClick={closeMobileMenu}
                                            className={({ isActive }) =>
                                                `flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors ${
                                                    isActive
                                                        ? 'bg-blue-600 text-white'
                                                        : 'text-blue-100 hover:bg-blue-500/40 hover:text-white'
                                                }`
                                            }
                                        >
                                            {item.icon}
                                            <span className="ml-3">{item.text}</span>
                                        </NavLink>
                                    ))}
                                </nav>
                            </div>

                            <div className="px-2 mt-6 border-t border-blue-700 pt-4">
                                <div className="flex items-center px-4 mb-3">
                                    <div className="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                        <User size={20} />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm font-medium text-white">{user?.nombre}</p>
                                    </div>
                                </div>
                                <button
                                    onClick={handleLogout}
                                    className="flex items-center w-full px-4 py-3 text-base font-medium rounded-lg text-blue-100 hover:bg-red-500/20 hover:text-red-200"
                                >
                                    <LogOut size={20} />
                                    <span className="ml-3">Cerrar Sesión</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Contenido */}
                <main className="flex-1 overflow-y-auto p-6 backdrop-blur-sm bg-white/10 border-t border-white/10 shadow-inner">
                    <div className="page-transition">{children}</div>
                </main>
            </div>
        </div>
    );
};

export default Layout;

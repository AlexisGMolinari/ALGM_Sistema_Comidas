import React, { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import {Menu, X, Home, ShoppingCart, DollarSign, FileText, Package, PieChart, LogOut, User, NotebookTextIcon, UserCog} from 'lucide-react';

interface LayoutProps {
  children: React.ReactNode;
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
  };

  const closeMobileMenu = () => {
    setIsMobileMenuOpen(false);
  };

  const isAdmin = user?.roles === 'ROLE_ADMIN';

  interface MenuItem { to: string; icon: React.ReactNode; text: string; access: 'all' | 'ROLE_ADMIN'; isGreen?: boolean; }

  const menuItems: MenuItem[] = [
    { to: '/dashboard', icon: <Home size={20} />, text: 'Inicio', access: 'all' },
    { to: '/orders', icon: <ShoppingCart size={20} />, text: 'Pedidos', access: 'all' },
    { to: '/cash-register', icon: <DollarSign size={20} />, text: 'Caja', access: 'all' },
    { to: '/products', icon: <Package size={20} />, text: 'Productos', access: 'all' }, //cambiar "all" por "admin" cuando se tenga el login bien
    { to: '/expenses', icon: <FileText size={20} />, text: 'Egresos', access: 'ROLE_ADMIN' },
    { to: '/reports', icon: <PieChart size={20} />, text: 'Reportes', access: 'ROLE_ADMIN' },
    { to: '/comprobantes', icon: <NotebookTextIcon size={20} />, text: 'Comprobantes', access: 'ROLE_ADMIN' },
    { to: '/admin', icon: <UserCog size={20} />, text: 'Administrador', access: 'ROLE_ADMIN', isGreen: true },
  ];

  const filteredMenuItems = menuItems.filter(
    item => item.access === 'all' || (item.access === 'ROLE_ADMIN' && isAdmin)
  );

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar - desktop */}
      <div className="hidden md:flex md:flex-shrink-0">
        <div className="flex flex-col w-64 bg-white shadow-md">
          <div className="flex items-center justify-center h-16 px-4 bg-[#FF6B35] text-white">
            <h2 className="text-xl font-bold">Carri-Bar</h2>
          </div>
          <div className="flex flex-col flex-grow px-4 py-4">
            <nav className="flex-1 space-y-2">
              {filteredMenuItems.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) => {
                      if(item.isGreen){
                    return `flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors bg-green-100 text-green-800 hover:bg-green-200`;
                  } else {
                    return `flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors ${
                      isActive
                        ? 'bg-[#FF6B35] text-white'
                        : 'text-gray-700 hover:bg-[#FFEDE5] hover:text-[#FF6B35]'
                    }`;
                  }
                  }}
                >
                  {item.icon}
                  <span className="ml-3">{item.text}</span>
                </NavLink>
              ))}
            </nav>
            <div className="pt-4 mt-auto border-t border-gray-200">
              <div className="flex items-center px-4 py-3 mb-2">
                <div className="flex-shrink-0">
                  <div className="h-10 w-10 rounded-full bg-[#2EC4B6] flex items-center justify-center text-white">
                    <User size={20} />
                  </div>
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-gray-700">{user?.nombre}</p>
                  {/*<p className="text-xs text-gray-500 capitalize">{user?.roles}</p>*/}
                </div>
              </div>
              <button
                onClick={handleLogout}
                className="flex items-center w-full px-4 py-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-red-50 hover:text-red-500"
              >
                <LogOut size={20} />
                <span className="ml-3">Cerrar Sesión</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile header */}
      <div className="flex flex-col flex-1 overflow-hidden">
        <div className="md:hidden">
          <div className="flex items-center justify-between px-4 py-3 bg-white shadow-sm">
            <div className="flex items-center">
              <button
                onClick={toggleMobileMenu}
                className="text-gray-500 focus:outline-none focus:text-gray-700"
              >
                <Menu size={24} />
              </button>
              <h1 className="ml-4 text-lg font-semibold text-gray-800">Carri-Bar</h1>
            </div>
            <div className="flex items-center">
              <div className="h-8 w-8 rounded-full bg-[#2EC4B6] flex items-center justify-center text-white">
                <User size={18} />
              </div>
            </div>
          </div>
        </div>

        {/* Mobile menu */}
        {isMobileMenuOpen && (
          <div className="fixed inset-0 z-40 flex md:hidden">
            <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={closeMobileMenu}></div>
            <div className="relative flex flex-col w-full max-w-xs pb-4 bg-white">
              <div className="flex items-center justify-between px-4 pt-5 pb-2">
                <h2 className="text-lg font-bold text-gray-800">Menú</h2>
                <button
                  onClick={closeMobileMenu}
                  className="text-gray-500 focus:outline-none"
                >
                  <X size={24} />
                </button>
              </div>
              <div className="flex-1 px-2 mt-2">
                <nav className="flex flex-col space-y-2">
                  {filteredMenuItems.map((item) => (
                    <NavLink
                      key={item.to}
                      to={item.to}
                      onClick={closeMobileMenu}
                      className={({ isActive }) => {
                        if (item.isGreen) {
                          return `flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors bg-green-100 text-green-800 hover:bg-green-200`;
                        } else {
                          return `flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors ${
                              isActive
                                  ? 'bg-[#FF6B35] text-white'
                                  : 'text-gray-700 hover:bg-[#FFEDE5] hover:text-[#FF6B35]'
                          }`;
                        }
                      }}
                    >
                      {item.icon}
                      <span className="ml-3">{item.text}</span>
                    </NavLink>
                  ))}
                </nav>
              </div>
              <div className="px-2 mt-6 border-t border-gray-200 pt-4">
                <div className="flex items-center px-4 mb-3">
                  <div className="flex-shrink-0">
                    <div className="h-10 w-10 rounded-full bg-[#2EC4B6] flex items-center justify-center text-white">
                      <User size={20} />
                    </div>
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-700">{user?.nombre}</p>
                  </div>
                </div>
                <button
                    onClick={handleLogout}
                    className="flex items-center w-full px-4 py-3 text-base font-medium text-gray-700 rounded-lg hover:bg-red-50 hover:text-red-500"
                >
                  <LogOut size={20} />
                  <span className="ml-3">Cerrar Sesión</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Main content */}
        <main className="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
          <div className="page-transition">{children}</div>
        </main>
      </div>
    </div>
  );
};

export default Layout;
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShoppingCart, DollarSign, FileText, PieChart, CreditCard, Clock } from 'lucide-react';
import useAuth from '../hooks/useAuth';
import { getDashboardResumen } from "../contexts/api.ts";
import { DashboardResumen } from "../types";
import {useToast} from "../components/common/SimpleToast.tsx";


interface QuickAccessCardProps {
  to: string;
  icon: React.ReactNode;
  title: string;
  bgColor: string;
  textColor: string;
}

const QuickAccessCard: React.FC<QuickAccessCardProps> = ({ to, icon, title, bgColor, textColor }) => (
  <Link 
    to={to} 
    className={`${bgColor} ${textColor} rounded-xl p-5 flex flex-col items-center shadow-md hover:shadow-lg transition-shadow duration-200`}
  >
    <div className="mb-3">{icon}</div>
    <h3 className="text-lg font-medium text-center">{title}</h3>
  </Link>
);

const Dashboard: React.FC = () => {
  const { user } = useAuth();
  const isAdmin = user?.roles === 'ROLE_ADMIN';
  //const isUser = user?.roles === 'ROLE_USER';
    const { showToast } = useToast();

  const [dashboardData, setDashboardData] = useState<DashboardResumen | null>(null);
  const [loading, setLoading] = useState(true);

  const currentDate = new Date().toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
  
  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
    }).format(amount);
  };

  useEffect(() => {
    const fetchData = async () => {
      try {
        const data = await getDashboardResumen();
        setDashboardData(data);
      } catch (error) {
        console.error('Error al cargar resumen del dashboard', error);
        showToast('Error al cargar resumen del dashboard', 'error' );
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading || !dashboardData) {
    return <div className="text-white text-center mt-10">Cargando panel...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">Panel Principal</h1>
          <p className="text-gray-100 mt-1 capitalize">{currentDate}</p>
        </div>
        <div className="mt-4 md:mt-0">
          <div className={`inline-flex items-center px-3 py-1 rounded-full ${
            dashboardData.cashRegisterStatus === 'open' 
              ? 'bg-green-100 text-green-800' 
              : 'bg-red-100 text-red-800'
          }`}>
            <Clock size={16} className="mr-1" />
            <span className="text-sm font-medium capitalize">
              Caja {dashboardData.cashRegisterStatus === 'open' ? 'Abierta' : 'Cerrada'}
            </span>
          </div>
        </div>
      </div>
      
      {/* Stats Cards Solo Admin*/}
      {isAdmin && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div className="card-transition bg-cyan-700 p-5 rounded-xl shadow-md border-l-4 border-[#FF6B35]">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-white">Ventas del Día</p>
                <h3 className="text-2xl font-bold text-orange-400 mt-1">{formatCurrency(dashboardData.salesTotal)}</h3>
              </div>
              <div className="h-12 w-12 bg-[#FFEDE5] rounded-full flex items-center justify-center">
                <DollarSign size={24} className="text-[#FF6B35]" />
              </div>
            </div>
          </div>

          <div className="card-transition bg-cyan-700 p-5 rounded-xl shadow-md border-l-4 border-[#2EC4B6]">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-white">Egresos del Día</p>
                <h3 className="text-2xl font-bold text-cyan-400 mt-1">{formatCurrency(dashboardData.dailyExpenses)}</h3>
              </div>
              <div className="h-12 w-12 bg-[#E6F9F7] rounded-full flex items-center justify-center">
                <FileText size={24} className="text-[#2EC4B6]" />
              </div>
            </div>
          </div>

          <div className="card-transition bg-cyan-700 p-5 rounded-xl shadow-md border-l-4 border-[#FDCA40]">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-white">Balance del Día</p>
                <h3 className="text-2xl font-bold text-yellow-300 mt-1">{formatCurrency(dashboardData.dailyBalance)}</h3>
              </div>
              <div className="h-12 w-12 bg-[#FFF8E5] rounded-full flex items-center justify-center">
                <PieChart size={24} className="text-[#FDCA40]" />
              </div>
            </div>
          </div>
        </div>
      )}
      
      {/* Order Status */}
      <div className="bg-cyan-700 p-5 rounded-xl shadow-md card-transition">
        <h2 className="text-xl font-semibold text-white mb-4">Estado de Pedidos</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div className="flex items-center justify-between bg-orange-50 p-4 rounded-lg">
            <div className="flex items-center">
              <div className="h-10 w-10 bg-[#FF6B35] rounded-full flex items-center justify-center text-white">
                <Clock size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Pedidos Pendientes</p>
                <p className="text-xl font-bold text-gray-800">{dashboardData.pendingOrders}</p>
              </div>
            </div>
            <Link to="/orders" className="text-[#FF6B35] hover:text-[#D6492C] font-medium text-sm">Ver Pedidos</Link>
          </div>
          
          <div className="flex items-center justify-between bg-green-50 p-4 rounded-lg">
            <div className="flex items-center">
              <div className="h-10 w-10 bg-[#3BB273] rounded-full flex items-center justify-center text-white">
                <ShoppingCart size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Pedidos Completados Hoy</p>
                <p className="text-xl font-bold text-gray-800">{dashboardData.completedOrdersToday}</p>
              </div>
            </div>
            <Link to="/orders" className="text-[#3BB273] hover:text-[#2E9D60] font-medium text-sm">Ver Historial</Link>
          </div>
        </div>
      </div>
      
      {/* Acceso Rápido */}
      <div className="bg-cyan-700 p-5 rounded-xl shadow-md card-transition">
        <h2 className="text-xl font-semibold text-white mb-4">Acceso Rápido</h2>
        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
          <QuickAccessCard 
            to="/orders" 
            icon={<ShoppingCart size={28} />} 
            title="Nuevo Pedido" 
            bgColor="bg-[#FFEDE5]" 
            textColor="text-[#FF6B35]"
          />
          {/* Solo ADMIN ve el resto */}
          {isAdmin && (
              <>
              <QuickAccessCard
                to="/cash-register"
                icon={<DollarSign size={28} />}
                title="Gestionar Caja"
                bgColor="bg-[#E6F9F7]"
                textColor="text-[#2EC4B6]"
              />

              <QuickAccessCard
                to="/expenses"
                icon={<FileText size={28} />}
                title="Registrar Egreso"
                bgColor="bg-[#FFF8E5]"
                textColor="text-[#D4A829]"
              />

              </>
          )}
        </div>
      </div>
      
      {/* Admin Section */}
      {isAdmin && (
        <div className="bg-cyan-700 p-5 rounded-xl shadow-md card-transition">
          <h2 className="text-xl font-semibold text-white mb-4">Administración</h2>
          <div className="flex justify-center">
            <Link to="/products" className="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors max-w-md w-full">
              <div className="h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                <CreditCard size={28} />
              </div>
              <div className="ml-3">
                <p className="font-medium text-gray-800">Gestionar Productos</p>
                <p className="text-sm text-gray-600">Actualizar precios, agregar o eliminar productos</p>
              </div>
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
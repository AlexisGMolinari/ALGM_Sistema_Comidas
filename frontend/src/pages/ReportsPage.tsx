import React, { useEffect, useState } from 'react';
import { 
  PieChart, BarChart, Calendar, Download,
  ArrowUp, ArrowDown, DollarSign, ShoppingCart 
} from 'lucide-react';
import { MonthlyReport, DailyReport } from '../types';
import { fetchMonthlyReport, fetchDailyReport } from '../contexts/api';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';



// Utility function to format currency
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(amount);
};

// Utility function to format date
const formatDate = (date: Date): string => {
  return date.toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

// Utility function to format as day name
const formatDayName = (date: Date): string => {
  return date.toLocaleDateString('es-ES', {
    weekday: 'long',
  });
};

const ReportsPage: React.FC = () => {
    const [activeTab, setActiveTab] = useState<'daily' | 'monthly'>('daily');
    const [monthly, setMonthly] = useState<MonthlyReport | null>(null);
    const [daily, setDaily] = useState<DailyReport[] | null>(null);

// 🔥 Nuevo: mes actual seleccionado por defecto
    const currentMonth = new Date().getMonth() == 1 ; // 1 = enero, 12 = diciembre
    const [selectedMonth, setSelectedMonth] = useState<string>(currentMonth.toString());

    useEffect(() => {
        const fetchReports = async () => {
            try {
                // Siempre cargar el diario
                const dailyData = await fetchDailyReport();
                setDaily(dailyData);

                // 🔥 Siempre cargar el mensual por defecto al inicio
                const monthlyData = await fetchMonthlyReport(selectedMonth);
                setMonthly(monthlyData);
            } catch (error) {
                console.error('Error cargando reportes:', error);
            }
        };

        fetchReports();
    }, [selectedMonth]);

    if (activeTab === 'daily' && !daily) return <div>Cargando reportes...</div>;
    if (activeTab === 'monthly' && !monthly) return <div>Cargando reportes...</div>;


    // Reporte diario
    const handleExportDaily = () => {
        if (!daily) return;

        const headers = ['Fecha', 'Ventas', 'Egresos', 'Balance', 'Pedidos'];
        const data = daily.map(d => [
            formatDate(d.date),
            formatCurrency(d.totalSales),
            formatCurrency(d.totalExpenses),
            formatCurrency(d.balance),
            d.ordersCount
        ]);

        const doc = new jsPDF();
        doc.setFontSize(16);
        doc.text('Reporte Diario', 14, 20);

        autoTable(doc, {
            startY: 30,
            head: [headers],
            body: data,
        });

        doc.save('Reporte_Diario.pdf');
    };


    //Reporte Mensual
    const handleExportMonthly = () => {
        if (!monthly) return;

        const headers = ['Categoría', 'Valor'];

        const ventas = monthly.salesByCategory.map(c => [c.name, formatCurrency(c.value)]);
        const egresos = monthly.expensesByCategory.map(c => [c.name, formatCurrency(c.value)]);

        const doc = new jsPDF();
        doc.setFontSize(16);
        doc.text('Resumen Mensual - Ventas', 14, 20);

        autoTable(doc, {
            startY: 30,
            head: [headers],
            body: ventas,
        });

        doc.text('Resumen Mensual - Egresos', 14, (doc as any).lastAutoTable.finalY + 10);
        autoTable(doc, {
            startY: (doc as any).lastAutoTable.finalY + 20,
            head: [headers],
            body: egresos,
        });

        doc.text(
            `Balance Neto: ${formatCurrency(monthly.balance)}`,
            14,
            (doc as any).lastAutoTable.finalY + 20
        );

        doc.save(`Resumen_Mensual_${monthly.month}.pdf`);
    };


    // Calculate total metrics
  const totalSales = (daily ?? []).reduce((sum, d) => sum + d.totalSales, 0);
  const totalExpenses = (daily ?? []).reduce((sum, d) => sum + d.totalExpenses, 0);
  const totalBalance = totalSales - totalExpenses;
  const totalOrders = (daily ?? []).reduce((sum, d) => sum + d.ordersCount, 0);


  // Calculate percentage of category in total
  const calculatePercentage = (value: number, total: number): string => {
    return ((value / total) * 100).toFixed(1) + '%';
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <h1 className="text-2xl font-bold text-white">Reportes Financieros</h1>
          <div className="mt-4 md:mt-0 flex space-x-2 items-center">
              {/* Botón Diario */}
              <button
                  onClick={() => setActiveTab('daily')}
                  className={`px-4 py-2 rounded-lg text-sm font-medium ${
                      activeTab === 'daily'
                          ? 'bg-[#FF6B35] text-white'
                          : 'bg-white text-gray-700 border border-gray-300'
                  }`}
              >
                  Diario
              </button>

              {/* Selector de Meses */}
              <div
                  className={`flex items-center border rounded-lg px-3 py-2 ${
                      activeTab === 'monthly'
                          ? 'border-[#FF6B35] bg-[#FFF3EF]'
                          : 'border-gray-300 bg-white'
                  }`}
              >
                  <select
                      value={selectedMonth}
                      onChange={(e) => {
                          setActiveTab('monthly');
                          const newMonth = e.target.value;

                          // 🔥 Si selecciona el mismo mes, forzamos recarga manual
                          if (newMonth === selectedMonth) {
                              setSelectedMonth(''); // fuerza cambio
                              setTimeout(() => setSelectedMonth(newMonth), 0);
                          } else {
                              setSelectedMonth(newMonth);
                          }
                      }}
                      className="text-sm font-medium text-gray-700 bg-transparent focus:outline-none cursor-pointer"
                  >
                      {[
                          'Enero',
                          'Febrero',
                          'Marzo',
                          'Abril',
                          'Mayo',
                          'Junio',
                          'Julio',
                          'Agosto',
                          'Septiembre',
                          'Octubre',
                          'Noviembre',
                          'Diciembre',
                      ].map((month, i) => (
                          <option key={i} value={i + 1}>
                              {month}
                          </option>
                      ))}
                  </select>
              </div>
          </div>
      </div>
      
      {activeTab === 'daily' ? (
        <div className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-[#FFEDE5] rounded-full flex items-center justify-center text-[#FF6B35]">
                  <DollarSign size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Ventas Totales (7 días)</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(totalSales)}</p>
                </div>
              </div>
            </div>
            
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-[#E6F9F7] rounded-full flex items-center justify-center text-[#2EC4B6]">
                  <ArrowDown size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Egresos Totales (7 días)</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(totalExpenses)}</p>
                </div>
              </div>
            </div>
            
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-[#FFF8E5] rounded-full flex items-center justify-center text-[#FDCA40]">
                  <ArrowUp size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Balance Total (7 días)</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(totalBalance)}</p>
                </div>
              </div>
            </div>
            
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                  <ShoppingCart size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Pedidos Totales (7 días)</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{totalOrders}</p>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl shadow-md overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
              <h2 className="text-lg font-semibold text-gray-800">Reporte Diario</h2>
                <button
                    onClick={handleExportDaily}
                    className="text-[#2EC4B6] hover:text-[#23A399] flex items-center text-sm font-medium"
                >
                    <Download size={16} className="mr-1" />
                    Exportar
                </button>

            </div>
            
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                <tr>
                  <th scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha
                  </th>
                  <th scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ventas
                  </th>
                  <th scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Egresos
                  </th>
                  <th scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Balance
                  </th>
                  <th scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Pedidos
                  </th>
                  {/*<th scope="col"*/}
                  {/*    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">*/}
                  {/*  Detalles*/}
                  {/*</th>*/}
                </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                {daily?.map((report, index) => (
                    <tr
                        key={index}
                        className={index === 0 ? 'bg-[#FFEDE5] bg-opacity-30' : 'hover:bg-gray-50'}
                    >
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex flex-col">
                          <div className="text-sm font-medium text-gray-900">
                            {formatDate(report.date)}
                          </div>
                          <div className="text-sm text-gray-500 capitalize">
                            {formatDayName(report.date)}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {formatCurrency(report.totalSales)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-red-600">
                          {formatCurrency(report.totalExpenses)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-green-600">
                          {formatCurrency(report.balance)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{report.ordersCount}</div>
                      </td>
                      {/*<td className="px-6 py-4 whitespace-nowrap text-sm font-medium">*/}
                      {/*  <button className="text-[#2EC4B6] hover:text-[#23A399]">*/}
                      {/*    <ChevronsRight size={20} />*/}
                      {/*  </button>*/}
                      {/*</td>*/}
                    </tr>
                ))}
                </tbody>


              </table>
            </div>
          </div>
        </div>
      ) : (
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div className="card-transition bg-white p-5 rounded-xl shadow-md">
                <div className="flex items-center">
                  <div className="h-12 w-12 bg-[#FFEDE5] rounded-full flex items-center justify-center text-[#FF6B35]">
                    <Calendar size={24}/>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-500">Periodo</p>
                    <p className="text-xl font-bold text-gray-800 mt-1">{monthly?.month ?? 0}</p>
                  </div>
                </div>
              </div>

              <div className="card-transition bg-white p-5 rounded-xl shadow-md">
                <div className="flex items-center">
                  <div className="h-12 w-12 bg-[#E6F9F7] rounded-full flex items-center justify-center text-[#2EC4B6]">
                    <DollarSign size={24}/>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-500">Ventas Mensuales</p>
                    <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(monthly?.totalSales ?? 0)}</p>
                </div>
              </div>
            </div>
            
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-[#FFF8E5] rounded-full flex items-center justify-center text-[#FDCA40]">
                  <ArrowDown size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Egresos Mensuales</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(monthly?.totalExpenses ?? 0)}</p>
                </div>
              </div>
            </div>
            
            <div className="card-transition bg-white p-5 rounded-xl shadow-md">
              <div className="flex items-center">
                <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                  <ArrowUp size={24} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-500">Balance Mensual</p>
                  <p className="text-xl font-bold text-gray-800 mt-1">{formatCurrency(monthly?.balance ?? 0)}</p>
                </div>
              </div>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white rounded-xl shadow-md overflow-hidden">
              <div className="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 className="text-lg font-semibold text-gray-800">Ventas por Categoría</h2>
                <div className="flex items-center text-sm text-gray-500">
                  <BarChart size={16} className="mr-1" />
                  Total: {formatCurrency(monthly?.totalSales ?? 0)}
                </div>
              </div>
              
              <div className="p-5">
                <div className="space-y-4">
                  {monthly?.salesByCategory.map((category, index) => (
                    <div key={index}>
                      <div className="flex justify-between mb-1">
                        <span className="text-sm font-medium text-gray-600">{category.name}</span>
                        <span className="text-sm font-medium text-gray-900">
                          {formatCurrency(category.value)} ({calculatePercentage(category.value, monthly?.totalSales ?? 0)})
                        </span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2.5">
                        <div 
                          className="bg-[#FF6B35] h-2.5 rounded-full" 
                          style={{ width: `${(category.value / monthly.totalSales) * 100}%` }}
                        ></div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
            
            <div className="bg-white rounded-xl shadow-md overflow-hidden">
              <div className="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 className="text-lg font-semibold text-gray-800">Egresos por Categoría</h2>
                <div className="flex items-center text-sm text-gray-500">
                  <PieChart size={16} className="mr-1" />
                  Total: {formatCurrency(monthly?.totalExpenses ?? 0)}
                </div>
              </div>
              
              <div className="p-5">
                <div className="space-y-4">
                  {monthly?.expensesByCategory.map((category, index) => (
                    <div key={index}>
                      <div className="flex justify-between mb-1">
                        <span className="text-sm font-medium text-gray-600">{category.name}</span>
                        <span className="text-sm font-medium text-gray-900">
                          {formatCurrency(category.value)} ({calculatePercentage(category.value, monthly?.totalExpenses ?? 0)})
                        </span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2.5">
                        <div 
                          className="bg-[#2EC4B6] h-2.5 rounded-full" 
                          style={{ width: `${(category.value / monthly?.totalExpenses) * 100}%` }}
                        ></div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl shadow-md overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
              <h2 className="text-lg font-semibold text-gray-800">Resumen Mensual</h2>
                <button
                    onClick={handleExportMonthly}
                    className="text-[#2EC4B6] hover:text-[#23A399] flex items-center text-sm font-medium"
                >
                    <Download size={16} className="mr-1" />
                    Exportar PDF
                </button>

            </div>
            
            <div className="p-5">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-gray-50 p-4 rounded-lg">
                  <h3 className="text-sm font-semibold text-gray-700 mb-3">Información General</h3>
                  
                  <div className="space-y-3">
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Periodo:</span>
                      <span className="text-sm font-medium text-gray-900">{monthly?.month ?? 0}</span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Total de Pedidos:</span>
                      <span className="text-sm font-medium text-gray-900">{monthly?.ordersCount ?? 0}</span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Promedio por Orden:</span>
                      <span className="text-sm font-medium text-gray-900">
                          {formatCurrency(
                              (monthly?.totalSales ?? 0) / (monthly?.ordersCount ?? 1)
                          )}
                      </span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Ventas Totales:</span>
                      <span className="text-sm font-medium text-green-600">{formatCurrency(monthly?.totalSales ?? 0)}</span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Egresos Totales:</span>
                      <span className="text-sm font-medium text-red-600">{formatCurrency(monthly?.totalExpenses ?? 0)}</span>
                    </div>
                    
                    <div className="pt-2 mt-2 border-t border-gray-200 flex justify-between">
                      <span className="text-sm font-semibold text-gray-800">Balance Neto:</span>
                      <span className="text-sm font-bold text-[#FF6B35]">{formatCurrency(monthly?.balance ?? 0)}</span>
                    </div>
                  </div>
                </div>
                
                <div className="bg-[#FFEDE5] bg-opacity-50 p-4 rounded-lg">
                  <h3 className="text-sm font-semibold text-gray-700 mb-3">Comparación con Mes Anterior</h3>
                  
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Ventas:</span>
                      <div className="flex items-center">
                        <span className="text-sm font-medium text-gray-900 mr-2">{formatCurrency(monthly?.totalSales ?? 0)}</span>
                        <span className="flex items-center text-green-600 text-xs font-medium">
                          <ArrowUp size={12} className="mr-0.5" />
                          8.5%
                        </span>
                      </div>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Egresos:</span>
                      <div className="flex items-center">
                        <span className="text-sm font-medium text-gray-900 mr-2">{formatCurrency(monthly?.totalExpenses ?? 0)}</span>
                        <span className="flex items-center text-red-600 text-xs font-medium">
                          <ArrowUp size={12} className="mr-0.5" />
                          4.2%
                        </span>
                      </div>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Pedidos:</span>
                      <div className="flex items-center">
                        <span className="text-sm font-medium text-gray-900 mr-2">{monthly?.ordersCount ?? 0}</span>
                        <span className="flex items-center text-green-600 text-xs font-medium">
                          <ArrowUp size={12} className="mr-0.5" />
                          10.6%
                        </span>
                      </div>
                    </div>

                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Valor Promedio:</span>
                      <div className="flex items-center">
                        <span className="text-sm font-medium text-gray-900 mr-2">
                          {formatCurrency(
                              (monthly?.totalSales ?? 0) / (monthly?.ordersCount ?? 1)
                          )}
                        </span>
                        <span className="flex items-center text-red-600 text-xs font-medium">
                          <ArrowDown size={12} className="mr-0.5" />
                          1.8%
                        </span>
                      </div>
                    </div>
                    
                    <div className="pt-2 mt-2 border-t border-gray-200 flex justify-between items-center">
                      <span className="text-sm font-semibold text-gray-800">Balance:</span>
                      <div className="flex items-center">
                        <span className="text-sm font-bold text-[#FF6B35] mr-2">{formatCurrency(monthly?.balance ?? 0)}</span>
                        <span className="flex items-center text-green-600 text-xs font-medium">
                          <ArrowUp size={12} className="mr-0.5" />
                          9.5%
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ReportsPage;
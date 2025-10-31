import React, { useState, useEffect } from 'react';
import { 
  DollarSign, Clock, ArrowDown, ArrowUp, User,
  AlertCircle, CheckCircle, PlusCircle, MinusCircle 
} from 'lucide-react';
import useAuth from '../hooks/useAuth';
import { CashRegister } from '../types';
import { getCajaActual, createCaja, cierreCaja } from '../contexts/api';
import { useToast } from '../components/common/SimpleToast';


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

// Utility function to format time
const formatTime = (date: Date): string => {
  return date.toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const CashRegisterPage: React.FC = () => {
  const { user } = useAuth();
  const isAdmin = user?.roles === 'ROLE_ADMIN';
    const { showToast } = useToast();
  const [cashRegister, setCashRegister] = useState<CashRegister | null>(null);
  const [loading, setLoading] = useState(true);

  const [showOpenModal, setShowOpenModal] = useState(false);
  const [showCloseModal, setShowCloseModal] = useState(false);
  const [openAmount, setOpenAmount] = useState('5000');
  const [closeNote, setCloseNote] = useState('');
  const [showMovementModal, setShowMovementModal] = useState(false);
  const [movementType, setMovementType] = useState<'in' | 'out'>('in');
  const [movementAmount, setMovementAmount] = useState('');
  const [movementReason, setMovementReason] = useState('');

  useEffect(() => {
    const fetchCaja = async () => {
      try {
        const data = await getCajaActual();
        setCashRegister(data);
      } catch (error) {
        console.error('Error cargando la caja actual', error);
          showToast('Error al cargar la caja actual', 'error' )
      } finally {
        setLoading(false);
      }
    };

    fetchCaja();
  }, []);

  // Function to handle cash register opening
  const handleOpenCashRegister = async () => {
    const amount = parseFloat(openAmount);
    if (isNaN(amount) || amount <= 0) {
        showToast('Ingrese un monto válido para apertura de caja', 'warning' )
      return;
    }

    try {
      await createCaja(amount); // hace POST con monto
      const nuevaCaja = await getCajaActual(); // obtiene caja actualizada
      setCashRegister(nuevaCaja); // actualiza la vista
      setShowOpenModal(false); // cierra modal
    } catch (error) {
      console.error('Error al abrir la caja', error);
      showToast('Error al abrir la caja', 'error' )
    }
  };
  
  // Function to handle cash register closing
  const handleCloseCashRegister = async () => {
    if (!cashRegister) return;

    const currentAmount =
        cashRegister.initialAmount + cashRegister.sales - cashRegister.expenses;

    try {
      await cierreCaja(cashRegister.id, {
        id: cashRegister.id,
        monto_final: currentAmount,
        total_ventas: cashRegister.sales,
        total_gastos: cashRegister.expenses,
        observaciones: closeNote || undefined,
      });

      const nuevaCaja = await getCajaActual();
      setCashRegister(nuevaCaja);
      setShowCloseModal(false);
      showToast('Caja cerrada correctamente', 'success' )
    } catch (error) {
      console.error('Error al cerrar la caja', error);
      showToast('Error al cerrar la caja', 'error' )
    }
  };
  
  // Function to handle cash movement
  const handleCashMovement = () => {
    const amount = parseFloat(movementAmount);
    if (isNaN(amount) || amount <= 0) {
      showToast('Ingrese un monto válido', 'warning' )
      return;
    }

    if (!movementReason.trim()) {
      showToast('Ingrese un motivo para el movimiento', 'warning' )
      return;
    }
    if (!cashRegister) return;

    const updatedExpenses =
        movementType === 'out'
            ? cashRegister.expenses + amount
            : cashRegister.expenses;

    const updatedSales =
        movementType === 'in' ? cashRegister.sales + amount : cashRegister.sales;

    setCashRegister({
      ...cashRegister,
      sales: updatedSales,
      expenses: updatedExpenses,
    });

    setShowMovementModal(false);
    setMovementAmount('');
    setMovementReason('');
  };
  
  // Open cash movement modal
  const openMovementModal = (type: 'in' | 'out') => {
    setMovementType(type);
    setShowMovementModal(true);
  };

// Aquí empieza el renderizado: chequeo explícito para cashRegister
  if (loading) {
    return (
        <div className="flex justify-center items-center h-screen">
          <p className="text-white text-lg">Cargando caja...</p>
        </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <h1 className="text-2xl font-bold text-white">Caja Registradora</h1>
        <div className="mt-4 md:mt-0">
          {!cashRegister || !cashRegister.isOpen ? (
            <div className="flex items-center text-sm font-medium text-red-700 bg-red-100 px-3 py-1 rounded-full">
              <Clock size={16} className="mr-1" />
              <span>Caja Cerrada</span>
            </div>
          ) : (
            <div className="flex items-center text-sm font-medium text-green-700 bg-green-100 px-3 py-1 rounded-full">
              <Clock size={16} className="mr-1" />
              <span>Caja Abierta</span>
            </div>
          )}
        </div>
      </div>

      {cashRegister && cashRegister.isOpen ? (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-2">
            <div className="bg-cyan-700 rounded-xl shadow-md overflow-hidden">
              <div className="px-5 py-4 flex justify-between items-center border-b border-gray-200">
                <h2 className="text-lg font-semibold text-white">Resumen de Caja</h2>
                <div className="flex space-x-3">
                  <button
                    onClick={() => openMovementModal('in')}
                    className="flex items-center px-3 py-1 bg-[#E6F9F7] text-[#2EC4B6] rounded-lg text-sm font-medium hover:bg-[#D1F5F2]"
                  >
                    <PlusCircle size={16} className="mr-1" />
                    Ingreso
                  </button>
                  <button
                    onClick={() => openMovementModal('out')}
                    className="flex items-center px-3 py-1 bg-[#FFEDE5] text-[#FF6B35] rounded-lg text-sm font-medium hover:bg-[#FFE0D1]"
                  >
                    <MinusCircle size={16} className="mr-1" />
                    Egreso
                  </button>
                </div>
              </div>

              <div className="p-5">
                {/* Solo Admin ve info de caja*/}
                {isAdmin && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                  <div className="bg-[#FFEDE5] p-4 rounded-lg">
                    <div className="flex items-center">
                      <div className="h-10 w-10 bg-[#FF6B35] rounded-full flex items-center justify-center text-white">
                        <DollarSign size={20} />
                      </div>
                      <div className="ml-3">
                        <p className="text-sm font-medium text-gray-600">Saldo Actual</p>
                        <p className="text-xl font-bold text-gray-800">{formatCurrency(cashRegister.currentAmount)}</p>
                      </div>
                    </div>
                  </div>
                  
                  <div className="bg-[#E6F9F7] p-4 rounded-lg">
                    <div className="flex items-center">
                      <div className="h-10 w-10 bg-[#2EC4B6] rounded-full flex items-center justify-center text-white">
                        <ArrowUp size={20} />
                      </div>
                      <div className="ml-3">
                        <p className="text-sm font-medium text-gray-600">Ventas Totales</p>
                        <p className="text-xl font-bold text-gray-800">{formatCurrency(cashRegister.sales)}</p>
                      </div>
                    </div>
                  </div>
                  
                  <div className="bg-[#FFF8E5] p-4 rounded-lg">
                    <div className="flex items-center">
                      <div className="h-10 w-10 bg-[#FDCA40] rounded-full flex items-center justify-center text-white">
                        <ArrowDown size={20} />
                      </div>
                      <div className="ml-3">
                        <p className="text-sm font-medium text-gray-600">Egresos</p>
                        <p className="text-xl font-bold text-gray-800">{formatCurrency(cashRegister.expenses)}</p>
                      </div>
                    </div>
                  </div>
                </div>
                    )}
                
                <div className="bg-gray-50 p-4 rounded-lg mb-6">
                  <h3 className="text-sm font-semibold text-gray-600 mb-3">Información de Apertura</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="flex items-center">
                      <Clock size={18} className="text-gray-500 mr-2" />
                      <div>
                        <p className="text-xs text-gray-500">Fecha y Hora</p>
                        <p className="text-sm font-medium text-gray-800"> {formatDate(new Date(cashRegister.openedAt))} - {formatTime(new Date(cashRegister.openedAt))}</p>
                      </div>
                    </div>
                    
                    <div className="flex items-center">
                      <User size={18} className="text-gray-500 mr-2" />
                      <div>
                        <p className="text-xs text-gray-500">Abierta por</p>
                        <p className="text-sm font-medium text-gray-800">{cashRegister.openedBy}</p>
                      </div>
                    </div>
                    
                    <div className="flex items-center">
                      <DollarSign size={18} className="text-gray-500 mr-2" />
                      <div>
                        <p className="text-xs text-gray-500">Monto Inicial</p>
                        <p className="text-sm font-medium text-gray-800">{formatCurrency(cashRegister.initialAmount)}</p>
                      </div>
                    </div>
                  </div>
                </div>
                {/* Solo Admin ve desglose de ventas */}
                {isAdmin && (
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <h3 className="text-sm font-semibold text-gray-600">Desglose de Ventas</h3>
                  </div>
                  <div className="p-4">
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <div className="flex items-center">
                          <DollarSign size={18} className="text-green-500 mr-2" />
                          <span className="text-sm text-gray-700">Efectivo</span>
                        </div>
                        <span className="text-sm font-medium text-gray-800"> {formatCurrency(cashRegister.salesBreakdown.efectivo)} </span>
                      </div>

                      <div className="flex justify-between items-center">
                      <div className="flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-purple-500 mr-2">
                            <path d="M22 7h-5a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h5" />
                            <path d="M9 11h5a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H9" />
                            <path d="M7 9h1c1 0 1-1 1-1V7c0-1-1-1-2-1H2" />
                            <path d="M2 7v10c0 1 1 2 2 2h14" />
                          </svg>
                          <span className="text-sm text-gray-700">Transferencia</span>
                        </div>
                        <span className="text-sm font-medium text-gray-800">{formatCurrency(cashRegister.salesBreakdown.transferencia)}</span>
                      </div>
                      
                      <div className="pt-2 mt-2 border-t border-gray-200 flex justify-between items-center">
                        <span className="text-sm font-medium text-gray-700">Total de Ventas ({cashRegister.salesCount})</span>
                        <span className="text-sm font-bold text-gray-800">{formatCurrency(cashRegister.sales)}</span>
                      </div>
                    </div>
                  </div>
                </div>
                )}
              </div>
            </div>
          </div>
          
          <div className="md:col-span-1">
            <div className="bg-cyan-700 rounded-xl shadow-md overflow-hidden h-fit">
              <div className="px-5 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-white">Acciones</h2>
              </div>
              
              <div className="p-5">
                <div className="space-y-4">
                  <button
                    onClick={() => setShowCloseModal(true)}
                    className="w-full flex items-center justify-center px-4 py-3 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C] transition-colors"
                  >
                    <Clock size={18} className="mr-2" />
                    Cerrar Caja
                  </button>
                  
                  <div className="bg-blue-50 p-4 rounded-lg">
                    <div className="flex items-start">
                      <AlertCircle size={18} className="text-blue-500 mr-2 mt-0.5 flex-shrink-0" />
                      <div>
                        <h4 className="text-sm font-medium text-blue-800">Recordatorio</h4>
                        <p className="text-xs text-blue-700 mt-1">
                          Recuerde verificar el dinero en efectivo antes de cerrar la caja. Todos los movimientos quedarán registrados en el sistema.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-white rounded-xl shadow-md overflow-hidden">
          <div className="p-8 flex flex-col items-center justify-center">
            <div className="h-20 w-20 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 mb-4">
              <DollarSign size={40} />
            </div>
            <h2 className="text-xl font-bold text-gray-800 mb-2">Caja Cerrada</h2>
            <p className="text-gray-600 text-center mb-6 max-w-md">
              Para comenzar a registrar ventas y movimientos, primero debe abrir la caja con un saldo inicial.
            </p>
            <button
              onClick={() => setShowOpenModal(true)}
              className="px-6 py-3 bg-[#FF6B35] text-white font-medium rounded-lg hover:bg-[#D6492C] transition-colors"
            >
              Abrir Caja
            </button>
          </div>
        </div>
      )}
      
      {/* Open Cash Register Modal */}
      {showOpenModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                      Abrir Caja
                    </h3>
                    
                    <div className="mt-2">
                      <div className="mb-4">
                        <label htmlFor="openAmount" className="block text-sm font-medium text-gray-700 mb-1">
                          Monto Inicial (ARS)
                        </label>
                        <input
                          type="number"
                          id="openAmount"
                          className="form-input w-full"
                          value={openAmount}
                          onChange={(e) => setOpenAmount(e.target.value)}
                          min="0"
                          step="100"
                        />
                      </div>
                      
                      <div className="bg-blue-50 p-3 rounded-md">
                        <div className="flex">
                          <AlertCircle size={16} className="text-blue-500 mr-2 mt-0.5 flex-shrink-0" />
                          <div>
                            <p className="text-xs text-blue-700">
                              El monto inicial debe coincidir con el dinero en efectivo disponible en la caja.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleOpenCashRegister}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#FF6B35] text-base font-medium text-white hover:bg-[#D6492C] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Abrir Caja
                </button>
                <button
                  type="button"
                  onClick={() => setShowOpenModal(false)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      
      {/* Close Cash Register Modal */}
      {showCloseModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                      Cerrar Caja
                    </h3>
                    
                    <div className="mt-2">
                      <div className="mb-4">
                        <p className="text-sm text-gray-700 mb-4">
                          Está a punto de cerrar la caja. El saldo final es:
                        </p>
                        
                        <div className="bg-[#FFEDE5] p-4 rounded-lg mb-4">
                          <p className="text-sm text-gray-700">Saldo Final</p>
                          <p className="text-2xl font-bold text-[#FF6B35]">{formatCurrency(cashRegister!.currentAmount)}</p>
                        </div>
                        
                        <div className="bg-gray-50 p-4 rounded-lg mb-4">
                          <div className="flex justify-between items-center mb-2">
                            <span className="text-sm text-gray-600">Saldo Inicial</span>
                            <span className="text-sm font-medium text-gray-800">{formatCurrency(cashRegister!.initialAmount)}</span>
                          </div>
                          <div className="flex justify-between items-center mb-2">
                            <span className="text-sm text-gray-600">+ Ventas</span>
                            <span className="text-sm font-medium text-green-600">{formatCurrency(cashRegister!.sales)}</span>
                          </div>
                          <div className="flex justify-between items-center">
                            <span className="text-sm text-gray-600">- Egresos</span>
                            <span className="text-sm font-medium text-red-600">{formatCurrency(cashRegister!.expenses)}</span>
                          </div>
                        </div>
                      </div>
                      
                      <div className="mb-4">
                        <label htmlFor="closeNote" className="block text-sm font-medium text-gray-700 mb-1">
                          Notas (opcional)
                        </label>
                        <textarea
                          id="closeNote"
                          className="form-input w-full"
                          value={closeNote}
                          onChange={(e) => setCloseNote(e.target.value)}
                          rows={3}
                          placeholder="Agregue notas o comentarios sobre el cierre de caja"
                        ></textarea>
                      </div>
                      
                      <div className="bg-yellow-50 p-3 rounded-md">
                        <div className="flex">
                          <AlertCircle size={16} className="text-yellow-500 mr-2 mt-0.5 flex-shrink-0" />
                          <div>
                            <p className="text-xs text-yellow-700">
                              Una vez cerrada la caja, no podrá realizar más operaciones hasta que se abra nuevamente.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleCloseCashRegister}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#FF6B35] text-base font-medium text-white hover:bg-[#D6492C] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Confirmar Cierre
                </button>
                <button
                  type="button"
                  onClick={() => setShowCloseModal(false)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      
      {/* Cash Movement Modal */}
      {showMovementModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                      {movementType === 'in' ? 'Registrar Ingreso' : 'Registrar Egreso'}
                    </h3>
                    
                    <div className="mt-2">
                      <div className="mb-4">
                        <label htmlFor="movementAmount" className="block text-sm font-medium text-gray-700 mb-1">
                          Monto (ARS)
                        </label>
                        <input
                          type="number"
                          id="movementAmount"
                          className="form-input w-full text-gray-700"
                          value={movementAmount}
                          onChange={(e) => setMovementAmount(e.target.value)}
                          min="0"
                          step="100"
                        />
                      </div>
                      
                      <div className="mb-4">
                        <label htmlFor="movementReason" className="block text-sm font-medium text-gray-700 mb-1">
                          Motivo
                        </label>
                        <input
                          type="text"
                          id="movementReason"
                          className="form-input w-full text-gray-700"
                          value={movementReason}
                          onChange={(e) => setMovementReason(e.target.value)}
                          placeholder={
                            movementType === 'in'
                              ? 'Ej: Devolución, Adelanto, etc.'
                              : 'Ej: Compra insumos, Pago proveedor, etc.'
                          }
                        />
                      </div>
                      
                      <div className={`p-3 rounded-md ${
                        movementType === 'in' ? 'bg-green-50' : 'bg-red-50'
                      }`}>
                        <div className="flex">
                          {movementType === 'in' ? (
                            <CheckCircle size={16} className="text-green-500 mr-2 mt-0.5 flex-shrink-0" />
                          ) : (
                            <AlertCircle size={16} className="text-red-500 mr-2 mt-0.5 flex-shrink-0" />
                          )}
                          <div>
                            <p className={`text-xs ${
                              movementType === 'in' ? 'text-green-700' : 'text-red-700'
                            }`}>
                              {movementType === 'in'
                                ? 'Este monto se sumará al saldo actual de caja.'
                                : 'Este monto se restará del saldo actual de caja.'}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleCashMovement}
                  className={`w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 ${
                    movementType === 'in'
                      ? 'bg-[#2EC4B6] hover:bg-[#23A399]'
                      : 'bg-[#FF6B35] hover:bg-[#D6492C]'
                  } text-white font-medium focus:outline-none sm:ml-3 sm:w-auto sm:text-sm`}
                >
                  Confirmar
                </button>
                <button
                  type="button"
                  onClick={() => setShowMovementModal(false)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CashRegisterPage;
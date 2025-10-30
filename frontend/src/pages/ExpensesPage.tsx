import React, { useEffect, useState } from 'react';
import { 
  FileText, Calendar, Filter, ChevronDown,
  Plus, Search, Trash2, Edit
} from 'lucide-react';
import EmptyState from '../components/common/EmptyState';
import { fetchExpenses, addExpense, updateExpense, deleteExpense, fetchExpenseCategories } from '../contexts/api';
import { Expense, Category } from '../types';


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

const ExpensesPage: React.FC = () => {
  const [expenses, setExpenses] = useState<Expense[]>([]);
  const [showExpenseModal, setShowExpenseModal] = useState(false);
  const [editingExpense, setEditingExpense] = useState<Expense | null>(null);
  
  const [amount, setAmount] = useState('');
  const [category, setCategory] = useState<number>(0);
  const [expenseCategories, setExpenseCategories] = useState<Category[]>([]);
  const [description, setDescription] = useState('');
  const [date, setDate] = useState(formatDate(new Date()).split('/').reverse().join('-'));

  const [filterCategory, setFilterCategory] = useState<number | 'all'>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => {
    const loadCategories = async () => {
      const categories = await fetchExpenseCategories();
      const activeCategories = categories.filter(c => c.activo === 1);
      setExpenseCategories(activeCategories);
      if (activeCategories.length > 0) {
        setCategory(activeCategories[0].id);
      }
    };
    loadCategories();
  }, []);

  useEffect(() => {
    const loadExpenses = async () => {
      const data = await fetchExpenses();
      setExpenses(data);
    };
    loadExpenses();
  }, []);

  const categoryColors: Record<number, string> = {
    1: 'bg-red-100 text-red-800',
    2: 'bg-blue-100 text-blue-800',
    3: 'bg-green-100 text-green-800',
    4: 'bg-yellow-100 text-yellow-800',
    5: 'bg-gray-100 text-gray-800',
  };

  // Function to add a new expense
  const handleAddExpense = async () => {
    const payload = {
      monto: parseFloat(amount),
      categoria_id: category,
      descripcion: description,
    };

    if (editingExpense) {
      await updateExpense(editingExpense.id, payload);
      setExpenses(expenses.map(e =>
          e.id === editingExpense.id ? { ...e, ...payload, date: new Date(date) } : e
      ));
    } else {
      await addExpense(payload);
      const updated = await fetchExpenses();
      setExpenses(updated);
    }

    resetForm();
    setShowExpenseModal(false);
  };
  
  // Function to delete an expense
  const handleDeleteExpense = async (id: number) => {
    if (confirm('¿Está seguro de eliminar este gasto?')) {
      await deleteExpense(id);
      setExpenses(expenses.filter(e => e.id !== id));
    }
  };
  
  // Function to edit an expense
  const handleEditExpense = (expense: Expense) => {
    setEditingExpense(expense);
    setAmount(expense.monto.toString());
    setCategory(expense.categoria_id);
    setDescription(expense.descripcion);
    setDate(formatDateForInput(new Date(expense.fecha)));
    setShowExpenseModal(true);
  };
  
  // Reset form
  const resetForm = () => {
    setAmount('');
    setCategory(expenseCategories.length > 0 ? expenseCategories[0].id : 0);
    setDescription('');
    setDate(formatDate(new Date()).split('/').reverse().join('-'));
    setEditingExpense(null);
  };
  
  // Format date for input
  const formatDateForInput = (date: Date): string => {
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${year}-${month}-${day}`;
  };
  
  // Get category name by id
  const getCategoryName = (categoryId: number): string => {
    const category = expenseCategories.find((cat) => cat.id === categoryId);
    return category ? category.nombre : 'Desconocido';
  };


  // Get category color by id
  const getCategoryColor = (categoryId: number): string => {
    return categoryColors[categoryId] || 'bg-gray-100 text-gray-800';
  };
  
  // Calculate total expenses
  const totalExpenses = expenses.reduce((total, expense) => total + expense.monto, 0);


  // Calculate total expenses by category
  const expensesByCategory = expenseCategories.map((cat) => {
    const total = expenses
      .filter((expense) => expense.categoria_id === cat.id)
      .reduce((sum, expense) => sum + expense.monto, 0);
    
    return {
      ...cat,
      total,
    };
  });

  // Filter expenses
  const filteredExpenses = expenses.filter((expense) => {
    const matchesCategory = filterCategory === 'all' || expense.categoria_id === filterCategory;
    const matchesSearch =
        expense.descripcion.toLowerCase().includes(searchTerm.toLowerCase()) ||
        getCategoryName(expense.categoria_id).toLowerCase().includes(searchTerm.toLowerCase());

    return matchesCategory && matchesSearch;
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Registro de Egresos</h1>
          <p className="mt-1 text-gray-600">
            Total gastos: <span className="font-medium">{formatCurrency(totalExpenses)}</span>
          </p>
        </div>
        <div className="mt-4 md:mt-0">
          <button
            onClick={() => {
              resetForm();
              setShowExpenseModal(true);
            }}
            className="flex items-center px-4 py-2 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
          >
            <Plus size={18} className="mr-2" />
            Nuevo Egreso
          </button>
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="md:col-span-2">
          <div className="bg-white rounded-xl shadow-md overflow-hidden">
            <div className="p-5 border-b border-gray-200">
              <div className="flex flex-col sm:flex-row justify-between space-y-4 sm:space-y-0">
                <div className="relative max-w-md">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search size={18} className="text-gray-400" />
                  </div>
                  <input
                    type="text"
                    className="pl-10 form-input w-full max-w-xs"
                    placeholder="Buscar por descripción o categoría"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                  />
                </div>
                <div className="flex space-x-2">
                  <div className="relative">
                    <button
                      onClick={() => setShowFilters(!showFilters)}
                      className="flex items-center space-x-1 bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                      <Filter size={16} />
                      <span>Filtrar</span>
                      <ChevronDown size={16} />
                    </button>
                    {showFilters && (
                      <div className="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10">
                        <div className="py-1">
                          <button
                            onClick={() => {
                              setFilterCategory('all');
                              setShowFilters(false);
                            }}
                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                          >
                            Todas las categorías
                          </button>
                          {expenseCategories.map((cat) => (
                            <button
                              key={cat.id}
                              onClick={() => {
                                setFilterCategory(cat.id);
                                setShowFilters(false);
                              }}
                              className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                            >
                              {cat.nombre}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
            
            <div className="overflow-x-auto">
              {filteredExpenses.length > 0 ? (
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fecha
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descripción
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categoría
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Monto
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {filteredExpenses.map((expense) => (
                      <tr key={expense.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <Calendar size={16} className="text-gray-400 mr-2" />
                            <div className="text-sm text-gray-900">{formatDate(new Date(expense.fecha))}</div>
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <div className="text-sm text-gray-900">{expense.descripcion}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(expense.categoria_id)}`}>
                            {getCategoryName(expense.categoria_id)}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm font-medium text-red-600">{formatCurrency(expense.monto)}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <div className="flex space-x-3">
                            <button
                              onClick={() => handleEditExpense(expense)}
                              className="text-indigo-600 hover:text-indigo-900"
                            >
                              <Edit size={16} />
                            </button>
                            <button
                              onClick={() => handleDeleteExpense(expense.id)}
                              className="text-red-600 hover:text-red-900"
                            >
                              <Trash2 size={16} />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <EmptyState
                  title="No hay egresos registrados"
                  description="Agregue nuevos egresos para visualizarlos aquí."
                  icon={<FileText size={48} className="text-gray-400" />}
                  action={{
                    label: "Agregar Egreso",
                    onClick: () => {
                      resetForm();
                      setShowExpenseModal(true);
                    }
                  }}
                />
              )}
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-xl shadow-md overflow-hidden h-fit">
          <div className="px-5 py-4 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-800">Resumen por Categoría</h2>
          </div>
          
          <div className="p-5">
            <div className="space-y-4">
              {expensesByCategory.map((category) => (
                <div key={category.id} className="flex justify-between items-center">
                  <div className="flex items-center">
                    <span className={`inline-flex items-center justify-center h-8 w-8 rounded-full ${categoryColors[category.id] || 'bg-gray-100 text-gray-800'}`}>
                      {category.nombre.charAt(0)}
                    </span>
                    <span className="ml-2 text-sm text-gray-700">{category.nombre}</span>
                  </div>
                  <span className="text-sm font-medium text-gray-900">{formatCurrency(category.total)}</span>
                </div>
              ))}
              
              <div className="pt-4 mt-2 border-t border-gray-200">
                <div className="flex justify-between items-center">
                  <span className="text-base font-semibold text-gray-800">Total </span>
                  <span className="text-base font-bold text-red-600">{formatCurrency(totalExpenses)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {/* Expense Form Modal */}
      {showExpenseModal && (
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
                      {editingExpense ? 'Editar Egreso' : 'Registrar Nuevo Egreso'}
                    </h3>
                    
                    <div className="mt-2 space-y-4">
                      <div>
                        <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-1">
                          Monto (ARS)
                        </label>
                        <div className="relative rounded-md shadow-sm">
                          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span className="text-gray-500 sm:text-sm">$</span>
                          </div>
                          <input
                            type="number"
                            id="amount"
                            className="form-input block w-full pl-10"
                            placeholder="0.00"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            min="0"
                            step="0.01"
                          />
                        </div>
                      </div>

                      <div>
                        <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-1">
                          Categoría
                        </label>
                        <select
                            id="category"
                            className="form-input block w-full"
                            value={category}
                            onChange={(e) => setCategory(Number(e.target.value))}
                        >
                          {expenseCategories.map((cat) => (
                              <option key={cat.id} value={cat.id}>
                                {cat.nombre}
                              </option>
                          ))}
                        </select>

                      </div>

                      <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                          Descripción
                        </label>
                        <textarea
                          id="description"
                          className="form-input block w-full"
                          rows={3}
                          placeholder="Ingrese una descripción del gasto"
                          value={description}
                          onChange={(e) => setDescription(e.target.value)}
                        ></textarea>
                      </div>
                      {/*<div>*/}
                      {/*  <label htmlFor="date" className="block text-sm font-medium text-gray-700 mb-1">*/}
                      {/*    Fecha*/}
                      {/*  </label>*/}
                      {/*  <input*/}
                      {/*    type="date"*/}
                      {/*    id="date"*/}
                      {/*    className="form-input block w-full"*/}
                      {/*    value={date}*/}
                      {/*    onChange={(e) => setDate(e.target.value)}*/}
                      {/*  />*/}
                      {/*</div>*/}
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleAddExpense}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#FF6B35] text-base font-medium text-white hover:bg-[#D6492C] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                >
                  {editingExpense ? 'Guardar Cambios' : 'Registrar Egreso'}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    resetForm();
                    setShowExpenseModal(false);
                  }}
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

export default ExpensesPage;
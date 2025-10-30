import React, { useEffect, useState } from 'react';
import {
  Plus, Search, Package, Edit, Trash2, Check, X
} from 'lucide-react';
import { Product } from '../types';
import useAuth from '../hooks/useAuth';
import { products as productsApi, combos } from "../contexts/api.ts";
import {useToast} from "../components/common/SimpleToast.tsx";


// Utility function to format currency
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(amount);
};

const getCategoryData = (id: number) => {
  switch (id) {
    case 1:
      return { name: 'Comidas', style: 'bg-orange-100 text-orange-800', key: 'food' };
    case 2:
      return { name: 'Bebidas', style: 'bg-blue-100 text-blue-800', key: 'drinks' };
    case 3:
      return { name: 'Combos', style: 'bg-purple-100 text-purple-800', key: 'combos' };
    default:
      return { name: 'Desconocido', style: 'bg-gray-100 text-gray-800', key: 'unknown' };
  }
};

const ProductsPage: React.FC = () => {
  const { user } = useAuth();
  const isAdmin = user?.roles === 'ROLE_ADMIN';
    const { showToast } = useToast();

  const [products, setProducts] = useState<Product[]>([]);

  const [showProductModal, setShowProductModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);

  const [productName, setProductName] = useState('');
  const [productPrice, setProductPrice] = useState('');
  const [productCategory, setProductCategory] = useState<'food' | 'drinks' | 'combos'>('food');
  const [productAvailable, setProductAvailable] = useState(true);
  const [productStock, setStockProduct] = useState('');

  const [filterCategory, setFilterCategory] = useState<'all' | 'food' | 'drinks' | 'combos'>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [showOnlyAvailable, setShowOnlyAvailable] = useState(true);
  const [comboComponentes, setComboComponentes] = useState<number[]>([]);

  const [showStockModal, setShowStockModal] = useState(false);
  const [selectedProductId, setSelectedProductId] = useState<number | null>(null);
  const [stockMovementType, setStockMovementType] = useState<1 | 2>(1);
  const [stockMovementQty, setStockMovementQty] = useState('');


  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const { data } = await productsApi.getAll();
        setProducts(data);
      } catch (error) {
        console.error('Error al cargar productos:', error);
        showToast('Error al cargar productos', 'error');
      }
    };

    fetchProducts();
  }, []);

  // Function to add/edit a product
  const handleSaveProduct = async () => {
    const priceValue = parseFloat(productPrice);
    const stockValue = parseInt(productStock, 10);

    if (!productName.trim()) {
      showToast('Ingrese un nombre para el producto', 'warning');
      return;
    }

    if (isNaN(stockValue) || stockValue < 0) {
      showToast('Ingrese una cantidad de stock válida', 'warning');
      return;
    }

    if (isNaN(priceValue) || priceValue <= 0) {
        showToast('Ingrese un precio válido', 'warning');
      return;
    }
    const categoryMap: Record<string, number> = {
      food: 1,
      drinks: 2,
      combos: 3
    };

    const productoPayload = {
      id: editingProduct ? editingProduct.id : 0,
      nombre: productName.trim(),
      precio: priceValue,
      categoria_prod_id: categoryMap[productCategory], // o directamente productCategory si ya es number
      stock_actual: stockValue, // si no estás cargando stock, poné 0 u otro valor por defecto
      activo: productAvailable ? 1 : 0,
    };

    try {
      if (editingProduct) {
        await productsApi.update(Number(editingProduct.id), productoPayload);
      } else {
        if (productCategory === 'combos') {
          if (comboComponentes.length === 0) {
            showToast('Debe seleccionar al menos un producto para el combo.', 'warning');
            return;
          }
          await combos.create({
            ...productoPayload,
            componentes: comboComponentes.map((producto_id) => ({
              producto_id,
              cantidad: 1,
            })),
          });
        } else {
          await productsApi.create(productoPayload);
        }
      }

      const { data } = await productsApi.getAll();
      setProducts(data);

      resetForm();
      setShowProductModal(false);
    } catch (error) {
      console.error('Error al guardar producto:', error);
      showToast('Ocurrió un error al guardar el producto', 'error');
    }
  };

  // Function to toggle product availability
  const toggleProductAvailability = async (id: number) => {
    const product = products.find(p => p.id === id);
    if (!product) return;

    try {
      await productsApi.update(product.id, {
          id: product.id,
          nombre: product.nombre,
          precio: product.precio,
          categoria_prod_id: product.categoria_prod_id,
          stock_actual: product.stock_actual,
        activo: product.activo ? 0 : 1,
      });
      const updatedProducts = await productsApi.getAll();
      setProducts(updatedProducts.data);
    } catch (error) {
      console.error('Error al cambiar disponibilidad:', error);
      showToast('No se pudo actualizar la disponibilidad', 'error');
    }
  };

  // Function to delete a product
  const handleDeleteProduct = async (id: number) => {
    if (!confirm('¿Está seguro de desactivar este producto?')) return;

    try {
      await productsApi.disable(id); // usa disable para deshabilitar

      const { data } = await productsApi.getAll();
      setProducts(data);
    } catch (error) {
      console.error('Error al desactivar producto:', error);
      showToast('No se pudo desactivar el producto', 'error');
    }
  };

  // Función para actualizar Stock
  const handleStockUpdate = async () => {
    if (!selectedProductId) {
      showToast('Debe seleccionar un producto', 'warning');
      return;
    }

    const cantidad = parseInt(stockMovementQty, 10);
    if (isNaN(cantidad) || cantidad <= 0) {
      showToast('Ingrese una cantidad válida', 'warning');
      return;
    }

    try {
      await productsApi.updateStock(selectedProductId, stockMovementType, cantidad);
      showToast('Stock actualizado correctamente', 'success');
      const { data } = await productsApi.getAll();
      setProducts(data);
      setShowStockModal(false);
      setSelectedProductId(null);
      setStockMovementQty('');
    } catch (error) {
      console.error('Error al actualizar stock:', error);
      showToast('No se pudo actualizar el stock', 'error');
    }
  };

  // Function to edit a product
  const handleEditProduct = (product: Product) => {
    setEditingProduct(product);
    setProductName(product.nombre);
    setProductPrice(product.precio.toString());
    const categoryData = getCategoryData(product.categoria_prod_id);
    setProductCategory(categoryData.key as 'food' | 'drinks' | 'combos');
    setProductAvailable(Boolean(product.activo));
    setShowProductModal(true);
  };

  // Reset form
  const resetForm = () => {
    setProductName('');
    setProductPrice('');
    setStockProduct('');
    setProductCategory('food');
    setProductAvailable(true);
    setEditingProduct(null);
  };

  // Filter products
  const filteredProducts = products.filter((product) => {
    const categoryKey = getCategoryData(product.categoria_prod_id).key;
    const matchesCategory = filterCategory === 'all' || categoryKey === filterCategory;
    const matchesSearch = product.nombre.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesAvailability = showOnlyAvailable ? product.activo === 1 : true;
    return matchesCategory && matchesSearch && matchesAvailability;
  });

  // Count products by category
  const productCounts = {
    all: products.length,
    food: products.filter((p) => getCategoryData(p.categoria_prod_id).key === 'food').length,
    drinks: products.filter((p) => getCategoryData(p.categoria_prod_id).key === 'drinks').length,
    combos: products.filter((p) => getCategoryData(p.categoria_prod_id).key === 'combos').length,
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Gestión de Productos</h1>
          <p className="mt-1 text-gray-600">
            Total: <span className="font-medium">{products.length} productos</span>
          </p>
        </div>
        {/* Solo Admin puede agregar o actualizar stock*/}
        {isAdmin && (
          <div className="flex gap-3 mt-4 md:mt-0">
            <button
                onClick={() => {
                  resetForm();
                  setShowProductModal(true);
                }}
                className="flex items-center px-4 py-2 bg-[#FF6B35] text-white rounded-lg hover:bg-[#D6492C]"
            >
              <Plus size={18} className="mr-2"/>
              Nuevo Producto
            </button>

            <button
                onClick={() => setShowStockModal(true)}
                className="flex items-center px-4 py-2 bg-[#2EC4B6] text-white rounded-lg hover:bg-[#1BB3A1]"
            >
              <Package size={18} className="mr-2"/>
              Actualizar Stock
            </button>
          </div>
        )}
      </div>

      <div className="bg-white rounded-xl shadow-md overflow-hidden">
        <div className="p-5 border-b border-gray-200">
          <div className="flex flex-col md:flex-row justify-between md:items-center space-y-4 md:space-y-0">
            <div className="relative max-w-md">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search size={18} className="text-gray-400"/>
              </div>
              <input
                  type="text"
                  className="pl-10 form-input w-full max-w-xs"
                placeholder="Buscar productos"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>

            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => setFilterCategory('all')}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  filterCategory === 'all'
                    ? 'bg-[#FF6B35] text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Todos ({productCounts.all})
              </button>
              <button
                onClick={() => setFilterCategory('food')}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  filterCategory === 'food'
                    ? 'bg-[#FF6B35] text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Comidas ({productCounts.food})
              </button>
              <button
                onClick={() => setFilterCategory('drinks')}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  filterCategory === 'drinks'
                    ? 'bg-[#FF6B35] text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Bebidas ({productCounts.drinks})
              </button>
              <button
                onClick={() => setFilterCategory('combos')}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium ${
                  filterCategory === 'combos'
                    ? 'bg-[#FF6B35] text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Combos ({productCounts.combos})
              </button>

              <label className="flex items-center ml-4 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  className="h-4 w-4 text-[#2EC4B6] rounded border-gray-300 focus:ring-[#2EC4B6]"
                  checked={showOnlyAvailable}
                  onChange={() => setShowOnlyAvailable(!showOnlyAvailable)}
                />
                <span className="ml-2">Solo disponibles</span>
              </label>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
            <tr>
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Producto
              </th>
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cantidad
              </th>
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Categoría
              </th>
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Precio
              </th>
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              {/* Solo Admin ve las acciones*/}
              {isAdmin && (
              <th scope="col"
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
                  )}
            </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
            {filteredProducts.map((product) => (
                <tr key={product.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="flex items-center">
                      <div
                          className="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-lg flex items-center justify-center">
                        <Package size={18} className="text-gray-500"/>
                      </div>
                      <div className="ml-4">
                        <div className="flex items-center space-x-2">
                          <span className="text-sm font-medium text-gray-900">{product.nombre}</span>
                          {product.activo === 0 && (
                              <span className="ml-2 px-2 py-0.5 rounded text-xs font-medium text-red-700 bg-red-100">
                                  Inactivo
                                </span>
                          )}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{product.stock_actual}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        getCategoryData(product.categoria_prod_id).style
                    }`}>
                      {getCategoryData(product.categoria_prod_id).name}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">{formatCurrency(product.precio)}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <button
                        onClick={() => toggleProductAvailability(product.id)}
                        className={`inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium ${
                            product.activo
                                ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                : 'bg-red-100 text-red-800 hover:bg-red-200'
                        }`}
                    >
                      {product.activo ? (
                          <>
                            <Check size={12} className="mr-1"/>
                            Disponible
                          </>
                      ) : (
                          <>
                            <X size={12} className="mr-1"/>
                            No Disponible
                          </>
                      )}
                    </button>
                  </td>
                  {/* Solo Admin ve las acciones*/}
                  {isAdmin && (
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex space-x-3">
                        <button
                            onClick={() => handleEditProduct(product)}
                            className="text-indigo-600 hover:text-indigo-900"
                        >
                          <Edit size={16}/>
                        </button>
                        <button
                            onClick={() => handleDeleteProduct(product.id)}
                            className="text-red-600 hover:text-red-900"
                        >
                          <Trash2 size={16}/>
                        </button>
                      </div>
                    </td>
                  )}
                </tr>
            ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Product Form Modal */}
      {showProductModal && (
          <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
              {/* Fondo negro difuminado */}
              <div className="fixed inset-0 bg-black bg-opacity-30 backdrop-blur-sm" aria-hidden="true"></div>

              <div
                  className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-10">
                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <div className="sm:flex sm:items-start">
                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                      <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                        {editingProduct ? 'Editar Producto' : 'Nuevo Producto'}
                      </h3>

                      <div className="mt-2 space-y-4">
                        <div>
                          <label htmlFor="productName" className="block text-sm font-medium text-gray-700 mb-1">
                            Nombre del Producto
                          </label>
                          <input
                              type="text"
                              id="productName"
                              className="form-input block w-full"
                              value={productName}
                              onChange={(e) => setProductName(e.target.value)}
                              placeholder="Ej: Hamburguesa Completa"
                          />
                        </div>

                        <div>
                          <label htmlFor="productPrice" className="block text-sm font-medium text-gray-700 mb-1">
                            Precio (ARS)
                          </label>
                          <div className="relative rounded-md shadow-sm">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                              <span className="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input
                                type="number"
                                id="productPrice"
                                className="form-input block w-full pl-10"
                                placeholder="0.00"
                                value={productPrice}
                                onChange={(e) => setProductPrice(e.target.value)}
                                min="0"
                                step="0.01"
                            />
                          </div>
                        </div>

                        <div>
                          <label htmlFor="productCategory" className="block text-sm font-medium text-gray-700 mb-1">
                            Categoría
                          </label>
                          <select
                              id="productCategory"
                              className="form-input block w-full"
                              value={productCategory}
                              onChange={(e) => setProductCategory(e.target.value as 'food' | 'drinks' | 'combos')}
                          >
                            <option value="food">Comidas</option>
                            <option value="drinks">Bebidas</option>
                            <option value="combos">Combos</option>
                          </select>
                        </div>
                        {/* ✅ Aquí insertás lo nuevo */}
                        {productCategory === 'combos' && (
                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Productos del Combo
                              </label>
                              <div className="border rounded p-2 space-y-1 max-h-60 overflow-y-auto">
                                {products
                                    .filter((p) => p.categoria_prod_id !== 3 && p.activo === 1)
                                    .map((p) => (
                                        <label key={p.id} className="flex items-center space-x-2">
                                          <input
                                              type="checkbox"
                                              value={p.id}
                                              checked={comboComponentes.includes(p.id)}
                                              onChange={(e) => {
                                                const value = Number(e.target.value);
                                                if (e.target.checked) {
                                                  setComboComponentes([...comboComponentes, value]);
                                                } else {
                                                  setComboComponentes(comboComponentes.filter((id) => id !== value));
                                                }
                                              }}
                                          />
                                          <span>{p.nombre}</span>
                                        </label>
                                    ))}
                              </div>
                            </div>
                        )}

                        <div>
                          <label htmlFor="productName" className="block text-sm font-medium text-gray-700 mb-1">
                            Cantidad en Stock
                          </label>
                          <input
                              type="text"
                              id="stock_actual"
                              className="form-input block w-full"
                              value={productStock}
                              onChange={(e) => setStockProduct(e.target.value)}
                              min="0"
                              placeholder="Debe ser mayor a 0"
                          />
                        </div>

                        <div className="flex items-center">
                          <input
                              id="productAvailable"
                              type="checkbox"
                              className="h-4 w-4 text-[#2EC4B6] rounded border-gray-300 focus:ring-[#2EC4B6]"
                              checked={productAvailable}
                              onChange={(e) => setProductAvailable(e.target.checked)}
                          />
                          <label htmlFor="productAvailable" className="ml-2 block text-sm text-gray-700">
                            Disponible para la venta
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button
                      type="button"
                      onClick={handleSaveProduct}
                      className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#FF6B35] text-base font-medium text-white hover:bg-[#D6492C] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                  >
                    {editingProduct ? 'Guardar Cambios' : 'Crear Producto'}
                  </button>
                  <button
                      type="button"
                      onClick={() => {
                        resetForm();
                        setShowProductModal(false);
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
      {showStockModal && (
          <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 py-8">
              <div className="fixed inset-0 bg-black bg-opacity-30 backdrop-blur-sm"></div>
              <div className="bg-white rounded-lg p-6 z-10 w-full max-w-md">
                <h2 className="text-lg font-semibold text-gray-800 mb-4">Actualizar Stock</h2>

                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                  <select
                      className="form-select w-full"
                      value={selectedProductId ?? ''}
                      onChange={(e) => setSelectedProductId(Number(e.target.value))}
                  >
                    <option value="">Seleccione un producto</option>
                    {products.map((p) => (
                        <option key={p.id} value={p.id}>
                          {p.nombre}
                        </option>
                    ))}
                  </select>
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento</label>
                  <div className="flex gap-3">
                    <button
                        onClick={() => setStockMovementType(1)}
                        className={`px-4 py-2 rounded ${stockMovementType === 1 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-800'}`}
                    >
                      Ingreso
                    </button>
                    <button
                        onClick={() => setStockMovementType(2)}
                        className={`px-4 py-2 rounded ${stockMovementType === 2 ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-800'}`}
                    >
                      Egreso
                    </button>
                  </div>
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                  <input
                      type="number"
                      className="form-input w-full"
                      value={stockMovementQty}
                      onChange={(e) => setStockMovementQty(e.target.value)}
                      min="1"
                  />
                </div>

                <div className="flex justify-end gap-2">
                  <button
                      onClick={() => setShowStockModal(false)}
                      className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  >
                    Cancelar
                  </button>
                  <button
                      onClick={handleStockUpdate}
                      className="px-4 py-2 bg-[#FF6B35] text-white rounded hover:bg-[#D6492C]"
                  >
                    Confirmar
                  </button>
                </div>
              </div>
            </div>
          </div>
      )}

    </div>
  );
};

export default ProductsPage;
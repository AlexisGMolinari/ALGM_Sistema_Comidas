"use client"

import React, { useState, useEffect } from 'react';
import {
    Plus, Search, ShoppingCart, Check, Clock, X, Filter, ChevronDown, DollarSign, Smartphone, Printer,// Printer
} from 'lucide-react';
import {
    fetchPedidos, fetchPedidoById, updatePedido, fetchProductosByCategoria, createPedido, uploadComprobanteImage,
    cancelPedido, completeOrder, combos, getCajaActual, configuracion
} from "../contexts/api.ts";
import { Order, Product } from '../types';
import { Edit } from 'lucide-react';
import useAuth from "../hooks/useAuth.ts";
import { useToast } from '../components/common/SimpleToast';
import { getApiErrorMessage } from "../utils/apiErrors";
import {Link} from "react-router-dom";
import {ConfirmModal} from "../components/common/ConfirmModal.tsx";



// Order Type
interface OrderItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}
interface SplitOrderItem extends OrderItem {
    selected: boolean
    selectedQuantity: number
}
interface SplitPayment {
    cash: number
    transfer: number
}

type SortKey = keyof Order;
interface SortConfig {
  key: SortKey;
  direction: 'asc' | 'desc';
}

// Utility function to format currency
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(amount);
};

const OrdersPage: React.FC = () => {
    const { showToast } = useToast();

  const [activeTab, setActiveTab] = useState<'newOrder' | 'orderList'>('orderList');
  const [selectedCategory, setSelectedCategory] = useState<'food' | 'drinks' | 'combos'>('food');
  const [cart, setCart] = useState<OrderItem[]>([]);
  const [customerName, setCustomerName] = useState('');
    const [orderNote, setOrderNote] = useState("");
    const [paymentMethod, setPaymentMethod] = useState<'cash' | 'transfer' | 'mixed'>('cash');
    const [filterStatus, setFilterStatus] = useState<'all' | 'pendiente' | 'completado' | 'anulado' | 'eliminado'>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [orders, setOrders] = useState<Order[]>([]);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [transferImageUrl, setTransferImageUrl] = useState('');
  const [productos, setProductos] = useState<Product[]>([]);
  const [showComprobanteModal, setShowComprobanteModal] = React.useState(false);
  const [selectedOrderForComprobante, setSelectedOrderForComprobante] = useState<Order | null>(null);
  const [comprobanteImageFile, setComprobanteImageFile] = useState<File | null>(null);
  const [comprobanteImagePreview, setComprobanteImagePreview] = React.useState('');
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [sortConfig, setSortConfig] = useState<SortConfig | null>(null);
  const [editingOrder, setEditingOrder] = useState<Order | null>(null);

  const [showSplitModal, setShowSplitModal] = useState(false);
  const [selectedOrderForSplit, setSelectedOrderForSplit] = useState<Order | null>(null);
  const [splitOrderItems, setSplitOrderItems] = useState<SplitOrderItem[]>([]);
  const [splitCustomerName, setSplitCustomerName] = useState("");
  const [splitPaymentMethod, setSplitPaymentMethod] = useState<"cash" | "transfer" | "mixed">("cash");
  const [splitPayment, setSplitPayment] = useState<SplitPayment>({ cash: 0, transfer: 0 });
  const [splitTransferImageFile, setSplitTransferImageFile] = useState<File | null>(null);
  const [splitTransferImagePreview, setSplitTransferImagePreview] = useState("");
    const [cajaAbierta, setCajaAbierta] = useState<boolean>(true);
    const [loadingCaja, setLoadingCaja] = useState<boolean>(true);
    const [showConfigModal, setShowConfigModal] = useState(false);
    const [config, setConfig] = useState({
        imprime_ticket: 0,
        formato_ticket: null as number | null
    });
    const [orderToCancel, setOrderToCancel] = useState<number | null>(null);
    const { user } = useAuth();
    const roles = user?.roles?.split(',').map(r => r.trim()) ?? [];

    const isAdmin =
        roles.includes('ROLE_ADMIN') ||
        roles.includes('ROLE_SUPERADMIN');

    useEffect(() => {
        configuracion.getConfig().then((data) => {
            setConfig({
                imprime_ticket: data.imprime_ticket ?? 0,
                formato_ticket: data.formato_ticket ?? null
            });
        });
    }, []);

    useEffect(() => {
    const loadOrders = async () => {
      const fetchedPedidos = await fetchPedidos();
      // NO mapear ni transformar status acá
      setOrders(fetchedPedidos);
    };
    loadOrders();
  }, []);

    useEffect(() => {
        const checkCaja = async () => {
            try {
                setLoadingCaja(true);

                const caja = await getCajaActual(); // 👈 tu endpoint real acá

                setCajaAbierta(caja?.isOpen === true);
            } catch (error) {
                setCajaAbierta(false);
                showToast(getApiErrorMessage(error,'Hubo un error para obtener la caja actual.'), 'info');
            } finally {
                setLoadingCaja(false);
            }
        };

        checkCaja();
    }, []);




    useEffect(() => {
    const categoriaMap: Record<typeof selectedCategory, number> = {
      food: 1,
      drinks: 2,
      combos: 3,
    };

    const loadProductos = async () => {
      if (selectedCategory === 'combos') {
        const combosBackend = await combos.getAll();
        setProductos(combosBackend);
      } else {
        const productosBackend = await fetchProductosByCategoria(categoriaMap[selectedCategory]);
        setProductos(productosBackend);
      }
    };
    loadProductos();
  }, [selectedCategory]);

// Function to subir comprobante y completar pedido
  const handleUploadAndComplete = async () => {
    if (!selectedOrderForComprobante || !comprobanteImageFile) return;

    try {
        // 👉 metodo de pago REAL elegido
        const metodoPago = paymentMethod; // "cash" | "transfer" | "mixed"
        const metodoPagoId = mapPaymentMethodToId(metodoPago);

        // 👉 totales según metodo
        const totalCash =
            metodoPago === "cash"
                ? selectedOrderForComprobante.total
                : metodoPago === "mixed"
                    ? splitPayment.cash
                    : 0;

        const totalTransfer =
            metodoPago === "transfer"
                ? selectedOrderForComprobante.total
                : metodoPago === "mixed"
                    ? splitPayment.transfer
                    : 0;

        // 📤 Subir comprobante + metodo + totales
        await uploadComprobanteImage(
            Number(selectedOrderForComprobante.id),
            comprobanteImageFile,
            metodoPagoId,
            totalCash,
            totalTransfer
        );

        // ✅ Completar pedido
        await completeOrder(selectedOrderForComprobante.id);

        // 🔄 Refrescar pedidos
        const updatedOrders = await fetchPedidos();
        setOrders(updatedOrders);

        // 🧹 Limpiar estados del modal
        setShowComprobanteModal(false);
        setSelectedOrderForComprobante(null);
        setComprobanteImageFile(null);
        setComprobanteImagePreview('');
    } catch (error) {
      console.error('Error al subir comprobante:', error);
        showToast(getApiErrorMessage(error,'Hubo un error al subir el comprobante. Intente nuevamente.'), 'error');
    }
  };

  // Calcular cuánto stock queda descontando lo que hay en carrito
  const getAvailableStock = (productId: string | number): number => {
    const producto = productos.find(p => p.id === Number(productId));
    const enCarrito = cart.find(item => item.id === productId)?.quantity || 0;
    return (producto?.stock_actual || 0) - enCarrito;
  };

  // Editor de pedidos
  const handleEditOrder = async (orderId: string) => {
    const data = await fetchPedidoById(Number(orderId));
    if (!data){
        showToast('No se encontró pedido con ese ID.', 'warning');
      return;
    }

    const { order, items } = data;

    setEditingOrder(order);
    setCustomerName(order.customerName);
    setPaymentMethod(order.paymentMethod as 'cash' | 'transfer' | 'mixed');
    setCart(items);
    setActiveTab("newOrder");
  };


  const handleCompleteClick = async (order: Order) => {
      const data = await fetchPedidoById(Number(order.id))
      if (!data) {
          showToast('No se encontró pedido con ese ID.', 'warning');
          return;
      }
      const { items } = data;
      const splitItems: SplitOrderItem[] = items.map((item) => ({
          ...item,
          selected: false,
          selectedQuantity: 0,
      }))
      setSelectedOrderForSplit(order);
      setSplitOrderItems(splitItems);
      setSplitCustomerName("");
      setSplitPaymentMethod("cash");
      setSplitPayment({ cash: 0, transfer: 0 });
      setSplitTransferImageFile(null);
      setSplitTransferImagePreview("");
      setShowSplitModal(true);
  };

    const handleSplitQuantityChange = (itemId: string, newQuantity: number) => {
        setSplitOrderItems((prev) =>
            prev.map((item) => {
                if (item.id === itemId) {
                    // Ensure quantity doesn't exceed available quantity and is not negative
                    const validQuantity = Math.max(0, Math.min(newQuantity, item.quantity))
                    return {
                        ...item,
                        selectedQuantity: validQuantity,
                        // Auto-select item if quantity > 0, deselect if quantity = 0
                        selected: validQuantity > 0,
                    }
                }
                return item
            }),
        )
    }
    const handleSplitItemToggle = (itemId: string) => {
        setSplitOrderItems((prev) =>
            prev.map((item) => (item.id === itemId ? { ...item, selected: !item.selected, selectedQuantity: item.selected ? 0 : Math.min(1, item.quantity), } : item)),
        )
    }

    // Select / deselect all (sets selectedQuantity = full quantity when selecting all)
    const handleToggleSelectAll = () => {
        const areAllFullySelected = splitOrderItems.length > 0 && splitOrderItems.every(i => i.selected && i.selectedQuantity === i.quantity)
        const willSelect = !areAllFullySelected
        setSplitOrderItems(prev => prev.map(i => ({ ...i, selected: willSelect, selectedQuantity: willSelect ? i.quantity : 0 })))
    }

    // Total of selected items — USE selectedQuantity
    const getSelectedItemsTotal = (): number => {
        return splitOrderItems
            .filter((item) => item.selected && item.selectedQuantity > 0)
            .reduce((total, item) => total + item.price * item.selectedQuantity, 0)
    }

    const validateSplitPayment = (): boolean => {
        const selectedTotal = getSelectedItemsTotal()

        if (splitPaymentMethod === "mixed") {
            const totalPayment = splitPayment.cash + splitPayment.transfer
            return Math.abs(totalPayment - selectedTotal) < 0.01 // Allow for floating point precision
        }
        return true
    }

    const mapPaymentMethodToId = (
        method: "cash" | "transfer" | "mixed"
    ): number => {
        switch (method) {
            case "cash":
                return 1;
            case "transfer":
                return 2;
            case "mixed":
                return 3;
            default:
                return 1;
        }
    };


    const handleProcessSplit = async () => {
        if (!selectedOrderForSplit) return;

        const selectedItems = splitOrderItems.filter(
            (item) => item.selected && item.selectedQuantity > 0
        );

        if (selectedItems.length === 0) {
            showToast("Seleccione al menos un producto con cantidad mayor a 0", "info");
            return;
        }

        const totalOriginalQty = splitOrderItems.reduce((t, it) => t + it.quantity, 0);
        const totalSelectedQty = splitOrderItems.reduce(
            (t, it) => t + (it.selected ? it.selectedQuantity : 0),
            0
        );
        const isAllFullySelected = totalSelectedQty > 0 && totalSelectedQty === totalOriginalQty;

        /* =========================================================
      CASO 1: TODOS LOS PRODUCTOS → COMPLETAR PEDIDO ORIGINAL
      ========================================================= */
        if (isAllFullySelected) {
            if (splitPaymentMethod === "mixed" && !validateSplitPayment()) {
                showToast(
                    "El monto total del pago debe ser igual al total del pedido",
                    "warning"
                );
                return;
            }

            if (
                (splitPaymentMethod === "transfer" ||
                    splitPaymentMethod === "mixed") &&
                !splitTransferImageFile
            ) {
                showToast(
                    "Debe subir el comprobante de transferencia",
                    "info"
                );
                return;
            }

            try {
                const metodoPago = splitPaymentMethod; // cash | transfer | mixed
                const metodoPagoId = mapPaymentMethodToId(metodoPago);

                const totalCash =
                    metodoPago === "cash"
                        ? selectedOrderForSplit.total
                        : metodoPago === "mixed"
                            ? splitPayment.cash
                            : 0;

                const totalTransfer =
                    metodoPago === "transfer"
                        ? selectedOrderForSplit.total
                        : metodoPago === "mixed"
                            ? splitPayment.transfer
                            : 0;

                // 🔴 IMPORTANTE: acá es donde SE DEFINE el método final
                if (splitTransferImageFile) {
                    await uploadComprobanteImage(
                        Number(selectedOrderForSplit.id),
                        splitTransferImageFile,
                        metodoPagoId,
                        totalCash,
                        totalTransfer
                    );
                }

                await completeOrder(selectedOrderForSplit.id);

                const updatedOrders = await fetchPedidos();
                setOrders(updatedOrders);

                setShowSplitModal(false);
                setSelectedOrderForSplit(null);
                setSplitOrderItems([]);
                setSplitCustomerName("");
                setSplitPaymentMethod("cash");
                setSplitPayment({ cash: 0, transfer: 0 });
                setSplitTransferImageFile(null);
                setSplitTransferImagePreview("");

                showToast("Pedido completado exitosamente", "success");
                return;
            } catch (error) {
                console.error("Error al completar el pedido:", error);
                showToast(getApiErrorMessage(error,
                    "Hubo un error al completar el pedido"),
                    "error"
                );
                return;
            }
        }

        /* =========================================================
       CASO 2: PEDIDO PARCIAL (DIVIDIDO)
       ========================================================= */
        if (!splitCustomerName.trim()) {
            showToast("Ingrese el nombre del cliente", "info");
            return;
        }

        if (splitPaymentMethod === "mixed") {
            const totalPago = splitPayment.cash + splitPayment.transfer;
            const totalSeleccionado = getSelectedItemsTotal();
            if (Math.abs(totalPago - totalSeleccionado) > 0.01) {
                showToast(
                    "La suma de efectivo y transferencia debe igualar el total",
                    "warning"
                );
                return;
            }
        }

        if (
            (splitPaymentMethod === "transfer" ||
                splitPaymentMethod === "mixed") &&
            !splitTransferImageFile
        ) {
            showToast(
                "Debe subir el comprobante de transferencia",
                "warning"
            );
            return;
        }

        try {
            const selectedTotal = getSelectedItemsTotal();
            const metodoPagoId = mapPaymentMethodToId(splitPaymentMethod);

            /* 1️⃣ Crear nuevo pedido */
            const newOrder = await createPedido(
                splitCustomerName,
                metodoPagoId,
                selectedTotal,
                selectedItems.map((item) => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.selectedQuantity,
                })),
                "",
                splitPaymentMethod === "cash"
                    ? selectedTotal
                    : splitPaymentMethod === "mixed"
                        ? splitPayment.cash
                        : 0,
                splitPaymentMethod === "transfer"
                    ? selectedTotal
                    : splitPaymentMethod === "mixed"
                        ? splitPayment.transfer
                        : 0
            );

            /* 2️⃣ Subir comprobante al nuevo pedido */
            if (splitTransferImageFile && newOrder?.id) {
                await uploadComprobanteImage(
                    newOrder.id,
                    splitTransferImageFile,
                    metodoPagoId,
                    splitPaymentMethod === "cash" ? selectedTotal : splitPayment.cash,
                    splitPaymentMethod === "transfer"
                        ? selectedTotal
                        : splitPayment.transfer
                );
            }

            /* 3️⃣ Recalcular ítems restantes */
            const remainingItems: OrderItem[] = splitOrderItems
                .map((item) => {
                    if (item.selected && item.selectedQuantity > 0) {
                        const remainingQty =
                            item.quantity - item.selectedQuantity;
                        return remainingQty > 0
                            ? {
                                id: item.id,
                                name: item.name,
                                price: item.price,
                                quantity: remainingQty,
                            }
                            : null;
                    }
                    return {
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: item.quantity,
                    };
                })
                .filter(Boolean) as OrderItem[];

            if (remainingItems.length > 0) {
                await updatePedido(
                    Number(selectedOrderForSplit.id),
                    selectedOrderForSplit.customerName,
                    "cash", // el restante queda abierto
                    remainingItems,
                    ""
                );
            } else {
                await completeOrder(selectedOrderForSplit.id);
            }

            const updatedOrders = await fetchPedidos();
            setOrders(updatedOrders);

            setShowSplitModal(false);
            setSelectedOrderForSplit(null);
            setSplitOrderItems([]);
            setSplitCustomerName("");
            setSplitPaymentMethod("cash");
            setSplitPayment({ cash: 0, transfer: 0 });
            setSplitTransferImageFile(null);
            setSplitTransferImagePreview("");

            showToast("Pedido dividido exitosamente", "success");
        } catch (error) {
            console.error("Error al dividir el pedido:", error);
            showToast(getApiErrorMessage(error,
                "Hubo un error al dividir el pedido"),
                "error"
            );
        }
    };


    const handleCancelOrder = (orderId: string) => {
        setOrderToCancel(Number(orderId));
    };

    const confirmCancelOrder = async () => {
        if (!orderToCancel) return;

        try {
            await cancelPedido(orderToCancel);
            const updatedOrders = await fetchPedidos();
            setOrders(updatedOrders);
        } catch (error) {
            showToast(getApiErrorMessage(error,'Hubo un error al anular el pedido.'), 'error');
        } finally {
            setOrderToCancel(null);
        }
    };


  // Function to add item to cart
    const addToCart = (item: Product) => {
        const existingItem = cart.find((cartItem) => cartItem.id === item.id.toString());

        if (existingItem) {
            setCart(
                cart.map((cartItem) =>
                    cartItem.id === item.id.toString()
                        ? { ...cartItem, quantity: cartItem.quantity + 1 }
                        : cartItem
                )
            );
        } else {
            setCart([...cart, { id: item.id.toString(), name: item.nombre, price: item.precio, quantity: 1 }]);
        }
    };

    // Function to update item quantity
    const updateQuantity = (itemId: string, newQuantity: number) => {
        const availableStock = getAvailableStock(itemId);
        const currentQuantity = cart.find(i => i.id === itemId)?.quantity ?? 0;

        if (newQuantity <= 0) {
            setCart(cart.filter((item) => item.id !== itemId));
        } else if (newQuantity <= availableStock + currentQuantity) {
            setCart(
                cart.map((item) =>
                    item.id === itemId ? { ...item, quantity: newQuantity } : item
                )
            );
        } else {
            showToast(getApiErrorMessage(error,'No hay suficiente stock disponible.'), 'warning');
        }
    };


    // Function to remove item from cart
    const removeFromCart = (itemId: string) => {
        setCart(cart.filter((item) => item.id !== itemId));
    };

    // Calculate cart total
    const cartTotal = cart.reduce((total, item) => total + item.price * item.quantity, 0);

    // Function to create a new order
    const createOrder = async () => {
        if (cart.length === 0) {
            showToast('El carrito está vacío', 'info');
            return;
        }
        if (!customerName.trim()) {
            showToast('Ingrese el nombre del cliente', 'warning');
            return;
        }

        // Validación de pagos mixtos
        if (splitPaymentMethod === "mixed") {
            const totalPago = splitPayment.cash + splitPayment.transfer;
            if (Math.abs(totalPago - cartTotal) > 0.01) {
                showToast(
                    'La suma de efectivo y transferencia debe igualar el total',
                    'warning'
                );
                return;
            }
        }

        try {
            const metodoPago: "cash" | "transfer" | "mixed" = splitPaymentMethod;

            // 🧮 Totales según metodo REAL
            const totalCash =
                metodoPago === "cash"
                    ? cartTotal
                    : metodoPago === "mixed"
                        ? splitPayment.cash
                        : 0;

            const totalTransfer =
                metodoPago === "transfer"
                    ? cartTotal
                    : metodoPago === "mixed"
                        ? splitPayment.transfer
                        : 0;


            if (editingOrder) {
                // ✏️ Editar pedido existente
                await updatePedido(
                    Number(editingOrder.id),
                    customerName,
                    metodoPago,
                    cart,
                    transferImageUrl
                );
            } else {
                // ➕ Crear pedido nuevo
                await createPedido(
                    customerName,
                    mapPaymentMethodToId(metodoPago), // ✅ mixed => 3
                    cartTotal,
                    cart,
                    transferImageUrl,
                    totalCash,
                    totalTransfer
                );
            }

            const updatedOrders = await fetchPedidos();
            setOrders(updatedOrders);

            // 🔄 Reset UI
            setCart([]);
            setCustomerName('');
            setSplitPaymentMethod('cash');
            setSplitPayment({ cash: 0, transfer: 0 });
            setTransferImageUrl('');
            setShowPaymentModal(false);
            setActiveTab('orderList');
            setEditingOrder(null);
        } catch (error) {
            console.error('Error al crear pedido:', error);
            showToast(getApiErrorMessage(error,'Hubo un problema al crear el pedido.'), 'error');
        }
    };


    const handleSort = (key: SortKey) => {
        if (sortConfig?.key === key) {
            // Cambia dirección
            setSortConfig({
                key,
                direction: sortConfig.direction === 'asc' ? 'desc' : 'asc',
            });
        } else {
            setSortConfig({ key, direction: 'asc' });
        }
    };

    // Function to print order ticket
    // const printOrderTicket = (order: Order) => {
    //   // In a real app, this would send data to a printer
    //   console.log('Printing ticket for order:', order.id);
    //   alert(`Imprimiendo ticket para el pedido: ${order.id}`);
    // };

    // Filtered orders based on status and search
    const filteredOrders = React.useMemo(() => {
        return orders.filter(order => {
            const matchesStatus = filterStatus === 'all' || order.status === filterStatus;
            const matchesSearch = order.customerName.toLowerCase().includes(searchTerm.toLowerCase()) || order.id.includes(searchTerm);
            return matchesStatus && matchesSearch;
        });
    }, [orders, filterStatus, searchTerm]);

// Función para ordenar las columnas de mayor a menor y viceversa
    const sortedOrders = React.useMemo(() => {
        if (!sortConfig) return filteredOrders;

        return [...filteredOrders].sort((a, b) => {
            const key = sortConfig.key;

            let aValue = a[key];
            let bValue = b[key];

            if (aValue === undefined || aValue === null) return 1;
            if (bValue === undefined || bValue === null) return -1;

            if (aValue instanceof Date && bValue instanceof Date) {
                aValue = aValue.getTime();
                bValue = bValue.getTime();
            }

            if (typeof aValue === 'string' && typeof bValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            if (aValue < bValue) {
                return sortConfig.direction === 'asc' ? -1 : 1;
            }
            if (aValue > bValue) {
                return sortConfig.direction === 'asc' ? 1 : -1;
            }
            return 0;
        });
    }, [filteredOrders, sortConfig]);

    const getFilterStatusLabel = () => {
        switch (filterStatus) {
            case 'pendiente': return 'Pendientes';
            case 'completado': return 'Completados';
            case 'anulado': return 'Anulados';
            case 'eliminado': return 'Eliminados';
            default: return 'Todos';
        }
    };

    const handleUpdateOrder = async () => {
        if (!editingOrder) return;

        if (cart.length === 0) {
            showToast("El carrito está vacío", "info");
            return;
        }

        if (!customerName.trim()) {
            showToast("Ingrese el nombre del cliente", "warning");
            return;
        }

        try {
            await updatePedido(
                Number(editingOrder.id), // 🔥 usamos TU objeto
                customerName,
                paymentMethod,
                cart
            );

            showToast("Pedido actualizado correctamente", "success");

            // 🔥 RESET (CLAVE)
            setEditingOrder(null);
            setCart([]);
            setCustomerName("");
            setPaymentMethod("cash");
            setActiveTab("orderList"); // 👈 opcional pero recomendable

            const updatedOrders = await fetchPedidos();
            setOrders(updatedOrders);

        } catch (error) {
            console.error("Error al actualizar pedido:", error);
            showToast(
                getApiErrorMessage(error, "Error al actualizar pedido"),
                "error"
            );
        }
    };

    const handleCreateOrderWithoutPayment = async () => {
        if (cart.length === 0) {
            showToast("El carrito está vacío", "info");
            return;
        }

        if (!customerName.trim()) {
            showToast("Ingrese el nombre del cliente", "warning");
            return;
        }

        try {
            const newOrder = await createPedido(
                customerName,
                1, // métod por defecto, o podrías dejar "undefined" si lo maneja el backend
                cartTotal,
                cart,
                "", // sin comprobante
                0,  // total_efectivo
                0   // total_transferencia
            );
            // 🔹 Nuevo: imprimir ticket de cocina
            printTicket(
                { id: newOrder.id, customerName, total: cartTotal } as any,
                cart,
                "kitchen",
                orderNote
            );
            showToast("Pedido creado correctamente. Pendiente de pago.", "success");

            // Reset UI
            setCart([]);
            setCustomerName("");
            setPaymentMethod("cash");
            setShowPaymentModal(false);
            setOrderNote("");

            // Refrescar lista
            const updatedOrders = await fetchPedidos();
            setOrders(updatedOrders);
        } catch (error) {
            console.error("Error al crear pedido sin pago:", error);
            showToast(getApiErrorMessage(error,"Error al crear pedido."), "error");
        }
    };

    // Lógica del Ticket
    const printTicket = (
        order: Order,
        items: any[],
        type: "kitchen" | "customer",
        note?: string
    ) => {
        // 🔴 1. VALIDACIÓN GLOBAL
        if (config.imprime_ticket !== 1) return;

        // 🔴 2. VALIDAR FORMATO
        if (!config.formato_ticket) {
            console.warn("Formato de ticket no definido");
            return;
        }

        const is58 = config.formato_ticket === 1;

        const printWindow = window.open("", "_blank", "width=400,height=600");
        if (!printWindow) return;

        const now = new Date();
        const formattedDate = now.toLocaleString("es-AR");

        // 🔴 3. ESTILOS DINÁMICOS
        const style = `
        <style>
            body { 
                font-family: monospace; 
                width: ${is58 ? "58mm" : "80mm"}; 
                margin: 0; 
                padding: 8px; 
            }
            h1, h2, h3 { text-align: center; margin: 4px 0; }
            table { width: 100%; border-collapse: collapse; font-size: ${is58 ? "12px" : "14px"}; }
            td { padding: 4px 0; }
            .price { font-size: ${is58 ? "10px" : "12px"}; color: #555; margin-top: 2px; }
            .qty { font-size: ${is58 ? "18px" : "20px"}; font-weight: bold; text-align: right; }
            .total { font-size: ${is58 ? "14px" : "16px"}; font-weight: bold; text-align: right; margin-top: 6px; }
            .note { margin-top: 8px; font-size: ${is58 ? "12px" : "14px"}; white-space: pre-wrap; word-wrap: break-word; }
            .thankyou { text-align: center; margin-top: 12px; font-size: ${is58 ? "12px" : "14px"}; }

            @media print { 
                @page { size: ${is58 ? "58mm" : "80mm"} auto; margin: 0; } 
                body { margin: 0; } 
            }
        </style>
    `;

        let html = `
        <html><head>${style}</head><body>
        <h2>${type === "kitchen" ? "TICKET DE COCINA" : "COMPROBANTE"}</h2>
        <h3>Pedido #${order.id}</h3>
        <p><strong>Cliente:</strong> ${order.customerName || "Sin nombre"}</p>
        <p><strong>Fecha:</strong> ${formattedDate}</p>
        <hr/>
        <table>
    `;

        items.forEach(i => {
            html += `
        <tr>
            <td>
                ${i.name}
                ${type === "customer" ? `<div class="price">${formatCurrency(i.price)}</div>` : ""}
            </td>
            <td class="qty">x${i.quantity}</td>
        </tr>
        `;
        });

        html += "</table>";

        // 🔹 Observaciones
        if (type === "kitchen" && note && note.trim() !== "") {
            html += `<div class="note"><strong>OBSERVACIONES:</strong><br>${note}</div>`;
        }

        if (type === "customer") {
            html += `
            <hr/>
            <div class="total">TOTAL: ${formatCurrency(order.total)}</div>
            <div class="thankyou">¡Gracias por su compra!</div>
        `;
        }

        html += "</body></html>";

        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.print();
    };

    // Fin Lógica del Ticket

    return (
        <div className="space-y-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                <h1 className="text-2xl font-bold text-white">Gestión de Pedidos</h1>
                <div className="mt-4 md:mt-0 flex flex-col sm:flex-row sm:space-x-3 space-y-2 sm:space-y-0">
                    <button
                        onClick={() => setShowConfigModal(true)}
                        className="btn bg-cyan-400 text-white border border-black"
                    >
                        🖨️ Impresora
                    </button>
                    <button
                        onClick={() => setActiveTab("orderList")}
                        className={`btn ${
                            activeTab === "orderList" ? "bg-[#FF6B35] text-white" : "bg-white text-gray-700 border border-gray-300"
                        }`}
                    >
                        <ShoppingCart size={18} className="mr-2" />
                        Ver Pedidos
                    </button>
                    <button
                        onClick={() => setActiveTab("newOrder")}
                        className={`btn ${
                            activeTab === "newOrder" ? "bg-[#FF6B35] text-white" : "bg-white text-gray-700 border border-gray-300"
                        }`}
                    >
                        <Plus size={18} className="mr-2" />
                        Nuevo Pedido
                    </button>
                </div>
            </div>

            {activeTab === "orderList" ? (
                <div className="bg-cyan-700 rounded-xl shadow-md overflow-hidden">
                    <div className="p-5 border-b border-gray-200">
                        <div className="flex flex-col md:flex-row justify-between space-y-4 md:space-y-0">
                            <div className="relative max-w-md">
                                <div className="absolute pl-2 flex items-center pointer-events-none">
                                    <Search size={19} className="text-gray-400"/>
                                </div>
                                <input
                                    type="text"
                                    className="pl-10 form-input w-full max-w-xs text-gray-700"
                                    placeholder="Buscar por cliente o #"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <div className="flex space-x-2">
                                <div className="relative">
                                    <button
                                        onClick={() => setIsFilterOpen(!isFilterOpen)}
                                        className="flex items-center space-x-1 bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        <Filter size={16} />
                                        <span>Estado: {getFilterStatusLabel()}</span>
                                        <ChevronDown size={16} />
                                    </button>
                                    <div
                                        className={`absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 ${isFilterOpen ? "" : "hidden"}`}
                                    >
                                        <div className="py-1">
                                            <button
                                                onClick={() => {
                                                    setFilterStatus("all")
                                                    setIsFilterOpen(false) // cerrar dropdown
                                                }}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                                            >
                                                Todos
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setFilterStatus("pendiente")
                                                    setIsFilterOpen(false)
                                                }}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                                            >
                                                Pendientes
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setFilterStatus("completado")
                                                    setIsFilterOpen(false)
                                                }}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                                            >
                                                Completados
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setFilterStatus("anulado")
                                                    setIsFilterOpen(false)
                                                }}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                                            >
                                                Anulados
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setFilterStatus("eliminado")
                                                    setIsFilterOpen(false)
                                                }}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                                            >
                                                Eliminados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        {/* 🔄 Cargando estado de caja */}
                        {loadingCaja ? (
                            <div className="text-center py-10 text-gray-400">
                                Verificando estado de caja...
                            </div>

                        ) : !cajaAbierta ? (

                            /* 🔒 CAJA CERRADA */
                            <div className="text-center py-16">
                                <ShoppingCart size={48} className="mx-auto text-gray-400" />

                                <h3 className="mt-4 text-lg font-semibold text-gray-200">
                                    Caja cerrada
                                </h3>

                                <p className="mt-2 text-sm text-gray-300">
                                    Para comenzar a tomar pedidos, primero debes abrir una caja.
                                </p>

                                <div className="mt-6">
                                    <Link to="/cash-register">
                                        <button className="px-6 py-3 bg-[#FF6B35] hover:bg-[#D6492C] text-white rounded-lg font-medium transition">
                                            Abrir Caja
                                        </button>
                                    </Link>
                                </div>
                            </div>

                        ) : filteredOrders.length > 0 ? (
                            <>
                                {/* 📱 MOBILE VIEW */}
                                <div className="grid grid-cols-1 gap-4 md:hidden">
                                    {sortedOrders.map((order) => (
                                        <div key={order.id} className="bg-white rounded-xl shadow-md p-4 space-y-3">

                                            {/* HEADER */}
                                            <div className="flex justify-between items-center">
                                                <span className="font-bold text-lg text-gray-800">#{order.id}</span>

                                                <span className={`text-xs px-2 py-1 rounded-full font-medium ${
                                                    order.status === "completado"
                                                        ? "bg-green-100 text-green-800"
                                                        : order.status === "anulado"
                                                            ? "bg-red-100 text-red-800"
                                                            : order.status === "eliminado"
                                                                ? "bg-gray-200 text-gray-600"
                                                                : "bg-yellow-100 text-yellow-800"
                                                }`}>
                                                    {order.status}
                                                </span>
                                            </div>

                                            {/* INFO */}
                                            <div className="text-sm text-gray-700 space-y-1">
                                                <p><strong>Cliente:</strong> {order.customerName}</p>
                                                <p><strong>Total:</strong> {formatCurrency(order.total)}</p>
                                                <p><strong>Hora:</strong> {order.createdAt.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })}</p>
                                                <div className="flex items-center text-sm text-gray-700">
                                                    <strong className="mr-1">Pago:</strong>
                                                        {order.paymentMethod === "cash" && (
                                                            <DollarSign size={16} className="mx-1 text-green-500" />
                                                        )}
                                                        {order.paymentMethod === "transfer" && (
                                                            <Smartphone size={16} className="mx-1 text-purple-500" />
                                                        )}
                                                        {order.paymentMethod === "mixed" && (
                                                            <>
                                                                <DollarSign size={16} className="mx-1 text-green-500" />
                                                                <Smartphone size={16} className="mx-1 text-purple-500" />
                                                            </>
                                                        )}
                                                    <span className="ml-1">
                                                        {order.paymentMethod === "cash"
                                                            ? "Efectivo"
                                                            : order.paymentMethod === "transfer"
                                                                ? "Transferencia"
                                                                : order.paymentMethod === "mixed"
                                                                    ? "Mixto"
                                                                    : order.paymentMethod
                                                        }
                                                    </span>
                                                </div>
                                            </div>

                                            {/* ACCIONES GRANDES */}
                                            <div className="grid grid-cols-2 gap-2 pt-2">

                                                {order.status === "pendiente" && (
                                                    <button
                                                        onClick={() => handleCompleteClick(order)}
                                                        className="bg-green-500 text-white py-2 rounded-lg text-sm"
                                                    >
                                                        ✔
                                                    </button>
                                                )}

                                                {order.status !== "anulado" && order.status !== "eliminado" && (
                                                    <button
                                                        onClick={() => handleEditOrder(order.id)}
                                                        className="bg-blue-500 text-white py-2 rounded-lg text-sm"
                                                    >
                                                        ✏️
                                                    </button>
                                                )}

                                                {config.imprime_ticket === 1 && (
                                                    <button
                                                        onClick={() => {
                                                            fetchPedidoById(Number(order.id)).then(data => {
                                                                if (data?.items) {
                                                                    printTicket(order, data.items, "customer");
                                                                } else {
                                                                    showToast("No se pudieron obtener los productos del pedido.", "error");
                                                                }
                                                            });
                                                        }}
                                                        className="bg-[#2EC4B6] text-white py-2 rounded-lg text-sm"
                                                    >
                                                        🖨️
                                                    </button>
                                                )}
                                                {order.status !== "anulado" && order.status !== "eliminado" && (
                                                    <button
                                                        onClick={() => handleCancelOrder(order.id)}
                                                        className="bg-red-500 text-white py-2 rounded-lg text-sm col-span-2"
                                                    >
                                                        Anular
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <table className="hidden md:table min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("id")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Pedido #{sortConfig?.key === "id" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("customerName")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Cliente
                                            {sortConfig?.key === "customerName" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("status")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Estado
                                            {sortConfig?.key === "status" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("total")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Total
                                            {sortConfig?.key === "total" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("createdAt")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Hora
                                            {sortConfig?.key === "createdAt" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            onClick={() => handleSort("paymentMethod")}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Pago
                                            {sortConfig?.key === "paymentMethod" ? (sortConfig.direction === "asc" ? " 🔼" : " 🔽") : null}
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Acciones
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                    {sortedOrders.map((order) => (
                                        <tr key={order.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">#{order.id}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{order.customerName}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                        order.status === "completado"
                                                            ? "bg-green-100 text-green-800"
                                                            : order.status === "anulado"
                                                                ? "bg-red-100 text-red-800"
                                                                : order.status === "eliminado"
                                                                    ? "bg-gray-200 text-gray-600 line-through"
                                                                    : "bg-yellow-100 text-yellow-800" // pendiente
                                                    }`}
                                                >
                                                  {order.status === "completado" && <Check size={12} className="mr-1" />}
                                                    {order.status === "anulado" && <X size={12} className="mr-1" />}
                                                    {order.status === "eliminado" && <X size={12} className="mr-1" />}
                                                    {order.status === "pendiente" && <Clock size={12} className="mr-1" />}

                                                    {order.status === "completado"
                                                        ? "Completado"
                                                        : order.status === "anulado"
                                                            ? "Anulado"
                                                            : order.status === "eliminado"
                                                                ? "Eliminado"
                                                                : "Pendiente"}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900 font-medium">{formatCurrency(order.total)}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-500">
                                                    {order.createdAt.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center text-sm text-gray-500">
                                                    {order.paymentMethod === "cash" && <DollarSign size={16} className="mr-1 text-green-500" />}
                                                    {order.paymentMethod === "transfer" && <Smartphone size={16} className="mr-1 text-purple-500" />}
                                                    {order.paymentMethod === "mixed" && (
                                                        <>
                                                            <DollarSign size={16} className="mr-1 text-green-500" />
                                                            <Smartphone size={16} className="mr-1 text-purple-500" />
                                                        </>
                                                    )}
                                                    <span className="capitalize">
                                                        {order.paymentMethod === "cash"
                                                            ? "Efectivo"
                                                            : order.paymentMethod === "transfer"
                                                                ? "Transferencia"
                                                                : order.paymentMethod === "mixed"
                                                                    ? "Mixto"
                                                                    : order.paymentMethod
                                                        }
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                {order.status === "pendiente" && (
                                                    <button
                                                        onClick={() => handleCompleteClick(order)}
                                                        className="text-[#3BB273] hover:text-[#2E9D60]"
                                                        title="Dividir y completar pedido"
                                                    >
                                                        <Check size={18} />
                                                    </button>
                                                )}
                                                {order.status !== "anulado" && order.status !== "eliminado" && (
                                                    <button
                                                        onClick={() => handleEditOrder(order.id)}
                                                        className="text-blue-500 hover:text-blue-700"
                                                        title="Editar pedido"
                                                    >
                                                        <Edit size={18} />
                                                    </button>
                                                )}
                                                {config.imprime_ticket === 1 && (
                                                    <button
                                                        onClick={() => {
                                                            fetchPedidoById(Number(order.id)).then(data => {
                                                                if (data?.items) {
                                                                    printTicket(order, data.items, "customer");
                                                                } else {
                                                                    showToast("No se pudieron obtener los productos del pedido.", "error");
                                                                }
                                                            });
                                                        }}
                                                        className="text-[#2EC4B6] hover:text-[#23A399]"
                                                        title="Imprimir ticket"
                                                    >
                                                        <Printer size={18}/>
                                                    </button>
                                                )}
                                                {order.status !== "anulado" && order.status !== "eliminado" && (
                                                    <button
                                                        onClick={() => handleCancelOrder(order.id)}
                                                        className="text-red-500 hover:text-red-700"
                                                        title="Anular pedido"
                                                    >
                                                        <X size={18} />
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    </tbody>
                                </table>
                            </>

                        ) : (
                            /* 📭 NO HAY PEDIDOS (PERO CAJA ABIERTA) */
                            <div className="text-center py-10">
                                <ShoppingCart size={48} className="mx-auto text-gray-400" />

                                <h3 className="mt-4 text-lg font-medium text-white">
                                    No hay pedidos
                                </h3>

                                <p className="mt-2 text-sm text-gray-300">
                                    Aún no se registraron pedidos en la caja actual.
                                </p>

                                <p className="mt-1 text-sm text-gray-200">
                                    Si aplicaste filtros, puede que no haya coincidencias.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <div className="bg-white rounded-xl shadow-md overflow-hidden">
                            {editingOrder && (
                                <div className="px-5 py-2 bg-yellow-100 text-yellow-800 text-sm font-medium">
                                    Editando pedido #{editingOrder.id}
                                </div>
                            )}

                            <div className="px-5 py-4 border-b border-gray-200">
                                <div className="flex items-center space-x-4">
                                    <button
                                        onClick={() => setSelectedCategory("food")}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                            selectedCategory === "food"
                                                ? "bg-[#FF6B35] text-white"
                                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                        }`}
                                    >
                                        Comidas
                                    </button>
                                    <button
                                        onClick={() => setSelectedCategory("drinks")}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                            selectedCategory === "drinks"
                                                ? "bg-[#FF6B35] text-white"
                                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                        }`}
                                    >
                                        Bebidas
                                    </button>
                                    <button
                                        onClick={() => setSelectedCategory("combos")}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                            selectedCategory === "combos"
                                                ? "bg-[#FF6B35] text-white"
                                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                        }`}
                                    >
                                        Combos
                                    </button>
                                </div>
                            </div>

                            <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                {productos.map((item) => {
                                    const stockRestante = getAvailableStock(item.id)
                                    return (
                                        <div
                                            key={item.id}
                                            className="bg-gray-50 border border-gray-200 rounded-lg p-4 flex justify-between hover:shadow-md transition-shadow"
                                        >
                                            <div>
                                                <h3 className="font-medium text-gray-800">{item.nombre}</h3>
                                                <p className="text-[#FF6B35] font-bold">{formatCurrency(item.precio)}</p>
                                                <p className="text-sm text-gray-500">Stock: {stockRestante}</p>
                                            </div>
                                            <button
                                                onClick={() => addToCart(item)}
                                                disabled={stockRestante <= 0}
                                                className={`self-center p-2 rounded-full transition-colors ${
                                                    stockRestante <= 0
                                                        ? "bg-gray-300 text-white cursor-not-allowed"
                                                        : "bg-[#FF6B35] text-white hover:bg-[#D6492C]"
                                                }`}
                                            >
                                                <Plus size={16} />
                                            </button>
                                        </div>
                                    )
                                })}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-md overflow-hidden h-fit">
                        {editingOrder && (
                            <div className="px-5 py-2 bg-yellow-100 text-yellow-800 text-sm font-medium">
                                Editando pedido #{editingOrder.id}
                            </div>
                        )}
                        <div className="px-5 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-800">Detalle del Pedido</h2>
                        </div>

                        <div className="p-5">
                            <div className="mb-4">
                                <label htmlFor="customerName" className="form-label">
                                    Nombre del Cliente
                                </label>
                                <input
                                    id="customerName"
                                    type="text"
                                    className="form-input w-full text-gray-700"
                                    placeholder="Ingrese nombre del cliente"
                                    value={customerName}
                                    onChange={(e) => setCustomerName(e.target.value)}
                                />
                            </div>

                            <div className="mb-4">
                                <h3 className="text-sm font-medium text-gray-700 mb-2">Productos</h3>

                                {cart.length > 0 ? (
                                    <div className="space-y-3 max-h-64 overflow-y-auto mb-3">
                                        {cart.map((item) => (
                                            <div key={item.id} className="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-medium text-gray-800">{item.name}</h4>
                                                    <p className="text-xs text-gray-500">{formatCurrency(item.price)}</p>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    {(isAdmin || !editingOrder) && (
                                                        <button
                                                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                            className="p-1 bg-gray-200 rounded-full hover:bg-gray-300"
                                                        >
                                                            <span className="sr-only">Disminuir</span>
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                className="h-4 w-4 text-gray-600"
                                                                viewBox="0 0 20 20"
                                                                fill="currentColor"
                                                            >
                                                                <path
                                                                    fillRule="evenodd"
                                                                    d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                                                    clipRule="evenodd"
                                                                />
                                                            </svg>
                                                        </button>
                                                    )}
                                                    <span className="text-sm font-medium text-gray-700">{item.quantity}</span>
                                                    <button
                                                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                        className="p-1 bg-gray-200 rounded-full hover:bg-gray-300"
                                                    >
                                                        <span className="sr-only">Aumentar</span>
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            className="h-4 w-4 text-gray-600"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                    {(isAdmin || !editingOrder) && (
                                                        <button
                                                            onClick={() => removeFromCart(item.id)}
                                                            className="p-1 text-red-500 hover:text-red-700"
                                                        >
                                                            <span className="sr-only">Eliminar</span>
                                                            <X size={16} />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6 bg-gray-50 rounded-lg">
                                        <ShoppingCart size={32} className="mx-auto text-gray-400" />
                                        <p className="mt-2 text-sm text-gray-500">El carrito está vacío</p>
                                    </div>
                                )}
                            </div>

                            {/* Observaciones del pedido */}
                            <div className="mb-4">
                                <label htmlFor="orderNote" className="form-label">
                                    Observaciones / Notas del Pedido
                                </label>
                                <textarea
                                    id="orderNote"
                                    className="form-textarea w-full rounded-md border-gray-300 focus:ring-[#FF6B35] focus:border-[#FF6B35] text-gray-700"
                                    placeholder="Ej: sin verduras, poco queso, sin sal..."
                                    rows={3}
                                    value={orderNote}
                                    onChange={(e) => setOrderNote(e.target.value)}
                                />
                            </div>

                            <div className="border-t border-gray-200 pt-4 mt-4">
                                <div className="flex justify-between mb-2">
                                    <span className="text-sm text-gray-600">Subtotal</span>
                                    <span className="text-sm font-medium text-gray-800">{formatCurrency(cartTotal)}</span>
                                </div>
                                <div className="flex justify-between font-bold">
                                    <span className="text-base text-gray-800">Total</span>
                                    <span className="text-base text-[#FF6B35]">{formatCurrency(cartTotal)}</span>
                                </div>
                            </div>

                            <div className="mt-6">
                                <button
                                    onClick={editingOrder ? handleUpdateOrder : handleCreateOrderWithoutPayment}
                                    disabled={cart.length === 0 || !customerName.trim()}
                                    className={`w-full py-3 px-4 rounded-lg font-medium text-white ${
                                        cart.length === 0 || !customerName.trim()
                                            ? "bg-gray-300 cursor-not-allowed"
                                            : "bg-[#FF6B35] hover:bg-[#D6492C]"
                                    }`}
                                >
                                    {editingOrder ? "Actualizar Pedido" : "Finalizar Pedido"}
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            )}

            {showSplitModal && selectedOrderForSplit && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4">
                        <div className="fixed inset-0 bg-gray-500 opacity-75"></div>
                        <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 relative z-10 max-h-[90vh] overflow-y-auto">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-center bg-gray-400">Dividir Pedido #{selectedOrderForSplit.id}</h3>
                                <label className="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        checked={splitOrderItems.length > 0 && splitOrderItems.every(i => i.selected && i.selectedQuantity === i.quantity)}
                                        onChange={handleToggleSelectAll}
                                        className="h-4 w-4 text-[#FF6B35] focus:ring-[#FF6B35] border-gray-300 rounded"
                                    />
                                    <span className="text-sm text-gray-600">Seleccionar todo</span>
                                </label>
                            </div>

                            {/* Customer Name Input */}
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Nombre del Cliente</label>
                                <input
                                    type="text"
                                    className="w-full px-3 py-2 border border-gray-300 text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FF6B35]"
                                    placeholder="Ingrese nombre del cliente"
                                    value={splitCustomerName}
                                    onChange={(e) => setSplitCustomerName(e.target.value)}
                                />
                            </div>

                            {/* Products Selection */}
                            <div className="mb-4">
                                <h4 className="text-sm font-medium text-gray-700 mb-2">Seleccionar Productos y Cantidades</h4>
                                <div className="max-h-64 overflow-y-auto border border-gray-200 rounded-md">
                                    {splitOrderItems.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between p-3 border-b border-gray-100 last:border-b-0"
                                        >
                                            <div className="flex items-center space-x-3 flex-1">
                                                <input
                                                    type="checkbox"
                                                    checked={item.selected}
                                                    onChange={() => handleSplitItemToggle(item.id)}
                                                    className="h-4 w-4 text-[#FF6B35] focus:ring-[#FF6B35] border-gray-300 rounded"
                                                />
                                                <div className="flex-1">
                                                    <h5 className="text-sm font-medium text-gray-800">{item.name}</h5>
                                                    <p className="text-xs text-gray-500">
                                                        Disponible: {item.quantity} | Precio: {formatCurrency(item.price)}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center space-x-2">
                                                <div className="flex items-center space-x-1">
                                                    <button
                                                        onClick={() => handleSplitQuantityChange(item.id, item.selectedQuantity - 1)}
                                                        disabled={item.selectedQuantity <= 0}
                                                        className="p-1 bg-gray-200 rounded-full hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            className="h-3 w-3 text-gray-600"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>

                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.quantity}
                                                        value={item.selectedQuantity}
                                                        onChange={(e) => handleSplitQuantityChange(item.id, Number.parseInt(e.target.value) || 0)}
                                                        className="w-16 px-2 py-1 text-center text-gray-800 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#FF6B35]"
                                                    />

                                                    <button
                                                        onClick={() => handleSplitQuantityChange(item.id, item.selectedQuantity + 1)}
                                                        disabled={item.selectedQuantity >= item.quantity}
                                                        className="p-1 bg-gray-200 rounded-full hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            className="h-3 w-3 text-gray-600"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div className="text-sm font-medium text-gray-800 min-w-[80px] text-right">
                                                    {item.selectedQuantity > 0 ? formatCurrency(item.price * item.selectedQuantity) : "-"}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Selected Items Total */}
                            <div className="mb-4 p-3 bg-gray-50 rounded-md">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">Total de productos seleccionados:</span>
                                    <span className="text-lg font-bold text-[#FF6B35]">{formatCurrency(getSelectedItemsTotal())}</span>
                                </div>
                            </div>

                            {/* Payment Method Selection */}
                            <div className="mb-4">
                                <h4 className="text-sm font-medium text-gray-700 mb-2">Método de Pago</h4>
                                <div className="space-y-2">
                                    <label className="inline-flex items-center text-gray-700">
                                        <input
                                            type="radio"
                                            name="splitPaymentMethod"
                                            value="cash"
                                            checked={splitPaymentMethod === "cash"}
                                            onChange={() => setSplitPaymentMethod("cash")}
                                            className="form-radio text-[#FF6B35]"
                                        />
                                        <span className="ml-2 flex items-center">
                      <DollarSign size={18} className="mr-1 text-green-500" />
                       Efectivo
                    </span>
                                    </label>

                                    <label className="inline-flex items-center text-gray-700">
                                        <input
                                            type="radio"
                                            name="splitPaymentMethod"
                                            value="transfer"
                                            checked={splitPaymentMethod === "transfer"}
                                            onChange={() => setSplitPaymentMethod("transfer")}
                                            className="form-radio text-[#FF6B35]"
                                        />
                                        <span className="ml-2 flex items-center">
                      <Smartphone size={18} className="mr-1 text-purple-500" />
                       Transferencia
                    </span>
                                    </label>

                                    <label className="inline-flex items-center text-gray-700">
                                        <input
                                            type="radio"
                                            name="splitPaymentMethod"
                                            value="mixed"
                                            checked={splitPaymentMethod === "mixed"}
                                            onChange={() => setSplitPaymentMethod("mixed")}
                                            className="form-radio text-[#FF6B35]"
                                        />
                                        <span className="ml-2 flex items-center">
                      <DollarSign size={16} className="mr-1 text-green-500" />
                      <Smartphone size={16} className="mr-1 text-purple-500" />
                       Mixto (Efectivo + Transferencia)
                    </span>
                                    </label>
                                </div>
                            </div>

                            {/* Mixed Payment Inputs */}
                            {splitPaymentMethod === "mixed" && (
                                <div className="mb-4 p-3 bg-gray-50 rounded-md">
                                    <h5 className="text-sm font-medium text-gray-700 mb-2">Distribución del Pago</h5>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Efectivo</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max={getSelectedItemsTotal()}
                                                className="w-full px-3 py-2 border border-gray-300 text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FF6B35]"
                                                value={splitPayment.cash === 0 ? "" : splitPayment.cash} // 👈 clave
                                                onChange={(e) => {
                                                    const value = e.target.value;
                                                    setSplitPayment((prev) => ({
                                                        ...prev,
                                                        cash: value === "" ? 0 : Number.parseFloat(value),
                                                    }));
                                                }}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Transferencia</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max={getSelectedItemsTotal()}
                                                className="w-full px-3 py-2 border border-gray-300 text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FF6B35]"
                                                value={splitPayment.transfer === 0 ? "" : splitPayment.transfer}
                                                onChange={(e) => {
                                                    const value = e.target.value;
                                                    setSplitPayment((prev) => ({
                                                        ...prev,
                                                        transfer: value === "" ? 0 : Number.parseFloat(value),
                                                    }));
                                                }}
                                            />

                                        </div>
                                    </div>
                                    <div className="mt-2 text-xs text-gray-600">
                                        Total: {formatCurrency(splitPayment.cash + splitPayment.transfer)} /{" "}
                                        {formatCurrency(getSelectedItemsTotal())}
                                    </div>
                                </div>
                            )}

                            {/* Transfer Image Upload */}
                            {(splitPaymentMethod === "transfer" || splitPaymentMethod === "mixed") && (
                                <div className="mb-4">
                                    <h5 className="text-sm font-medium text-gray-700 mb-2">Comprobante de Transferencia</h5>
                                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                        {splitTransferImagePreview ? (
                                            <div className="text-center">
                                                <img
                                                    src={splitTransferImagePreview || "/placeholder.svg"}
                                                    alt="Comprobante"
                                                    className="h-32 object-contain mx-auto"
                                                />
                                                <button
                                                    onClick={() => {
                                                        setSplitTransferImageFile(null)
                                                        setSplitTransferImagePreview("")
                                                    }}
                                                    className="mt-2 text-sm text-red-600 hover:text-red-500"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="space-y-1 text-center">
                                                <svg
                                                    className="mx-auto h-12 w-12 text-gray-400"
                                                    stroke="currentColor"
                                                    fill="none"
                                                    viewBox="0 0 48 48"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                        strokeWidth="2"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                </svg>
                                                <div className="flex text-sm text-gray-600 justify-center">
                                                    <label
                                                        htmlFor="split-file-upload"
                                                        className="relative cursor-pointer bg-white rounded-md font-medium text-[#FF6B35] hover:text-[#D6492C]"
                                                    >
                                                        <span>Subir archivo</span>
                                                        <input
                                                            id="split-file-upload"
                                                            name="split-file-upload"
                                                            type="file"
                                                            className="sr-only"
                                                            accept="image/*"
                                                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                                                const file = e.target.files?.[0]
                                                                if (file) {
                                                                    setSplitTransferImageFile(file)
                                                                    setSplitTransferImagePreview(URL.createObjectURL(file))
                                                                }
                                                            }}
                                                        />
                                                    </label>
                                                    <p className="pl-1">o arrastrar y soltar</p>
                                                </div>
                                                <p className="text-xs text-gray-500">PNG, JPG, GIF hasta 10MB</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Action Buttons */}
                            <div className="flex justify-end space-x-2 mt-6">
                                <button
                                    onClick={() => {
                                        setShowSplitModal(false)
                                        setSelectedOrderForSplit(null)
                                        setSplitOrderItems([])
                                        setSplitCustomerName("")
                                        setSplitPaymentMethod("cash")
                                        setSplitPayment({ cash: 0, transfer: 0 })
                                        setSplitTransferImageFile(null)
                                        setSplitTransferImagePreview("")
                                    }}
                                    className="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400"
                                >
                                    Cancelar
                                </button>

                                {/* Button switches behavior/label depending on whether ALL items (full quantities) are selected */}
                                <button
                                    onClick={handleProcessSplit}
                                    disabled={
                                        // compute states:
                                        (() => {
                                            const anySelected = splitOrderItems.filter((item) => item.selected && item.selectedQuantity > 0).length > 0
                                            const totalOriginalQty = splitOrderItems.reduce((t, it) => t + it.quantity, 0)
                                            const totalSelectedQty = splitOrderItems.reduce((t, it) => t + (it.selected ? it.selectedQuantity : 0), 0)
                                            const isAllFullySelected = totalSelectedQty > 0 && totalSelectedQty === totalOriginalQty

                                            if (isAllFullySelected) {
                                                // For completion: no need for splitCustomerName, but still validate payments/images
                                                return (splitPaymentMethod === "mixed" && !validateSplitPayment()) ||
                                                    ((splitPaymentMethod === "transfer" || splitPaymentMethod === "mixed") && !splitTransferImageFile)
                                            } else {
                                                // For dividing: need customer name + at least one selected + payment validations
                                                return !splitCustomerName.trim() ||
                                                    !anySelected ||
                                                    (splitPaymentMethod === "mixed" && !validateSplitPayment()) ||
                                                    ((splitPaymentMethod === "transfer" || splitPaymentMethod === "mixed") && !splitTransferImageFile)
                                            }
                                        })()
                                    }
                                    className={`px-4 py-2 rounded text-white ${
                                        // Visual style: enable if above check returns false
                                        (() => {
                                            const anySelected = splitOrderItems.filter((item) => item.selected && item.selectedQuantity > 0).length > 0
                                            const totalOriginalQty = splitOrderItems.reduce((t, it) => t + it.quantity, 0)
                                            const totalSelectedQty = splitOrderItems.reduce((t, it) => t + (it.selected ? it.selectedQuantity : 0), 0)
                                            const isAllFullySelected = totalSelectedQty > 0 && totalSelectedQty === totalOriginalQty

                                            const disabled = isAllFullySelected
                                                ? (splitPaymentMethod === "mixed" && !validateSplitPayment()) ||
                                                ((splitPaymentMethod === "transfer" || splitPaymentMethod === "mixed") && !splitTransferImageFile)
                                                : !splitCustomerName.trim() ||
                                                !anySelected ||
                                                (splitPaymentMethod === "mixed" && !validateSplitPayment()) ||
                                                ((splitPaymentMethod === "transfer" || splitPaymentMethod === "mixed") && !splitTransferImageFile)

                                            return disabled ? "bg-gray-400 cursor-not-allowed" : "bg-[#FF6B35] hover:bg-[#D6492C]"
                                        })()
                                    }`}
                                >
                                    {(() => {
                                        const totalOriginalQty = splitOrderItems.reduce((t, it) => t + it.quantity, 0)
                                        const totalSelectedQty = splitOrderItems.reduce((t, it) => t + (it.selected ? it.selectedQuantity : 0), 0)
                                        const isAllFullySelected = totalSelectedQty > 0 && totalSelectedQty === totalOriginalQty
                                        return isAllFullySelected ? "Completar Pedido" : "Dividir Pedido"
                                    })()}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal para subir comprobante */}
            {showComprobanteModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4">
                        <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                            <h3 className="text-lg font-medium mb-4 text-center">Subir comprobante de transferencia</h3>

                            <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                {comprobanteImagePreview ? (
                                    <div className="text-center">
                                        <img
                                            src={comprobanteImagePreview || "/placeholder.svg"}
                                            alt="Comprobante"
                                            className="h-32 object-contain mx-auto"
                                        />
                                        <button
                                            onClick={() => {
                                                setComprobanteImageFile(null)
                                                setComprobanteImagePreview("")
                                            }}
                                            className="mt-2 text-sm text-red-600 hover:text-red-500"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                ) : (
                                    <div className="space-y-1 text-center">
                                        <svg
                                            className="mx-auto h-12 w-12 text-gray-400"
                                            stroke="currentColor"
                                            fill="none"
                                            viewBox="0 0 48 48"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                        </svg>
                                        <div className="flex text-sm text-gray-600 justify-center">
                                            <label
                                                htmlFor="file-upload"
                                                className="relative cursor-pointer bg-white rounded-md font-medium text-[#2EC4B6] hover:text-[#23A399]"
                                            >
                                                <span>Subir archivo</span>
                                                <input
                                                    id="file-upload"
                                                    name="file-upload"
                                                    type="file"
                                                    className="sr-only"
                                                    accept="image/*"
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                                        const file = e.target.files?.[0]
                                                        if (file) {
                                                            setComprobanteImageFile(file)
                                                            setComprobanteImagePreview(URL.createObjectURL(file))
                                                        }
                                                    }}
                                                />
                                            </label>
                                            <p className="pl-1">o arrastrar y soltar</p>
                                        </div>
                                        <p className="text-xs text-gray-500">PNG, JPG, GIF hasta 10MB</p>
                                    </div>
                                )}
                            </div>

                            <div className="flex justify-end space-x-2 mt-6">
                                <button
                                    onClick={() => {
                                        setShowComprobanteModal(false)
                                        setComprobanteImageFile(null)
                                        setComprobanteImagePreview("")
                                    }}
                                    className="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400"
                                >
                                    Cancelar
                                </button>
                                <button
                                    onClick={handleUploadAndComplete}
                                    disabled={!comprobanteImageFile}
                                    className={`px-4 py-2 rounded text-white ${
                                        comprobanteImageFile ? "bg-[#FF6B35] hover:bg-[#D6492C]" : "bg-gray-400 cursor-not-allowed"
                                    }`}
                                >
                                    Subir y Completar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Payment Modal */}
            {showPaymentModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" aria-hidden="true">
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>

                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">
              &#8203;
            </span>

                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">Método de Pago</h3>

                                        <div className="mt-2 space-y-4">
                                            <div>
                                                <label className="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        className="form-radio"
                                                        name="paymentMethod"
                                                        value="cash"
                                                        checked={paymentMethod === "cash"}
                                                        onChange={() => setPaymentMethod("cash")}
                                                    />
                                                    <span className="ml-2 flex items-center">
                            <DollarSign size={18} className="mr-1 text-green-500" />
                            Efectivo
                          </span>
                                                </label>
                                            </div>

                                            <div>
                                                <label className="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        className="form-radio"
                                                        name="paymentMethod"
                                                        value="transfer"
                                                        checked={paymentMethod === "transfer"}
                                                        onChange={() => setPaymentMethod("transfer")}
                                                    />
                                                    <span className="ml-2 flex items-center">
                            <Smartphone size={18} className="mr-1 text-purple-500" />
                            Transferencia
                          </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={createOrder}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#FF6B35] text-base font-medium text-white hover:bg-[#D6492C] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    {editingOrder ? "Actualizar Pedido" : "Confirmar Pedido"}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowPaymentModal(false)}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            {showConfigModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white text-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 animate-fadeIn">

                        {/* HEADER */}
                        <div className="flex items-center justify-between mb-5">
                            <h2 className="text-xl font-semibold">
                                🖨️ Configuración de Impresión
                            </h2>
                            <button
                                onClick={() => setShowConfigModal(false)}
                                className="text-gray-400 hover:text-gray-600 text-lg"
                            >
                                ✕
                            </button>
                        </div>

                        {/* SWITCH */}
                        <div className="flex items-center justify-between mb-6 p-3 rounded-lg border">
                            <span className="font-medium">Imprimir tickets</span>

                            <label className="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    className="sr-only peer"
                                    checked={config.imprime_ticket === 1}
                                    onChange={(e) => {
                                        const value = e.target.checked ? 1 : 0;
                                        setConfig({
                                            imprime_ticket: value,
                                            formato_ticket: value === 0 ? null : config.formato_ticket
                                        });
                                    }}
                                />
                                <div className="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-[#FF6B35] transition"></div>
                                <div className="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                            </label>
                        </div>

                        {/* FORMATO */}
                        <div className={`mb-6 p-3 rounded-lg border ${config.imprime_ticket === 0 ? "opacity-50" : ""}`}>
                            <p className="mb-3 font-medium">Formato del ticket</p>

                            <div className="space-y-2">
                                <label className="flex items-center justify-between p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                                    <span>58mm (Compacto)</span>
                                    <input
                                        type="radio"
                                        disabled={config.imprime_ticket === 0}
                                        checked={config.formato_ticket === 1}
                                        onChange={() => setConfig({ ...config, formato_ticket: 1 })}
                                    />
                                </label>

                                <label className="flex items-center justify-between p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                                    <span>80mm (Más amplio)</span>
                                    <input
                                        type="radio"
                                        disabled={config.imprime_ticket === 0}
                                        checked={config.formato_ticket === 2}
                                        onChange={() => setConfig({ ...config, formato_ticket: 2 })}
                                    />
                                </label>
                            </div>
                        </div>

                        {/* BOTONES */}
                        <div className="flex justify-end space-x-2">
                            <button
                                onClick={() => setShowConfigModal(false)}
                                className="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-100 transition"
                            >
                                Cancelar
                            </button>

                            <button
                                onClick={async () => {
                                    try {
                                        if (config.imprime_ticket === 1 && !config.formato_ticket) {
                                            showToast("Debes seleccionar un formato de ticket", "warning");
                                            return;
                                        }

                                        const configToSave = {
                                            imprime_ticket: config.imprime_ticket,
                                            formato_ticket: config.imprime_ticket === 1 ? config.formato_ticket : null
                                        };

                                        await configuracion.update(configToSave);

                                        const fresh = await configuracion.getConfig();
                                        setConfig({
                                            imprime_ticket: fresh.imprime_ticket ?? 0,
                                            formato_ticket: fresh.formato_ticket ?? null
                                        });

                                        showToast("Configuración guardada correctamente", "success");

                                        setShowConfigModal(false);
                                    } catch (error) {
                                        console.error("Error al guardar configuración:", error);
                                        showToast(
                                            getApiErrorMessage(error, "Error al guardar la configuración"),
                                            "error"
                                        );
                                    }
                                }}
                                className="px-4 py-2 rounded-lg bg-[#FF6B35] text-white hover:bg-[#e55a2b] transition"
                            >
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            )}
            <ConfirmModal
                open={orderToCancel !== null}
                title="Anular pedido"
                message={
                    <>
                        ¿Estás seguro de que querés anular este pedido?
                        <br />
                        <span className="text-red-500 font-medium">
                Esta acción no se puede deshacer.
            </span>
                    </>
                }
                confirmText="Sí, anular"
                confirmColor="red"
                onConfirm={confirmCancelOrder}
                onCancel={() => setOrderToCancel(null)}
            />
        </div>
    );
};

export default OrdersPage;
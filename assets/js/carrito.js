// ============================================================
// 🛒 SISTEMA DE CARRITO DE COMPRAS CON SESSIONSTORAGE
// ============================================================
// 
// 📌 IMPORTANTE: Usa sessionStorage en lugar de localStorage
//    - El carrito es específico por navegador y sesión
//    - Se borra automáticamente al cerrar la pestaña/ventana
//    - Cada navegador tiene su propio carrito independiente
// 
// 🔒 MEDIDAS DE SEGURIDAD IMPLEMENTADAS:
// 
// 1. HASH DE INTEGRIDAD: Detecta si el carrito ha sido modificado manualmente
//    en el sessionStorage. Si se detecta una modificación, el carrito se limpia
//    automáticamente.
// 
// 2. VALIDACIÓN DE PRECIOS: Valida que los precios estén dentro de rangos
//    razonables (0.01 - 999999.99) antes de guardar o enviar.
// 
// 3. PROTECCIÓN CONTRA MANIPULACIÓN: Los precios no pueden ser modificados
//    después de agregar un producto al carrito.
// 
// 4. VALIDACIÓN ANTES DE ENVIAR: Verifica la integridad del carrito antes
//    de procesar el pedido.
// 
// ⚠️ IMPORTANTE: Estas medidas son solo una capa de protección del lado del cliente.
//    El SERVIDOR (API) DEBE validar siempre:
//    - Que los precios coinciden con la base de datos
//    - Que los productos existen y están disponibles
//    - Que las cantidades son válidas
//    - Recalcular el total usando los precios del servidor
// 
// ============================================================

const Carrito = {
    // Usar sessionStorage en lugar de localStorage para que sea específico por sesión
    // sessionStorage se borra automáticamente al cerrar la pestaña/ventana
    storage: sessionStorage, // Cambiar a sessionStorage
    
    // Clave para sessionStorage
    STORAGE_KEY: 'compusisgt_carrito',
    // Clave para timestamp de expiración
    TIMESTAMP_KEY: 'compusisgt_carrito_timestamp',
    // Clave para hash de integridad
    HASH_KEY: 'compusisgt_carrito_hash',
    // Tiempo de expiración en horas (1 hora)
    EXPIRACION_HORAS: 1,
    // Bandera para evitar recursión infinita
    _limpiando: false,
    // Precio mínimo y máximo permitidos (protección básica)
    PRECIO_MINIMO: 0.01,
    PRECIO_MAXIMO: 999999.99,
    CANTIDAD_MAXIMA: 99,
    MENSAJE_RESERVA: 'Producto bajo reserva. Nos comunicaremos contigo para confirmar disponibilidad.',

    normalizarPresentacion(producto, precioBase) {
        if (window.LMProductPresentations && typeof window.LMProductPresentations.normalizeCartPresentation === 'function') {
            return window.LMProductPresentations.normalizeCartPresentation(producto, precioBase);
        }

        return {
            presentacion: producto.presentacion || 'UNIDAD',
            tipo_presentacion: producto.tipo_presentacion || 'unidad',
            precio_presentacion: parseFloat(producto.precio_presentacion || precioBase || 0),
            stock_presentacion: producto.stock_presentacion ?? producto.stock ?? null,
            cantidadpresentacion: parseInt(producto.cantidadpresentacion || 1, 10) || 1
        };
    },

    obtenerClaveProducto(item) {
        if (item?.cart_key) {
            return String(item.cart_key);
        }

        if (window.LMProductPresentations && typeof window.LMProductPresentations.cartKey === 'function') {
            return window.LMProductPresentations.cartKey(item);
        }

        const id = String(item?.idarticulo ?? item?.id ?? '').trim();
        const tipo = String(item?.tipo_presentacion || item?.presentacion || 'unidad')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '') || 'unidad';

        return `${id}::${tipo}`;
    },

    itemCoincideConClave(item, clave) {
        const claveTexto = String(clave ?? '').trim();
        if (!claveTexto) {
            return false;
        }

        if (String(item?.cart_key ?? '') === claveTexto || this.obtenerClaveProducto(item) === claveTexto) {
            return true;
        }

        return !claveTexto.includes('::') && String(item?.idarticulo ?? '') === claveTexto;
    },

    obtenerStockPresentacion(item) {
        if (item?.stock_presentacion === null || item?.stock_presentacion === undefined || item?.stock_presentacion === '') {
            return null;
        }

        const stock = Number(String(item.stock_presentacion).replace(',', '.'));
        return Number.isFinite(stock) ? Math.max(0, stock) : null;
    },

    requiereReservaPorStock(stockPresentacion, cantidad) {
        if (stockPresentacion === null || stockPresentacion === undefined) {
            return false;
        }

        const cantidadNum = parseInt(cantidad, 10);
        return Number.isInteger(cantidadNum) && cantidadNum >= 1 && cantidadNum > stockPresentacion;
    },

    // ============================================================
    // GENERAR HASH DE INTEGRIDAD DEL CARRITO
    // ============================================================
    generarHash(carrito) {
        // Crear una cadena única basada en los datos del carrito
        const datos = JSON.stringify(carrito.map(item => ({
            id: item.idarticulo,
            presentacion: item.tipo_presentacion || item.presentacion || 'unidad',
            precio: parseFloat(item.precio || 0).toFixed(2),
            cantidad: String(item.cantidad ?? '1').trim()
        })).sort((a, b) => String(a.id + a.presentacion).localeCompare(String(b.id + b.presentacion))));
        
        // Hash simple pero efectivo (no es criptográficamente seguro, pero detecta cambios)
        let hash = 0;
        for (let i = 0; i < datos.length; i++) {
            const char = datos.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convertir a entero de 32 bits
        }
        return Math.abs(hash).toString(36);
    },

    generarHashLegacy(carrito) {
        const datos = JSON.stringify(carrito.map(item => ({
            id: item.idarticulo,
            precio: parseFloat(item.precio || 0).toFixed(2),
            cantidad: String(item.cantidad ?? '1').trim()
        })).sort((a, b) => a.id - b.id));

        let hash = 0;
        for (let i = 0; i < datos.length; i++) {
            const char = datos.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(36);
    },

    // ============================================================
    // VERIFICAR INTEGRIDAD DEL CARRITO
    // ============================================================
    verificarIntegridad() {
        try {
            const carrito = this.storage.getItem(this.STORAGE_KEY);
            const hashGuardado = this.storage.getItem(this.HASH_KEY);
            
            if (!carrito) {
                return true; // Carrito vacío es válido
            }
            
            const carritoParsed = JSON.parse(carrito);
            const hashCalculado = this.generarHash(carritoParsed);
            const hashLegacy = this.generarHashLegacy(carritoParsed);
            
            if (hashGuardado !== hashCalculado && hashGuardado !== hashLegacy) {
                console.warn('⚠️ ADVERTENCIA: El carrito ha sido modificado. Se limpiará por seguridad.');
                this.limpiarCarrito();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Carrito modificado',
                        text: 'Se detectó una modificación no autorizada del carrito. Por seguridad, se ha limpiado.',
                        confirmButtonText: 'Aceptar'
                    });
                }
                return false;
            }
            
            return true;
        } catch (error) {
            console.error('Error al verificar integridad:', error);
            return false;
        }
    },

    // ============================================================
    // OBTENER CARRITO DESDE LOCALSTORAGE (con verificación de expiración e integridad)
    // ============================================================
    obtenerCarrito(skipExpirationCheck = false, skipIntegrityCheck = false) {
        try {
            // Si estamos limpiando, retornar array vacío sin verificar expiración
            if (this._limpiando) {
                return [];
            }

            // Verificar integridad del carrito (a menos que se omita)
            if (!skipIntegrityCheck && !this.verificarIntegridad()) {
                return [];
            }

            // Verificar si el carrito ha expirado (solo si no se omite la verificación)
            if (!skipExpirationCheck && this.carritoExpirado()) {
                this.limpiarCarrito();
                return [];
            }

            const carrito = this.storage.getItem(this.STORAGE_KEY);
            return carrito ? JSON.parse(carrito) : [];
        } catch (error) {
            console.error('Error al obtener carrito:', error);
            return [];
        }
    },

    // ============================================================
    // VERIFICAR SI EL CARRITO HA EXPIRADO
    // ============================================================
    carritoExpirado() {
        try {
            const timestamp = this.storage.getItem(this.TIMESTAMP_KEY);
            
            // Si no hay timestamp, el carrito es antiguo (sin sistema de expiración)
            // Lo consideramos expirado y lo limpiamos
            if (!timestamp) {
                return true;
            }

            const fechaCreacion = new Date(parseInt(timestamp));
            const fechaActual = new Date();
            // Calcular horas transcurridas en lugar de días
            const horasTranscurridas = (fechaActual - fechaCreacion) / (1000 * 60 * 60);

            return horasTranscurridas >= this.EXPIRACION_HORAS;
        } catch (error) {
            console.error('Error al verificar expiración del carrito:', error);
            return true; // En caso de error, considerar expirado
        }
    },

    // ============================================================
    // VALIDAR PRECIO DEL PRODUCTO
    // ============================================================
    validarPrecio(precio) {
        const precioNum = parseFloat(precio);
        if (isNaN(precioNum) || precioNum < this.PRECIO_MINIMO || precioNum > this.PRECIO_MAXIMO) {
            console.error('Precio invalido detectado:', precio);
            return false;
        }
        return true;
    },

    validarItemCarrito(item) {
        if (!item || typeof item !== 'object') {
            return { valido: false, mensaje: 'Se detecto un producto invalido en el carrito.' };
        }

        const idarticulo = parseInt(item.idarticulo, 10);
        if (!Number.isInteger(idarticulo) || idarticulo <= 0) {
            return { valido: false, mensaje: 'Se detecto un identificador de producto invalido.' };
        }

        const nombre = typeof item.nombre === 'string' ? item.nombre.trim() : '';
        if (!nombre || nombre.length > 150) {
            return { valido: false, mensaje: 'Se detecto un nombre de producto invalido.' };
        }

        if (!this.validarPrecio(item.precio)) {
            return { valido: false, mensaje: `El producto "${nombre}" tiene un precio invalido. Por favor, recarga la pagina.` };
        }

        const cantidadTexto = String(item.cantidad ?? '1').trim();
        if (!/^\d+$/.test(cantidadTexto)) {
            return { valido: false, mensaje: `La cantidad del producto "${nombre}" no es valida.` };
        }

        const cantidad = parseInt(cantidadTexto, 10);
        if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > this.CANTIDAD_MAXIMA) {
            return { valido: false, mensaje: `La cantidad del producto "${nombre}" no es valida.` };
        }

        const stockPresentacion = this.obtenerStockPresentacion(item);
        if (stockPresentacion !== null && stockPresentacion <= 0) {
            return { valido: false, mensaje: `El producto "${nombre}" no tiene stock disponible.` };
        }

        if (stockPresentacion !== null && cantidad > stockPresentacion) {
            return {
                valido: false,
                mensaje: `La cantidad solicitada de "${nombre}" supera el stock disponible.`
            };
        }

        const presentacion = typeof item.presentacion === 'string' ? item.presentacion.trim() : 'UNIDAD';
        if (presentacion.length > 60) {
            return { valido: false, mensaje: `La presentacion del producto "${nombre}" no es valida.` };
        }

        return { valido: true, mensaje: 'Producto valido' };
    },

    // ============================================================
    // GUARDAR CARRITO EN LOCALSTORAGE (con timestamp y hash de integridad)
    // ============================================================
    guardarCarrito(carrito) {
        try {
            // Validar precios antes de guardar
            for (let item of carrito) {
                const validacionItem = this.validarItemCarrito(item);
                if (!validacionItem.valido) {
                    console.error('Intento de guardar producto con datos invalidos:', item);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de validacion',
                            text: validacionItem.mensaje,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                    return false;
                }
            }
            
            // Generar hash de integridad
            const hash = this.generarHash(carrito);
            
            this.storage.setItem(this.STORAGE_KEY, JSON.stringify(carrito));
            // Guardar timestamp actual para control de expiración
            this.storage.setItem(this.TIMESTAMP_KEY, Date.now().toString());
            // Guardar hash de integridad
            this.storage.setItem(this.HASH_KEY, hash);
            
            this.actualizarContador();
            return true;
        } catch (error) {
            console.error('Error al guardar carrito:', error);
            return false;
        }
    },

    // ============================================================
    // AGREGAR PRODUCTO AL CARRITO (con validación de precio)
    // ============================================================
    agregarProducto(producto, cantidad = 1) {
        // Validar cantidad
        const cantidadTexto = String(cantidad ?? '1').trim();
        if (!/^\d+$/.test(cantidadTexto)) {
            this.mostrarNotificacion(`Ingrese una cantidad entera entre 1 y ${this.CANTIDAD_MAXIMA}.`);
            return false;
        }

        cantidad = parseInt(cantidadTexto, 10);
        if (cantidad < 1 || cantidad > this.CANTIDAD_MAXIMA) {
            this.mostrarNotificacion(`Ingrese una cantidad entre 1 y ${this.CANTIDAD_MAXIMA}.`);
            return false;
        }
        
        const presentacion = this.normalizarPresentacion(producto, producto.precio || producto.precio_venta || 0);
        const precio = parseFloat(presentacion.precio_presentacion || producto.precio || producto.precio_venta || 0);
        if (!this.validarPrecio(precio)) {
            console.error('⚠️ No se puede agregar producto con precio inválido');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El precio del producto no es válido. Por favor, recarga la página.',
                    confirmButtonText: 'Aceptar'
                });
            }
            return false;
        }

        const stockPresentacion = this.obtenerStockPresentacion(presentacion);
        if (stockPresentacion !== null && stockPresentacion <= 0) {
            this.mostrarNotificacion('Producto no disponible por falta de stock.');
            return false;
        }

        if (stockPresentacion !== null && cantidad > stockPresentacion) {
            this.mostrarNotificacion(`Cantidad no disponible. Stock actual: ${stockPresentacion}.`);
            return false;
        }

        let requiereReserva = false;

        const carrito = this.obtenerCarrito();
        const idarticulo = producto.idarticulo || producto.id;
        const claveProducto = this.obtenerClaveProducto({
            idarticulo,
            presentacion: presentacion.presentacion,
            tipo_presentacion: presentacion.tipo_presentacion
        });

        // Buscar si el producto ya existe en el carrito
        const productoExistente = carrito.find(item => this.obtenerClaveProducto(item) === claveProducto);

        if (productoExistente) {
            // Si existe, aumentar la cantidad (pero mantener el precio original)
            const cantidadActual = parseInt(productoExistente.cantidad || 1, 10);
            const cantidadFinal = cantidadActual + cantidad;
            if (cantidadFinal > this.CANTIDAD_MAXIMA) {
                this.mostrarNotificacion(`La cantidad maxima por producto es ${this.CANTIDAD_MAXIMA}.`);
                return false;
            }

            if (stockPresentacion !== null && cantidadFinal > stockPresentacion) {
                this.mostrarNotificacion(`Cantidad no disponible. Stock actual: ${stockPresentacion}.`);
                return false;
            }

            productoExistente.cantidad = cantidadFinal;
            productoExistente.requiere_reserva = requiereReserva;
            // IMPORTANTE: No permitir cambiar el precio de productos existentes
            // El precio se mantiene como estaba cuando se agregó por primera vez
        } else {
            // Si no existe, agregarlo
            const nuevoProducto = {
                idarticulo: idarticulo,
                nombre: producto.nombre || '',
                precio: precio, // Precio validado
                imagen: producto.imagen || '',
                cantidad: cantidad,
                descripcion: producto.descripcion || '',
                presentacion: presentacion.presentacion,
                tipo_presentacion: presentacion.tipo_presentacion,
                precio_presentacion: precio,
                stock_presentacion: stockPresentacion,
                cantidadpresentacion: presentacion.cantidadpresentacion || 1,
                requiere_reserva: requiereReserva,
                cart_key: claveProducto
            };
            carrito.push(nuevoProducto);
        }

        if (!this.guardarCarrito(carrito)) {
            return false;
        }
        
        // Mostrar notificación
        this.mostrarNotificacion(requiereReserva ? this.MENSAJE_RESERVA : 'Producto agregado al carrito');
        
        return true;
    },

    // ============================================================
    // ELIMINAR PRODUCTO DEL CARRITO
    // ============================================================
    eliminarProducto(idarticulo) {
        const carrito = this.obtenerCarrito();
        const nuevoCarrito = carrito.filter(item => !this.itemCoincideConClave(item, idarticulo));
        this.guardarCarrito(nuevoCarrito);
        this.mostrarNotificacion('Producto eliminado del carrito');
        return true;
    },

    // ============================================================
    // ACTUALIZAR CANTIDAD DE UN PRODUCTO (sin permitir cambiar precio)
    // ============================================================
    actualizarCantidad(idarticulo, nuevaCantidad) {
        const cantidadTexto = String(nuevaCantidad ?? '').trim();
        if (!/^\d+$/.test(cantidadTexto)) {
            this.mostrarNotificacion(`Ingrese una cantidad entera entre 1 y ${this.CANTIDAD_MAXIMA}.`);
            return false;
        }

        const cantidadNormalizada = parseInt(cantidadTexto, 10);
        if (cantidadNormalizada < 1) {
            return this.eliminarProducto(idarticulo);
        }

        if (cantidadNormalizada > this.CANTIDAD_MAXIMA) {
            this.mostrarNotificacion(`La cantidad maxima por producto es ${this.CANTIDAD_MAXIMA}.`);
            return false;
        }

        const carrito = this.obtenerCarrito();
        const producto = carrito.find(item => this.itemCoincideConClave(item, idarticulo));

        if (producto) {
            // Validar que el precio no haya sido modificado
            const precioOriginal = parseFloat(producto.precio || 0);
            if (!this.validarPrecio(precioOriginal)) {
                console.error('⚠️ Precio inválido detectado al actualizar cantidad');
                this.eliminarProducto(idarticulo);
                return false;
            }

            const stockPresentacion = this.obtenerStockPresentacion(producto);
            if (stockPresentacion !== null && stockPresentacion <= 0) {
                this.mostrarNotificacion('Producto no disponible por falta de stock.');
                return false;
            }

            if (stockPresentacion !== null && cantidadNormalizada > stockPresentacion) {
                this.mostrarNotificacion(`Cantidad no disponible. Stock actual: ${stockPresentacion}.`);
                return false;
            }

            producto.cantidad = cantidadNormalizada;
            producto.requiere_reserva = false;
            // Asegurar que el precio no se modifique
            producto.precio = precioOriginal;
            
            if (!this.guardarCarrito(carrito)) {
                return false;
            }

            return true;
        }
        return false;
    },

    // ============================================================
    // OBTENER CANTIDAD TOTAL DE PRODUCTOS
    // ============================================================
    obtenerCantidadTotal() {
        // Si estamos limpiando, retornar 0 sin verificar expiración
        if (this._limpiando) {
            return 0;
        }
        const carrito = this.obtenerCarrito(true); // Omitir verificación de expiración para evitar recursión
        return carrito.reduce((total, item) => {
            const cantidadTexto = String(item.cantidad ?? '1').trim();
            const cantidad = /^\d+$/.test(cantidadTexto) ? parseInt(cantidadTexto, 10) : 1;
            return total + Math.min(Math.max(cantidad, 1), this.CANTIDAD_MAXIMA);
        }, 0);
    },

    // ============================================================
    // OBTENER SUBTOTAL DEL CARRITO (con validación)
    // ============================================================
    obtenerSubtotal() {
        // Si estamos limpiando, retornar 0 sin verificar expiración
        if (this._limpiando) {
            return 0;
        }
        const carrito = this.obtenerCarrito(true, true); // Omitir verificaciones para evitar recursión
        return carrito.reduce((total, item) => {
            const precio = parseFloat(item.precio || 0);
            const cantidadTexto = String(item.cantidad ?? '1').trim();
            const cantidad = /^\d+$/.test(cantidadTexto) ? parseInt(cantidadTexto, 10) : 1;
            // Validar precio antes de sumar
            if (this.validarPrecio(precio) && cantidad >= 1 && cantidad <= this.CANTIDAD_MAXIMA) {
                return total + (precio * cantidad);
            }
            return total;
        }, 0);
    },

    // ============================================================
    // VALIDAR CARRITO ANTES DE ENVIAR (para procesar pedido)
    // ============================================================
    validarCarritoParaEnvio() {
        const carrito = this.obtenerCarrito();
        
        if (!carrito || carrito.length === 0) {
            return { valido: false, mensaje: 'El carrito está vacío' };
        }
        
        // Verificar integridad
        if (!this.verificarIntegridad()) {
            return { valido: false, mensaje: 'El carrito ha sido modificado. Por favor, recarga la página.' };
        }
        
        // Validar cada producto
        for (let item of carrito) {
            const validacionItem = this.validarItemCarrito(item);
            if (!validacionItem.valido) {
                return {
                    valido: false,
                    mensaje: validacionItem.mensaje
                };
            }
        }
        
        return { valido: true, mensaje: 'Carrito valido' };
    },

    // ============================================================
    // LIMPIAR CARRITO (incluye timestamp y hash)
    // ============================================================
    limpiarCarrito() {
        // Activar bandera para evitar recursión
        this._limpiando = true;
        
        this.storage.removeItem(this.STORAGE_KEY);
        this.storage.removeItem(this.TIMESTAMP_KEY);
        this.storage.removeItem(this.HASH_KEY);
        
        // Actualizar contador (ahora retornará 0 porque _limpiando está activo)
        this.actualizarContador();
        
        // Desactivar bandera después de un breve delay
        setTimeout(() => {
            this._limpiando = false;
        }, 100);
        
        return true;
    },

    // ============================================================
    // OBTENER HORAS RESTANTES ANTES DE EXPIRAR
    // ============================================================
    obtenerHorasRestantes() {
        try {
            const timestamp = this.storage.getItem(this.TIMESTAMP_KEY);
            if (!timestamp) {
                return 0;
            }

            const fechaCreacion = new Date(parseInt(timestamp));
            const fechaActual = new Date();
            const horasTranscurridas = (fechaActual - fechaCreacion) / (1000 * 60 * 60);
            const horasRestantes = Math.max(0, this.EXPIRACION_HORAS - horasTranscurridas);

            return Math.floor(horasRestantes * 10) / 10; // Redondear a 1 decimal
        } catch (error) {
            console.error('Error al calcular horas restantes:', error);
            return 0;
        }
    },

    // ============================================================
    // ACTUALIZAR CONTADOR EN EL HEADER
    // ============================================================
    actualizarContador() {
        const cantidad = this.obtenerCantidadTotal();
        
        // Actualizar contador en el header superior (si existe)
        const cartLinkHeader = document.querySelector('.middle-header-optional a[href="cart.php"]');
        if (cartLinkHeader) {
            let contador = cartLinkHeader.querySelector('.cart-count');
            if (!contador) {
                contador = document.createElement('span');
                contador.className = 'cart-count';
                cartLinkHeader.appendChild(contador);
            }
            
            if (cantidad > 0) {
                contador.textContent = cantidad;
                contador.style.display = 'flex';
            } else {
                contador.textContent = '';
                contador.style.display = 'none';
            }
        }
        
        // Actualizar contador en el navbar principal
        const cartLinkNavbar = document.querySelector('.navbar-nav .cart-icon-navbar');
        if (cartLinkNavbar) {
            let contadorNavbar = cartLinkNavbar.querySelector('.cart-count-navbar');
            if (!contadorNavbar) {
                contadorNavbar = document.createElement('span');
                contadorNavbar.className = 'cart-count-navbar';
                cartLinkNavbar.appendChild(contadorNavbar);
            }
            
            if (cantidad > 0) {
                contadorNavbar.textContent = cantidad;
                contadorNavbar.style.display = 'flex';
            } else {
                contadorNavbar.textContent = '';
                contadorNavbar.style.display = 'none';
            }
        }
    },

    // ============================================================
    // MOSTRAR NOTIFICACIÓN
    // ============================================================
    mostrarNotificacion(mensaje) {
        // Si existe SweetAlert, usarlo
        if (typeof Swal !== 'undefined') {
            const icono = /^(Producto bajo reserva|Producto no disponible|Cantidad no disponible|Ingrese|La cantidad|El precio)/i.test(String(mensaje)) ? 'warning' : 'success';
            Swal.fire({
                icon: icono,
                title: mensaje,
                showConfirmButton: false,
                timer: 1500,
                toast: true,
                position: 'top-end'
            });
        }
    }
};

window.Carrito = Carrito;

// ============================================================
// FUNCIÓN GLOBAL PARA AGREGAR AL CARRITO
// ============================================================
function agregarAlCarrito(idarticulo, nombre, precio, imagen, descripcion = '', cantidad = 1, presentacion = null) {
    if (cantidad && typeof cantidad === 'object') {
        presentacion = cantidad;
        cantidad = 1;
    }

    if (typeof presentacion === 'string' && window.LMProductPresentations && typeof window.LMProductPresentations.getRegisteredPresentation === 'function') {
        presentacion = window.LMProductPresentations.getRegisteredPresentation(presentacion);
    }

    const producto = {
        idarticulo: idarticulo,
        nombre: nombre,
        precio: precio,
        imagen: imagen,
        descripcion: descripcion,
        ...(presentacion && typeof presentacion === 'object' ? presentacion : {})
    };
    
    return Carrito.agregarProducto(producto, cantidad);
}

// ============================================================
// AGREGAR AL CARRITO DESDE EL MODAL
// ============================================================
function agregarAlCarritoDesdeModal() {
    const titulo = document.getElementById('mp-titulo')?.innerText || '';
    const precio = document.getElementById('mp-precio')?.innerText.replace('Q', '') || '0';
    const sku = document.getElementById('mp-sku')?.innerText || '';
    const imagen = document.getElementById('mp-imagen-principal')?.src || '';
    const descripcion = document.getElementById('mp-descripcion')?.innerText || '';
    const cantidad = document.getElementById('mp-cantidad')?.value || '1';
    
    if (!sku) {
        console.error('No se pudo obtener el SKU del producto');
        return;
    }
    
    agregarAlCarrito(sku, titulo, precio, imagen, descripcion, cantidad);
    
    // Cerrar el modal después de agregar
    setTimeout(() => {
        cerrarModal();
    }, 500);
}

// ============================================================
// INICIALIZAR CONTADOR AL CARGAR LA PÁGINA
// Y VERIFICAR EXPIRACIÓN AUTOMÁTICAMENTE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Verificar y limpiar carrito expirado al cargar la página
    if (Carrito.carritoExpirado()) {
        Carrito.limpiarCarrito();
    }
    
    Carrito.actualizarContador();
});



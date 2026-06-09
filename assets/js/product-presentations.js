(function (window) {
    "use strict";

    const EXCLUDED_NAMES = new Set(["MAYORISTA", "MINOREO", "MINORISTA", "MENUDEO"]);
    const cartRegistry = {};
    const DIRECT_PRESENTATIONS = [
        { index: "01", tipo: "unidad", nameKeys: ["nombre_01"], stockKeys: ["stock_unidad", "stock_01", "stock"], priceKeys: ["precio_unidad", "precio_01", "precio_venta", "precio"] },
        { index: "02", tipo: "blister", nameKeys: ["nombre_02"], stockKeys: ["stock_blister", "stock_02"], priceKeys: ["precio_blister", "precio_02"] },
        { index: "03", tipo: "caja", nameKeys: ["nombre_03"], stockKeys: ["stock_caja", "stock_03"], priceKeys: ["precio_caja", "precio_03"] },
        { index: "04", tipo: "fardo", nameKeys: ["nombre_04"], stockKeys: ["stock_fardo", "stock_04"], priceKeys: ["precio_fardo", "precio_04"] },
        { index: "05", tipo: "sacos", nameKeys: ["nombre_05"], stockKeys: ["stock_sacos", "stock_05"], priceKeys: ["precio_sacos", "precio_05"] },
        { index: "06", tipo: "paquete", nameKeys: ["nombre_06"], stockKeys: ["stock_paquete", "stock_06"], priceKeys: ["precio_paquete", "precio_06"] },
    ];

    for (let i = 7; i <= 20; i += 1) {
        const index = String(i).padStart(2, "0");
        DIRECT_PRESENTATIONS.push({
            index,
            tipo: `presentacion_${index}`,
            nameKeys: [`nombre_${index}`],
            stockKeys: [`stock_${index}`],
            priceKeys: [`precio_${index}`],
        });
    }

    function normalizeName(value) {
        let text = String(value ?? "").trim();
        if (typeof text.normalize === "function") {
            text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }
        return text.replace(/\s+/g, " ").toUpperCase();
    }

    function displayName(value) {
        return String(value ?? "").replace(/\s+/g, " ").trim();
    }

    function toNumber(value, fallback = 0) {
        if (value === null || value === undefined || value === "") {
            return fallback;
        }

        const normalized = Number(String(value).replace(",", "."));
        return Number.isFinite(normalized) ? normalized : fallback;
    }

    function firstValue(product, keys) {
        for (const key of keys) {
            if (!Object.prototype.hasOwnProperty.call(product, key)) {
                continue;
            }

            const value = product[key];
            if (value !== null && value !== undefined && String(value).trim() !== "") {
                return value;
            }
        }

        return undefined;
    }

    function isExcludedName(name) {
        return EXCLUDED_NAMES.has(normalizeName(name));
    }

    function isValidPresentationName(name) {
        const normalized = normalizeName(name);
        return normalized !== "" && normalized !== "0" && normalized !== "PRIMARY" && !EXCLUDED_NAMES.has(normalized);
    }

    function normalizeTipo(tipo, nombre) {
        const raw = normalizeName(tipo || nombre).toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "");
        return raw || "unidad";
    }

    function buildPresentation(data, fallbackTipo) {
        const nombre = displayName(data.nombre);
        if (!isValidPresentationName(nombre)) {
            return null;
        }

        const precio = toNumber(data.precio, 0);
        const stock = toNumber(data.stock, 0);

        if (precio <= 0 && stock <= 0) {
            return null;
        }

        return {
            nombre,
            tipo: normalizeTipo(data.tipo || fallbackTipo, nombre),
            stock,
            precio,
            disabled: stock <= 0 || precio <= 0,
        };
    }

    function dedupePresentations(presentations) {
        const seen = new Set();
        return presentations.filter((presentation) => {
            if (!presentation) {
                return false;
            }

            const key = normalizeName(`${presentation.tipo}:${presentation.nombre}`);
            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        });
    }

    function fromArray(product) {
        if (!Array.isArray(product.presentaciones)) {
            return [];
        }

        return product.presentaciones
            .map((item) => buildPresentation({
                nombre: item?.nombre ?? item?.presentacion,
                tipo: item?.tipo,
                stock: item?.stock ?? item?.existencia ?? item?.stocksucursal,
                precio: item?.precio ?? item?.precio_venta,
            }, item?.tipo))
            .filter(Boolean);
    }

    function fromFlatFields(product) {
        return DIRECT_PRESENTATIONS
            .map((definition) => buildPresentation({
                nombre: firstValue(product, definition.nameKeys),
                tipo: definition.tipo,
                stock: firstValue(product, definition.stockKeys),
                precio: firstValue(product, definition.priceKeys),
            }, definition.tipo))
            .filter(Boolean);
    }

    function extract(product) {
        if (!product || typeof product !== "object") {
            return [];
        }

        return dedupePresentations([...fromArray(product), ...fromFlatFields(product)]);
    }

    function defaultUnit(product) {
        const source = product && typeof product === "object" ? product : {};
        const rawName = firstValue(source, ["nombre_01"]);
        const nombre = isValidPresentationName(rawName) && !isExcludedName(rawName) ? displayName(rawName) : "UNIDAD";
        const precio = toNumber(firstValue(source, ["precio_unidad", "precio_01", "precio_venta", "precio"]) ?? source.precio, 0);
        const stockValue = firstValue(source, ["stock_unidad", "stock_01", "stock", "stocksucursal", "existencia"]);
        const stock = stockValue === undefined ? null : Math.max(0, toNumber(stockValue, 0));

        return {
            nombre,
            tipo: "unidad",
            stock,
            precio,
            disabled: precio <= 0 || (stock !== null && stock <= 0),
        };
    }

    function normalizeCartPresentation(product, fallbackPrice) {
        const source = product && typeof product === "object" ? product : {};
        const explicit = buildPresentation({
            nombre: source.presentacion || source.nombre_presentacion,
            tipo: source.tipo_presentacion || source.tipo,
            stock: source.stock_presentacion ?? source.stock,
            precio: source.precio_presentacion ?? source.precio ?? fallbackPrice,
        }, source.tipo_presentacion || source.tipo);

        const base = explicit || defaultUnit({
            ...source,
            precio_venta: fallbackPrice ?? source.precio_venta ?? source.precio,
        });

        return {
            presentacion: base.nombre,
            tipo_presentacion: base.tipo,
            precio_presentacion: toNumber(base.precio, toNumber(fallbackPrice, 0)),
            stock_presentacion: base.stock,
            cantidadpresentacion: Math.max(1, toNumber(source.cantidadpresentacion, 1)),
        };
    }

    function buildCartPayload(product) {
        const unit = defaultUnit(product || {});
        return {
            presentacion: unit.nombre,
            tipo_presentacion: unit.tipo,
            precio_presentacion: unit.precio,
            stock_presentacion: unit.stock,
            cantidadpresentacion: 1,
        };
    }

    function registerForCart(product) {
        const id = String(product?.idarticulo ?? product?.id ?? "").trim();
        const key = `${id || "producto"}-${Object.keys(cartRegistry).length + 1}`;
        cartRegistry[key] = buildCartPayload(product || {});
        return key;
    }

    function getRegisteredPresentation(key) {
        return cartRegistry[String(key ?? "")] || null;
    }

    function cartKey(item) {
        const id = String(item?.idarticulo ?? item?.id ?? "").trim();
        const tipo = normalizeTipo(item?.tipo_presentacion || item?.presentacion || "unidad", "unidad");
        return `${id}::${tipo}`;
    }

    window.LMProductPresentations = {
        extract,
        defaultUnit,
        normalizeCartPresentation,
        registerForCart,
        getRegisteredPresentation,
        normalizeName,
        isExcludedName,
        toNumber,
        cartKey,
    };
})(window);

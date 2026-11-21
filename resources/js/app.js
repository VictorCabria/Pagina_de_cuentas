// Halo de luz que sigue al cursor
const spotlight = document.querySelector('.spotlight');
if (spotlight && window.matchMedia('(pointer: fine)').matches) {
    window.addEventListener('pointermove', (e) => {
        spotlight.style.setProperty('--x', `${e.clientX}px`);
        spotlight.style.setProperty('--y', `${e.clientY}px`);
    });
}

// Barra de navegación: fondo al hacer scroll
const navbar = document.querySelector('[data-navbar]');
if (navbar) {
    const onScroll = () => navbar.classList.toggle('is-scrolled', window.scrollY > 20);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

// Menú móvil
const menuToggle = document.querySelector('[data-menu-toggle]');
const menu = document.querySelector('[data-menu]');
if (menuToggle && menu) {
    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        menuToggle.setAttribute('aria-expanded', String(open));
    };
    menuToggle.addEventListener('click', () => setOpen(menu.classList.contains('hidden')));
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
}

// Resalta en la barra de navegación la sección visible
const navLinks = document.querySelectorAll('.nav-link[data-section]');
if (navLinks.length) {
    const byId = new Map([...navLinks].map((link) => [link.dataset.section, link]));
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                navLinks.forEach((link) => link.classList.remove('is-active'));
                byId.get(entry.target.id)?.classList.add('is-active');
            });
        },
        { rootMargin: '-40% 0px -55% 0px' },
    );
    byId.forEach((_, id) => {
        const section = document.getElementById(id);
        if (section) observer.observe(section);
    });
}

// Texto que se escribe y borra solo (línea de terminal del hero)
const typing = document.querySelector('[data-typing]');
if (typing && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const words = JSON.parse(typing.dataset.typing);
    let word = 0;
    let chars = typing.textContent.length;
    let deleting = true;
    const tick = () => {
        const current = words[word];
        chars += deleting ? -1 : 1;
        typing.textContent = current.slice(0, chars);
        let delay = deleting ? 45 : 90;
        if (!deleting && chars === current.length) {
            deleting = true;
            delay = 1800;
        } else if (deleting && chars === 0) {
            deleting = false;
            word = (word + 1) % words.length;
            delay = 300;
        }
        setTimeout(tick, delay);
    };
    setTimeout(tick, 2200);
}

// Aparición suave de bloques al entrar en pantalla
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.1 },
);
document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));

// Vista previa de imágenes en formularios del panel
document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const target = document.getElementById(input.dataset.preview);
        const file = input.files?.[0];
        if (target && file) {
            target.src = URL.createObjectURL(file);
            target.classList.remove('hidden');
        }
    });
});

// Panel · formulario de plan: muestra el precio en COP que resultará de la tasa
const planForm = document.querySelector('[data-plan-form]');
if (planForm) {
    const rate = Number(planForm.dataset.rate);
    const usd = planForm.querySelector('[name="price_usd"]');
    const cop = planForm.querySelector('[name="price_cop"]');
    const preview = planForm.querySelector('[data-cop-preview]');
    const update = () => {
        const value = Number(usd.value);
        preview.textContent = !cop.value && value > 0
            ? `En pesos quedará en $${(Math.round((value * rate) / 100) * 100).toLocaleString('es-CO')} COP.`
            : '';
    };
    [usd, cop].forEach((input) => input.addEventListener('input', update));
    update();
}

// Panel · formulario de promoción: muestra solo el selector del alcance elegido
const promoForm = document.querySelector('[data-promo-form]');
if (promoForm) {
    const update = () => {
        const scope = promoForm.querySelector('[name="scope"]:checked')?.value;
        promoForm.querySelectorAll('[data-scope-target]').forEach((el) => {
            el.hidden = el.dataset.scopeTarget !== scope;
        });
    };
    promoForm.querySelectorAll('[name="scope"]').forEach((radio) => radio.addEventListener('change', update));
    update();
}

// Tienda: filtro por categoría y buscador del catálogo
const catalog = document.querySelector('[data-catalog]');
if (catalog) {
    const cards = [...catalog.querySelectorAll('[data-service]')];
    const tabs = [...catalog.querySelectorAll('[data-filter]')];
    const search = document.querySelector('[data-search]');
    const empty = catalog.querySelector('[data-empty]');
    let category = 'all';

    const normalize = (text) => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();

    const apply = () => {
        const term = normalize(search?.value ?? '');
        let visible = 0;
        cards.forEach((card) => {
            const show = (category === 'all' || card.dataset.category === category) && card.dataset.name.includes(term);
            card.classList.toggle('hidden', !show);
            visible += show ? 1 : 0;
        });
        empty?.classList.toggle('hidden', visible > 0);
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => {
        category = tab.dataset.filter;
        tabs.forEach((other) => {
            other.classList.toggle('is-active', other === tab);
            other.setAttribute('aria-selected', String(other === tab));
        });
        apply();
    }));

    if (search) {
        search.closest('form')?.addEventListener('submit', (event) => {
            event.preventDefault();
            catalog.scrollIntoView();
        });
        search.addEventListener('input', apply);
    }
    apply();
}

// Tienda: el plan elegido actualiza el total
const picker = document.querySelector('[data-plan-picker]');
if (picker) {
    const total = picker.querySelector('span[data-total]');
    const update = () => {
        const selected = picker.querySelector('input[name="plan_id"]:checked');
        if (selected && total) total.textContent = selected.dataset.total;
    };
    picker.addEventListener('change', update);
    update();
}

// Widget de WhatsApp: mensaje de bienvenida automático y mini chat
const wa = document.querySelector('[data-wa]');
if (wa) {
    const panel = wa.querySelector('[data-wa-panel]');
    const teaser = wa.querySelector('[data-wa-teaser]');
    const toggle = wa.querySelector('[data-wa-toggle]');
    const badge = wa.querySelector('[data-wa-badge]');
    const typing = wa.querySelector('[data-wa-typing]');
    const message = wa.querySelector('[data-wa-message]');
    const iconChat = wa.querySelector('[data-wa-icon-chat]');
    const iconClose = wa.querySelector('[data-wa-icon-close]');

    // sessionStorage puede no estar disponible (modo privado): el widget funciona igual.
    const seen = {
        get: () => { try { return sessionStorage.getItem('wa-seen') === '1'; } catch { return false; } },
        set: () => { try { sessionStorage.setItem('wa-seen', '1'); } catch { /* sin almacenamiento */ } },
    };

    let greeted = false;
    const setOpen = (open) => {
        panel.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        iconChat.classList.toggle('scale-50', open);
        iconChat.classList.toggle('opacity-0', open);
        iconClose.classList.toggle('scale-50', !open);
        iconClose.classList.toggle('opacity-0', !open);
        teaser.classList.remove('is-open');
        if (!open) return;

        badge?.remove();
        seen.set();
        if (!greeted) {
            greeted = true;
            setTimeout(() => {
                typing.hidden = true;
                message.hidden = false;
            }, 1300);
        }
    };

    toggle.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
    wa.querySelector('[data-wa-close]').addEventListener('click', () => setOpen(false));
    wa.querySelector('[data-wa-open]').addEventListener('click', () => setOpen(true));
    wa.querySelector('[data-wa-dismiss]').addEventListener('click', () => {
        teaser.classList.remove('is-open');
        seen.set();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false);
    });

    // La primera vez en la sesión, saluda solo a los pocos segundos.
    if (!seen.get()) {
        setTimeout(() => {
            if (!panel.classList.contains('is-open')) teaser.classList.add('is-open');
        }, 3500);
    } else {
        badge?.remove();
    }
}

// Confirmación del pedido: abre WhatsApp con el pedido una sola vez
const orderSent = document.querySelector('[data-order-sent]');
if (orderSent) {
    const key = `order-opened:${location.pathname}`;
    let opened = false;
    try { opened = sessionStorage.getItem(key) === '1'; } catch { /* sin almacenamiento */ }
    if (!opened) {
        try { sessionStorage.setItem(key, '1'); } catch { /* sin almacenamiento */ }
        setTimeout(() => { window.location.href = orderSent.dataset.whatsappUrl; }, 1200);
    }
}

// Carrito: cambiar cantidades o quitar sin recargar (así no se borra lo que el cliente escribió)
const cartLines = document.querySelector('[data-cart-lines]');
if (cartLines) {
    const totalEl = document.querySelector('[data-cart-total]');
    const countEl = document.querySelector('[data-cart-count]');
    const submit = document.querySelector('[data-cart-submit]');

    const send = async (form, quantity) => {
        const body = new FormData(form);
        if (quantity !== undefined) body.set('quantity', quantity);
        const response = await fetch(form.action, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    };

    const apply = (line, data) => {
        totalEl.textContent = data.total;
        if (countEl) {
            countEl.textContent = data.count;
            countEl.classList.toggle('hidden', data.count === 0);
        }
        if (submit) submit.disabled = !data.canOrder;

        if (data.quantity === 0) {
            line.style.opacity = '0';
            line.style.transform = 'translateX(16px)';
            setTimeout(() => {
                line.remove();
                if (!cartLines.querySelector('[data-cart-line]')) location.reload();
            }, 300);
            return;
        }

        line.querySelector('[data-cart-quantity]').textContent = data.quantity;
        line.querySelector('[data-cart-subtotal]').textContent = data.subtotal;
        const original = line.querySelector('[data-cart-original]');
        if (original && data.original) original.textContent = data.original;
        line.querySelector('[data-cart-plus]').disabled = data.quantity >= data.max;
        // Mantiene correctos los formularios por si se usan sin JavaScript
        line.querySelector('[data-cart-step="-1"] [name="quantity"]').value = data.quantity - 1;
        line.querySelector('[data-cart-step="1"] [name="quantity"]').value = Math.min(data.quantity + 1, data.max);
    };

    cartLines.addEventListener('submit', async (event) => {
        const form = event.target;
        const line = form.closest('[data-cart-line]');
        if (!line) return;
        event.preventDefault();
        if (line.dataset.busy) return;
        line.dataset.busy = '1';
        line.classList.add('opacity-70');

        try {
            const current = Number(line.querySelector('[data-cart-quantity]').textContent);
            const data = form.hasAttribute('data-cart-remove')
                ? await send(form)
                : await send(form, Math.max(0, current + Number(form.dataset.cartStep)));
            apply(line, data);
        } catch {
            form.submit(); // si falla la conexión, se usa el envío normal
        } finally {
            delete line.dataset.busy;
            line.classList.remove('opacity-70');
        }
    });
}

// Carrito: recuerda nombre y WhatsApp mientras se escriben
const orderForm = document.querySelector('[data-order-form]');
if (orderForm) {
    ['customer_name', 'customer_phone'].forEach((name) => {
        const input = orderForm.elements[name];
        const key = `checkout:${name}`;
        try {
            if (!input.value) input.value = localStorage.getItem(key) ?? '';
        } catch { /* sin almacenamiento */ }
        input.addEventListener('input', () => {
            try { localStorage.setItem(key, input.value); } catch { /* sin almacenamiento */ }
        });
    });
}

// Pide JSON al panel; null si falla (sin sesión, límite de consultas, sin conexión).
const getJson = async (url, params = {}) => {
    const target = new URL(url);
    target.search = new URLSearchParams(params);
    try {
        const response = await fetch(target, { headers: { Accept: 'application/json' } });
        return response.ok ? await response.json() : null;
    } catch {
        return null;
    }
};

// Panel · datos sensibles: llegan enmascarados y los reales se piden al servidor solo al usarlos.
// El ojo los muestra u oculta; copiar los manda al portapapeles sin mostrarlos.
const secretsCache = new Map();
const loadSecrets = (url) => {
    if (!secretsCache.has(url)) {
        secretsCache.set(url, getJson(url).then((data) => {
            if (!data) secretsCache.delete(url); // reintenta en el próximo clic
            return data;
        }));
    }
    return secretsCache.get(url);
};
const flashError = (button, message) => {
    button.title = message;
    button.classList.add('text-rose-400');
    setTimeout(() => button.classList.remove('text-rose-400'), 1500);
};
const swapIcons = (button, active, [idle, done]) => {
    button.querySelector(`[${idle}]`)?.classList.toggle('hidden', active);
    button.querySelector(`[${done}]`)?.classList.toggle('hidden', !active);
};

document.querySelectorAll('[data-secrets]').forEach((box) => {
    const url = box.dataset.secretsUrl;
    const targets = [...box.querySelectorAll('[data-secret]')];
    const eye = box.querySelector('[data-reveal]');
    let shown = false;

    eye?.addEventListener('click', async () => {
        if (shown) {
            targets.forEach((el) => {
                if (el instanceof HTMLInputElement) {
                    if (el.value === el.dataset.revealed) el.value = '';
                } else {
                    el.textContent = el.dataset.masked;
                }
            });
        } else {
            const data = await loadSecrets(url);
            if (!data) return flashError(eye, 'No se pudo cargar, intenta de nuevo');
            targets.forEach((el) => {
                const value = data[el.dataset.secret];
                if (!value) return;
                if (el instanceof HTMLInputElement) {
                    if (el.value) return; // no pisa lo que se escribió
                    el.value = value;
                    el.dataset.revealed = value;
                } else {
                    el.dataset.masked ??= el.textContent;
                    el.textContent = value;
                }
            });
        }
        shown = !shown;
        eye.setAttribute('aria-pressed', String(shown));
        eye.title = shown ? 'Ocultar' : 'Ver datos';
        swapIcons(eye, shown, ['data-icon-show', 'data-icon-hide']);
    });

    box.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const data = await loadSecrets(url);
            const value = data?.[button.dataset.copy];
            try {
                if (!value) throw new Error();
                await navigator.clipboard.writeText(value);
            } catch {
                return flashError(button, 'No se pudo copiar');
            }
            swapIcons(button, true, ['data-icon-idle', 'data-icon-done']);
            setTimeout(() => swapIcons(button, false, ['data-icon-idle', 'data-icon-done']), 1500);
        });
    });
});

// Admin → Cuentas: sugiere correos ya usados en la plataforma, llena la clave y suma meses al vencimiento.
// Los correos y la clave se piden al servidor al usarse: la página no trae ninguno.
const subscriptionForm = document.querySelector('[data-subscription-form]');
if (subscriptionForm) {
    const service = subscriptionForm.elements.service_id;
    const email = subscriptionForm.elements.account_email;
    const password = subscriptionForm.elements.account_password;
    const list = document.getElementById('account-emails');
    let emails = [];
    let loadedFor = null;

    const loadEmails = async () => {
        if (!service.value || loadedFor === service.value) return;
        loadedFor = service.value;
        emails = (await getJson(subscriptionForm.dataset.emailsUrl, { service_id: service.value })) ?? [];
        list.replaceChildren(...emails.map((value) => new Option(value, value)));
    };
    service.addEventListener('change', () => { loadedFor = null; list.replaceChildren(); });
    email.addEventListener('focus', loadEmails);

    email.addEventListener('change', async () => {
        const value = email.value.trim();
        if (password.value || !emails.includes(value)) return;
        const saved = await getJson(subscriptionForm.dataset.passwordUrl, { service_id: service.value, email: value });
        if (saved?.password && !password.value) password.value = saved.password;
    });

    const startsOn = subscriptionForm.elements.starts_on;
    const expiresOn = subscriptionForm.elements.expires_on;
    subscriptionForm.querySelectorAll('[data-add-months]').forEach((button) => {
        button.addEventListener('click', () => {
            const base = startsOn.value ? new Date(`${startsOn.value}T00:00:00`) : new Date();
            const day = base.getDate();
            base.setMonth(base.getMonth() + Number(button.dataset.addMonths));
            if (base.getDate() !== day) base.setDate(0); // 31 ene + 1 mes = 28/29 feb
            const pad = (n) => String(n).padStart(2, '0');
            expiresOn.value = `${base.getFullYear()}-${pad(base.getMonth() + 1)}-${pad(base.getDate())}`;
        });
    });
}

// Panel · formulario de compra: botones de meses y campo "Fecha de vencimiento" (fecha de compra + meses)
const purchaseForm = document.querySelector('[data-purchase-form]');
if (purchaseForm) {
    const purchasedOn = purchaseForm.elements.purchased_on;
    const months = purchaseForm.elements.duration_months;
    const preview = purchaseForm.querySelector('[data-expiry-preview]');
    const update = () => {
        const count = Number(months.value);
        const empty = !purchasedOn.value || !Number.isInteger(count) || count < 1;
        preview.classList.toggle('text-accent', !empty);
        preview.classList.toggle('text-mist-400', empty);
        if (empty) {
            preview.textContent = preview.dataset.empty;
            return;
        }
        const date = new Date(`${purchasedOn.value}T00:00:00`);
        const day = date.getDate();
        date.setMonth(date.getMonth() + count);
        if (date.getDate() !== day) date.setDate(0); // 31 ene + 1 mes = 28/29 feb
        preview.textContent = date.toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
    };
    purchaseForm.querySelectorAll('[data-set-months]').forEach((button) => {
        button.addEventListener('click', () => {
            months.value = button.dataset.setMonths;
            update();
        });
    });
    [purchasedOn, months].forEach((input) => input.addEventListener('input', update));
    update();
}

// Panel: campana de notificaciones y aviso flotante de cuentas por vencer.
// El aviso sale en cualquier pantalla mientras haya avisos sin leer, y la campana
// se actualiza sola cada 30 s (sin recargar) cuando llega uno nuevo.
const bell = document.querySelector('[data-bell]');
if (bell) {
    const toggle = bell.querySelector('[data-bell-toggle]');
    const icon = bell.querySelector('[data-bell-icon]');
    const badge = bell.querySelector('[data-bell-badge]');
    const panel = bell.querySelector('[data-bell-panel]');
    const list = bell.querySelector('[data-bell-list]');
    const empty = bell.querySelector('[data-bell-empty]');
    const readAll = bell.querySelector('[data-bell-read-all]');
    const toast = bell.querySelector('[data-bell-toast]');
    const toastTitle = bell.querySelector('[data-bell-toast-title]');
    const toastMessage = bell.querySelector('[data-bell-toast-message]');
    const toastCount = bell.querySelector('[data-bell-toast-count]');
    const toastWhatsapp = bell.querySelector('[data-bell-toast-whatsapp]');
    const toastPhone = bell.querySelector('[data-bell-toast-phone]');
    let count = Number(bell.dataset.count);
    let expiring = Number(bell.dataset.expiring);
    let latestId = toast.dataset.latest || '';
    let hideTimer;

    // Una sola alerta para todo el panel: se guarda cuándo empezó, así al cambiar de
    // pantalla sigue la misma (no se reinicia el tiempo). La "firma" cambia solo cuando
    // hay algo nuevo (otra cuenta por vencer o un aviso nuevo) y entonces empieza otra.
    const signature = () => `${expiring}|${latestId}`;
    const stateKey = 'bell-toast';
    const loadState = () => { try { return JSON.parse(sessionStorage.getItem(stateKey)) ?? {}; } catch { return {}; } };
    const saveState = (state) => { try { sessionStorage.setItem(stateKey, JSON.stringify(state)); } catch { /* sin almacenamiento */ } };

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel.hidden);
    });
    document.addEventListener('click', (event) => {
        if (!panel.hidden && !bell.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });

    // Dura 20 s en total, sumando todas las pantallas por las que pase; con la ✕ se
    // cierra en todas.
    const TOAST_MS = 20000;
    const progress = toast.querySelector('.bell-toast-progress');
    const showToast = () => {
        clearTimeout(hideTimer);
        if (expiring === 0) {
            toast.classList.remove('is-open');
            return;
        }

        let state = loadState();
        const isNew = state.signature !== signature();
        if (isNew) {
            state = { signature: signature(), startedAt: Date.now(), closed: false };
            saveState(state);
        }

        const remaining = TOAST_MS - (Date.now() - state.startedAt);
        if (state.closed || remaining <= 0) {
            toast.classList.remove('is-open');
            return;
        }

        // Si la alerta ya venía de otra pantalla, aparece sin animación de entrada.
        toast.classList.toggle('no-enter', !isNew);
        progress.style.setProperty('--toast-from', `${(remaining / TOAST_MS) * 100}%`);
        progress.style.animationDuration = `${remaining}ms`;
        toast.classList.remove('is-open');
        void toast.offsetWidth; // vuelve a arrancar la barrita desde donde iba
        toast.classList.add('is-open');
        hideTimer = setTimeout(() => toast.classList.remove('is-open'), remaining);
    };
    toast.querySelector('[data-bell-toast-close]').addEventListener('click', () => {
        clearTimeout(hideTimer);
        toast.classList.remove('is-open');
        saveState({ ...loadState(), signature: signature(), closed: true });
    });

    const render = (data) => {
        expiring = data.expiring;
        toastCount.textContent = expiring;
        toastTitle.textContent = expiring === 1 ? 'Cuenta por vencer' : 'Cuentas por vencer';
        if (expiring === 0) toast.classList.remove('is-open');
        count = data.count;
        const label = count > 99 ? '99+' : String(count);
        badge.textContent = label;
        badge.classList.toggle('hidden', count === 0);
        toggle.classList.toggle('has-unread', count > 0);
        icon.classList.toggle('bell-ring', count > 0);
        readAll.classList.toggle('hidden', count === 0);
        toggle.setAttribute('aria-label', count ? `Notificaciones (${count} sin leer)` : 'Notificaciones');
        if (data.latest) toastMessage.textContent = data.latest.message;
        latestId = data.latest?.id ?? '';

        // Botón de WhatsApp del cliente del aviso más reciente (en la alerta flotante)
        toastWhatsapp.classList.toggle('hidden', !data.latest?.whatsapp);
        if (data.latest?.whatsapp) {
            toastWhatsapp.href = data.latest.whatsapp;
            toastPhone.textContent = data.latest.phone;
        }

        // Agrega a la lista de la campana el aviso nuevo, si todavía no está.
        if (data.latest && !list.querySelector(`[data-id="${data.latest.id}"]`)) {
            const item = document.createElement('li');
            item.dataset.id = data.latest.id;
            item.className = 'flex gap-3 px-4 py-3 text-sm transition hover:bg-ink-800';
            item.innerHTML = '<span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-pink-500/15 text-base" aria-hidden="true">⏰</span>'
                + '<div class="min-w-0 flex-1"><a class="block text-mist-100 hover:text-accent"></a>'
                + '<div class="mt-1.5 flex flex-wrap items-center gap-2"><span class="text-xs text-mist-400">ahora</span></div></div>';
            const link = item.querySelector('a');
            link.href = data.latest.url;
            link.textContent = data.latest.message;
            if (data.latest.whatsapp) {
                const whatsapp = toastWhatsapp.cloneNode(true);
                whatsapp.classList.remove('hidden');
                whatsapp.removeAttribute('data-bell-toast-whatsapp');
                item.querySelector('.mt-1\\.5').prepend(whatsapp);
            }
            list.prepend(item);
        }
        empty.classList.toggle('hidden', list.children.length > 0);
    };

    // Al abrir cualquier pantalla: muestra la alerta en curso (o una nueva si cambió algo).
    showToast();

    const poll = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(bell.dataset.statusUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            const before = signature();
            render(data);
            if (signature() !== before) showToast(); // algo nuevo: vuelve a salir sin recargar
        } catch { /* sin conexión: se intenta en la próxima vuelta */ }
    };
    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
}

// Gráfica de finanzas: línea guía y detalle del mes al pasar el mouse (o tocar).
document.querySelectorAll('[data-finance-chart]').forEach((chart) => {
    const svg = chart.querySelector('svg');
    const crosshair = chart.querySelector('[data-crosshair]');
    const tooltip = chart.querySelector('[data-tooltip]');
    const field = (name) => tooltip.querySelector(`[data-tooltip-${name}]`);

    const show = (point) => {
        const x = Number(point.dataset.x);
        crosshair.setAttribute('x1', x);
        crosshair.setAttribute('x2', x);
        crosshair.classList.remove('hidden');

        field('month').textContent = point.dataset.month;
        field('income').textContent = point.dataset.income;
        field('investment').textContent = point.dataset.investment;
        field('profit').textContent = point.dataset.profit;
        field('profit-label').textContent = point.dataset.loss === '1' ? 'Pérdida' : 'Ganancia';
        tooltip.classList.remove('hidden');

        // Pone el cuadro al lado del mes, sin salirse de la tarjeta.
        const scale = svg.getBoundingClientRect().width / svg.viewBox.baseVal.width;
        const left = x * scale + 12;
        const fitsRight = left + tooltip.offsetWidth <= svg.getBoundingClientRect().width;
        tooltip.style.left = `${fitsRight ? left : x * scale - tooltip.offsetWidth - 12}px`;
    };

    const hide = () => {
        crosshair.classList.add('hidden');
        tooltip.classList.add('hidden');
    };

    chart.querySelectorAll('[data-point]').forEach((point) => {
        point.addEventListener('pointerenter', () => show(point));
    });
    svg.addEventListener('pointerleave', hide);
});

import './bootstrap';

const modal = document.querySelector('[data-ai-modal]');

if (modal) {
    const modalBody = modal.querySelector('[data-ai-modal-body]');
    const modalTitle = modal.querySelector('[data-ai-modal-title]');
    const modalMeta = modal.querySelector('[data-ai-modal-meta]');
    const modalLoader = modal.querySelector('[data-ai-modal-loader]');
    const modalError = modal.querySelector('[data-ai-modal-error]');

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    modal.querySelectorAll('[data-ai-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    const setLoading = (isLoading) => {
        modalLoader.classList.toggle('hidden', !isLoading);
        modalBody.classList.toggle('hidden', isLoading);
    };

    const setError = (message) => {
        if (message) {
            modalError.textContent = message;
            modalError.classList.remove('hidden');
        } else {
            modalError.textContent = '';
            modalError.classList.add('hidden');
        }
    };

    const createRow = (label, value) => {
        if (!value) {
            return null;
        }

        const row = document.createElement('div');
        row.className = 'space-y-1';

        const labelEl = document.createElement('p');
        labelEl.className = 'text-[11px] uppercase tracking-wide text-neutral-500';
        labelEl.textContent = label;

        const valueEl = document.createElement('p');
        valueEl.className = 'text-sm text-neutral-800';
        valueEl.textContent = value;

        row.append(labelEl, valueEl);
        return row;
    };

    const createModelRow = (value, isPay) => {
        if (!value) {
            return null;
        }

        const row = document.createElement('div');
        row.className = 'space-y-1';

        const labelEl = document.createElement('p');
        labelEl.className = 'text-[11px] uppercase tracking-wide text-neutral-500';
        labelEl.textContent = 'Modello usato';

        const valueEl = document.createElement('p');
        valueEl.className = 'flex items-center gap-1.5 text-sm text-neutral-800';

        if (isPay) {
            const icon = document.createElement('span');
            icon.className = 'inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-300 text-[9px] font-bold text-amber-950';
            icon.textContent = '$';
            icon.title = 'Modello a pagamento';
            valueEl.appendChild(icon);
        }

        valueEl.append(document.createTextNode(value));
        row.append(labelEl, valueEl);

        return row;
    };

    const createSectionTitle = (title) => {
        const titleEl = document.createElement('p');
        titleEl.className = 'text-[11px] uppercase tracking-wide text-neutral-500';
        titleEl.textContent = title;
        return titleEl;
    };

    const renderDetails = (data) => {
        modalBody.innerHTML = '';

        const headerGrid = document.createElement('div');
        headerGrid.className = 'grid gap-4 sm:grid-cols-2';

        const modelValue = data.model || 'Non disponibile';
        const softwareVersionValue = data.software_version || null;

        const left = document.createElement('div');
        left.className = 'space-y-3';
        const modelRow = createModelRow(modelValue, data.is_pay);
        if (modelRow) {
            left.appendChild(modelRow);
        }

        if (softwareVersionValue) {
            const softwareVersionRow = createRow('Versione software', softwareVersionValue);
            if (softwareVersionRow) {
                left.appendChild(softwareVersionRow);
            }
        }

        if (data.source) {
            const sourceRow = createRow('Origine', data.source.label);
            if (sourceRow) {
                left.appendChild(sourceRow);
            }
        }

        const right = document.createElement('div');
        right.className = 'space-y-3';

        if (data.source) {
            const sourceTitle = createRow('Titolo notizia', data.source.title);
            if (sourceTitle) {
                right.appendChild(sourceTitle);
            }

            const sourceName = createRow('Fonte', data.source.source_name);
            if (sourceName) {
                right.appendChild(sourceName);
            }

            const sourceDate = createRow('Data', data.source.date);
            if (sourceDate) {
                right.appendChild(sourceDate);
            }

            const sourceCategory = createRow('Categoria', data.source.category);
            if (sourceCategory) {
                right.appendChild(sourceCategory);
            }

            if (data.source.source_url) {
                const linkRow = document.createElement('div');
                linkRow.className = 'space-y-1';
                const linkLabel = document.createElement('p');
                linkLabel.className = 'text-[11px] uppercase tracking-wide text-neutral-500';
                linkLabel.textContent = 'Link fonte';

                const link = document.createElement('a');
                link.className = 'text-sm font-semibold text-indigo-600 hover:text-indigo-700';
                link.href = data.source.source_url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = 'Apri fonte';

                linkRow.append(linkLabel, link);
                right.appendChild(linkRow);
            }
        }

        headerGrid.append(left, right);
        modalBody.appendChild(headerGrid);

        if (data.source && (data.source.summary || data.source.why_it_matters)) {
            const summaryBlock = document.createElement('div');
            summaryBlock.className = 'space-y-2';

            if (data.source.summary) {
                summaryBlock.appendChild(createSectionTitle('Riassunto'));
                const summaryText = document.createElement('p');
                summaryText.className = 'text-sm text-neutral-700 leading-relaxed';
                summaryText.textContent = data.source.summary;
                summaryBlock.appendChild(summaryText);
            }

            if (data.source.why_it_matters) {
                summaryBlock.appendChild(createSectionTitle('Perche e rilevante'));
                const whyText = document.createElement('p');
                whyText.className = 'text-sm text-neutral-700 leading-relaxed';
                whyText.textContent = data.source.why_it_matters;
                summaryBlock.appendChild(whyText);
            }

            modalBody.appendChild(summaryBlock);
        }

    };

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-ai-details]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openModal();

        modalTitle.textContent = 'Dettagli AI';
        modalMeta.textContent = trigger.textContent?.trim() || '';

        setError('');
        setLoading(true);

        try {
            const response = await fetch(trigger.dataset.url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Errore nel caricamento dei dettagli AI.');
            }

            const data = await response.json();
            renderDetails(data);
            setLoading(false);
        } catch (error) {
            setLoading(false);
            setError('Impossibile caricare i dettagli AI. Riprova tra poco.');
        }
    });
}

const reportModal = document.querySelector('[data-report-modal]');

if (reportModal) {
    const confirmButton = reportModal.querySelector('[data-report-confirm]');
    const cancelButtons = reportModal.querySelectorAll('[data-report-cancel]');
    let lastTrigger = null;

    const openReportModal = (trigger) => {
        const targetUrl = trigger.getAttribute('href') || '#';
        confirmButton?.setAttribute('href', targetUrl);
        lastTrigger = trigger;
        reportModal.classList.remove('hidden');
        reportModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        confirmButton?.focus();
    };

    const closeReportModal = () => {
        reportModal.classList.add('hidden');
        reportModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        lastTrigger?.focus();
    };

    cancelButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeReportModal();
        });
    });

    reportModal.addEventListener('click', (event) => {
        if (event.target === reportModal) {
            closeReportModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !reportModal.classList.contains('hidden')) {
            closeReportModal();
        }
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-report-trigger]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openReportModal(trigger);
    });
}

const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

if (mobileMenuToggle && mobileMenu) {
    const openIcon = mobileMenuToggle.querySelector('[data-mobile-menu-icon="open"]');
    const closeIcon = mobileMenuToggle.querySelector('[data-mobile-menu-icon="close"]');

    const setMenuOpen = (isOpen) => {
        mobileMenu.classList.toggle('hidden', !isOpen);
        mobileMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        openIcon?.classList.toggle('hidden', isOpen);
        closeIcon?.classList.toggle('hidden', !isOpen);
    };

    setMenuOpen(false);

    mobileMenuToggle.addEventListener('click', () => {
        setMenuOpen(mobileMenu.classList.contains('hidden'));
    });

    document.addEventListener('click', (event) => {
        if (mobileMenu.contains(event.target) || mobileMenuToggle.contains(event.target)) {
            return;
        }

        setMenuOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            setMenuOpen(false);
        }
    });
}

const dropdown = document.querySelector('[data-dropdown]');

if (dropdown) {
    const toggle = dropdown.querySelector('[data-dropdown-toggle]');
    const menu = dropdown.querySelector('[data-dropdown-menu]');
    const chevron = dropdown.querySelector('[data-dropdown-chevron]');

    const setOpen = (isOpen) => {
        menu.classList.toggle('hidden', !isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        chevron?.classList.toggle('rotate-180', isOpen);
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(menu.classList.contains('hidden'));
    });

    document.addEventListener('click', (event) => {
        if (!dropdown.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.classList.contains('hidden')) {
            setOpen(false);
            toggle.focus();
        }
    });
}

const commentToggles = document.querySelectorAll('[data-comment-toggle]');

commentToggles.forEach((details) => {
    const summary = details.querySelector('[data-comment-summary]');
    const hideButton = details.querySelector('[data-comment-hide]');

    const syncToggleState = () => {
        const isOpen = details.hasAttribute('open');
        if (summary) {
            summary.classList.toggle('hidden', isOpen);
        }
        if (hideButton) {
            hideButton.classList.toggle('hidden', !isOpen);
        }
    };

    syncToggleState();

    details.addEventListener('toggle', syncToggleState);

    if (hideButton) {
        hideButton.addEventListener('click', () => {
            details.removeAttribute('open');
            syncToggleState();
            summary?.focus();
        });
    }
});

const avatarForm = document.querySelector('[data-avatar-form]');

if (avatarForm) {
    const errorClass = 'mt-2 text-xs text-rose-600';
    const borderErrorClass = 'border-rose-400';
    const borderNormalClass = 'border-neutral-200';

    const getErrorEl = (input) => {
        return input.closest('label')?.querySelector('[data-avatar-error]') || null;
    };

    const showError = (input, message) => {
        input.classList.add(borderErrorClass);
        input.classList.remove(borderNormalClass);

        let errorEl = getErrorEl(input);
        if (!errorEl) {
            errorEl = document.createElement('p');
            errorEl.className = errorClass;
            errorEl.setAttribute('data-avatar-error', '');
            input.parentNode.insertBefore(errorEl, input.nextSibling);
        }
        errorEl.textContent = message;
    };

    const clearError = (input) => {
        input.classList.remove(borderErrorClass);
        input.classList.add(borderNormalClass);

        const errorEl = getErrorEl(input);
        if (errorEl) {
            errorEl.remove();
        }
    };

    const validate = () => {
        let valid = true;
        const inputs = avatarForm.querySelectorAll('input[required], textarea[required], select[required]');

        inputs.forEach((input) => {
            clearError(input);

            const value = input.value.trim();

            if (!value) {
                showError(input, 'Questo campo è obbligatorio.');
                valid = false;
                return;
            }

            const minlength = input.getAttribute('minlength');
            if (minlength && value.length < parseInt(minlength, 10)) {
                showError(input, `Servono almeno ${minlength} caratteri.`);
                valid = false;
                return;
            }

            const min = input.getAttribute('min');
            const max = input.getAttribute('max');
            if (min !== null && max !== null && input.type === 'number') {
                const num = parseInt(value, 10);
                if (isNaN(num) || num < parseInt(min, 10) || num > parseInt(max, 10)) {
                    showError(input, `Il valore deve essere tra ${min} e ${max}.`);
                    valid = false;
                }
            }
        });

        return valid;
    };

    avatarForm.addEventListener('submit', (event) => {
        if (!validate()) {
            event.preventDefault();
        }
    });

    avatarForm.querySelectorAll('input, textarea, select').forEach((input) => {
        input.addEventListener('input', () => {
            clearError(input);
        });
    });
}

const cookieBanner = document.querySelector('[data-cookie-banner]');

if (cookieBanner) {
    const consentKey = 'livelia_cookie_consent';
    const acceptedValue = 'accepted';
    const rejectedValue = 'rejected';
    const gtagId = document.body?.dataset.gtagId;

    const readConsent = () => {
        try {
            return window.localStorage.getItem(consentKey);
        } catch (error) {
            return null;
        }
    };

    const writeConsent = (value) => {
        try {
            window.localStorage.setItem(consentKey, value);
        } catch (error) {
            // Ignore storage errors (private mode, blocked storage).
        }
    };

    const loadAnalytics = () => {
        if (!gtagId || window.__liveliaGtagLoaded) {
            return;
        }

        window.__liveliaGtagLoaded = true;
        window.dataLayer = window.dataLayer || [];

        const gtag = function () {
            window.dataLayer.push(arguments);
        };

        window.gtag = window.gtag || gtag;
        window.gtag('js', new Date());
        window.gtag('config', gtagId);

        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${gtagId}`;
        document.head.appendChild(script);
    };

    const showBanner = () => {
        cookieBanner.classList.remove('hidden');
    };

    const hideBanner = () => {
        cookieBanner.classList.add('hidden');
    };

    const storedConsent = readConsent();

    if (storedConsent === acceptedValue) {
        hideBanner();
        loadAnalytics();
    } else if (storedConsent === rejectedValue) {
        hideBanner();
    } else {
        showBanner();
    }

    const acceptButton = cookieBanner.querySelector('[data-cookie-accept]');
    const rejectButton = cookieBanner.querySelector('[data-cookie-reject]');

    acceptButton?.addEventListener('click', () => {
        writeConsent(acceptedValue);
        hideBanner();
        loadAnalytics();
    });

    rejectButton?.addEventListener('click', () => {
        writeConsent(rejectedValue);
        hideBanner();
    });
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-like-toggle]');
    if (!button || button.disabled) {
        return;
    }

    event.preventDefault();
    button.disabled = true;

    const url = button.dataset.url;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Errore nel toggle del mi piace.');
        }

        const data = await response.json();
        const nowLiked = data.liked;

        button.dataset.liked = nowLiked ? 'true' : 'false';
        button.setAttribute('aria-pressed', nowLiked ? 'true' : 'false');

        button.classList.toggle('text-rose-600', nowLiked);
        button.classList.toggle('text-neutral-600', !nowLiked);
        button.classList.toggle('hover:text-rose-600', !nowLiked);

        const svg = button.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', nowLiked ? 'currentColor' : 'none');
        }

        const countEl = button.querySelector('[data-like-count]');
        if (countEl) {
            const humanLikes = data.human_likes_count;
            const aiLikes = data.ai_likes_count ?? 0;
            countEl.textContent = humanLikes + aiLikes;
        }

        button.dataset.humanLikes = String(data.human_likes_count);
        if (data.ai_likes_count !== undefined) {
            button.dataset.aiLikes = String(data.ai_likes_count);
        }

        const wrapper = button.closest('[data-like-wrapper]');
        const tooltip = wrapper?.querySelector('[data-like-tooltip]');
        if (tooltip) {
            const totalLikes = (data.human_likes_count ?? 0) + (data.ai_likes_count ?? 0);
            if (totalLikes > 0) {
                tooltip.querySelector('[data-like-tooltip-ai]').textContent = `Mi piace AI: ${data.ai_likes_count ?? 0}`;
                tooltip.querySelector('[data-like-tooltip-human]').textContent = `Mi piace Umani: ${data.human_likes_count ?? 0}`;
            } else {
                tooltip.classList.add('hidden');
            }
        }
    } catch (error) {
        // Silently fail — state unchanged.
    }

    button.disabled = false;
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const inputId = button.dataset.passwordToggle;
    const input = document.getElementById(inputId);
    const iconShow = button.querySelector('[data-password-icon-show]');
    const iconHide = button.querySelector('[data-password-icon-hide]');

    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const isVisible = input.type === 'text';

        input.type = isVisible ? 'password' : 'text';
        button.setAttribute('aria-label', isVisible ? 'Mostra password' : 'Nascondi password');
        iconShow.classList.toggle('hidden', !isVisible);
        iconHide.classList.toggle('hidden', isVisible);
    });
});

document.querySelectorAll('[data-like-wrapper]').forEach((wrapper) => {
    const tooltip = wrapper.querySelector('[data-like-tooltip]');
    if (!tooltip) {
        return;
    }

    const target = wrapper.querySelector('[data-like-toggle]') || wrapper.querySelector('a');
    if (!target) {
        return;
    }

    target.addEventListener('mouseenter', () => {
        tooltip.classList.remove('hidden');
    });

    target.addEventListener('mouseleave', () => {
        tooltip.classList.add('hidden');
    });
});

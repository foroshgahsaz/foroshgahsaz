document.addEventListener('DOMContentLoaded', () => {
  initProductGallery();
  initProductLightbox();
  initProductZoom();
  initProductOptions();
  initProductQty();
  initProductTabs();
  initRelatedProductsSwiper();
  initVariantLoadingOverlay();
  initProductVariantImageSync();
});

function initVariantLoadingOverlay() {
  const grid = document.querySelector('.product-top-grid');
  const overlay = document.getElementById('productVariantOverlay');
  if (!grid || !overlay) {
    return;
  }

  let observer = null;

  const syncOverlay = (active) => {
    grid.classList.toggle('is-variant-loading', active);
    overlay.setAttribute('aria-hidden', active ? 'false' : 'true');
  };

  const syncFromSentinel = () => {
    const sentinel = grid.querySelector('.variant-loading-sentinel');
    syncOverlay(Boolean(sentinel?.classList.contains('is-active')));
  };

  const watchSentinel = () => {
    observer?.disconnect();
    observer = null;

    const sentinel = grid.querySelector('.variant-loading-sentinel');
    if (!sentinel) {
      syncOverlay(false);
      return;
    }

    syncFromSentinel();
    observer = new MutationObserver(syncFromSentinel);
    observer.observe(sentinel, { attributes: true, attributeFilter: ['class'] });
  };

  const bindLivewireHooks = () => {
    Livewire.hook('message.processed', () => {
      requestAnimationFrame(syncFromSentinel);
    });

    Livewire.hook('morph.updated', ({ el }) => {
      if (grid === el || grid.contains(el)) {
        watchSentinel();
      }
    });
  };

  watchSentinel();

  if (window.Livewire) {
    bindLivewireHooks();
  } else {
    document.addEventListener('livewire:init', () => {
      watchSentinel();
      bindLivewireHooks();
    }, { once: true });
  }
}

function initProductVariantImageSync() {
  const applyImage = (image) => {
    if (!image) {
      return;
    }

    const mainImage = document.getElementById('productMainImage');
    if (!mainImage) {
      return;
    }

    const fallback = mainImage.dataset.initialSrc || mainImage.src;
    const candidate = document.createElement('img');

    candidate.onload = () => {
      mainImage.src = image;
      mainImage.dataset.zoom = image;
    };

    candidate.onerror = () => {
      if (fallback) {
        mainImage.src = fallback;
        mainImage.dataset.zoom = fallback;
      }
    };

    candidate.src = image;
  };

  const bind = () => {
    Livewire.on('product-image-changed', ({ image }) => applyImage(image));
  };

  if (window.Livewire) {
    bind();
  } else {
    document.addEventListener('livewire:init', bind, { once: true });
  }
}

let productGalleryIndex = 0;
let lightboxSwiper = null;

function getGalleryImages() {
  const fromThumbs = Array.from(document.querySelectorAll('.productGalleryThumbs .swiper-slide img')).map((img) => ({
    src: img.dataset.zoom || img.src,
    alt: img.alt,
  }));

  if (fromThumbs.length) {
    return fromThumbs;
  }

  const mainImage = document.getElementById('productMainImage');
  if (mainImage?.src) {
    return [{
      src: mainImage.dataset.zoom || mainImage.src,
      alt: mainImage.alt || '',
    }];
  }

  return Array.from(document.querySelectorAll('.productLightboxSwiper .swiper-slide img')).map((img) => ({
    src: img.src,
    alt: img.alt,
  }));
}

function setMainGalleryImage(index) {
  const images = getGalleryImages();
  const mainImage = document.getElementById('productMainImage');
  const thumbsEl = document.querySelector('.productGalleryThumbs');
  if (!mainImage || !images[index]) return;

  productGalleryIndex = index;

  const candidate = document.createElement('img');
  candidate.onload = () => {
    mainImage.src = images[index].src;
    mainImage.dataset.zoom = images[index].src;
    mainImage.alt = images[index].alt;
  };
  candidate.onerror = () => {
    if (index === 0 && mainImage.src && mainImage.dataset.initialSrc) {
      mainImage.src = mainImage.dataset.initialSrc;
      mainImage.dataset.zoom = mainImage.dataset.initialSrc;
    }
  };
  candidate.src = images[index].src;

  thumbsEl?.querySelectorAll('.swiper-slide').forEach((slide, i) => {
    slide.classList.toggle('swiper-slide-thumb-active', i === index);
  });
}

function initProductGallery() {
  const thumbsEl = document.querySelector('.productGalleryThumbs');
  if (!thumbsEl || typeof Swiper === 'undefined') return;

  new Swiper(thumbsEl, {
    direction: 'horizontal',
    spaceBetween: 10,
    slidesPerView: 'auto',
    freeMode: true,
    watchOverflow: true,
  });

  thumbsEl.querySelectorAll('.swiper-slide').forEach((slide, index) => {
    slide.addEventListener('click', () => {
      setMainGalleryImage(index);
      if (lightboxSwiper) {
        lightboxSwiper.slideTo(index, 0);
      }
    });

    slide.addEventListener('dblclick', () => {
      setMainGalleryImage(index);
      window.openProductLightbox?.(index);
    });
  });

  productGalleryIndex = 0;
  thumbsEl.querySelectorAll('.swiper-slide').forEach((slide, index) => {
    slide.classList.toggle('swiper-slide-thumb-active', index === 0);
  });
}

function initProductLightbox() {
  const modal = document.getElementById('productZoomModal');
  const swiperEl = modal?.querySelector('.productLightboxSwiper');
  if (!modal || !swiperEl || typeof Swiper === 'undefined') return;

  lightboxSwiper = new Swiper(swiperEl, {
    slidesPerView: 1,
    spaceBetween: 0,
    speed: 300,
    loop: false,
    navigation: {
      nextEl: modal.querySelector('[data-lightbox-next]'),
      prevEl: modal.querySelector('[data-lightbox-prev]'),
    },
    pagination: {
      el: modal.querySelector('.product-lightbox__pagination'),
      clickable: true,
    },
    keyboard: {
      enabled: true,
      onlyInViewport: false,
    },
    on: {
      slideChange(swiper) {
        if (!modal.classList.contains('is-open')) {
          return;
        }
        setMainGalleryImage(swiper.activeIndex);
      },
    },
    allowTouchMove: true,
  });

  const openLightbox = (index = productGalleryIndex) => {
    setMainGalleryImage(index);
    lightboxSwiper.slideTo(index, 0);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeLightbox = () => {
    resetLightboxZoom();
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  const lightboxStage = modal.querySelector('[data-lightbox-stage]');
  const lightboxZoomBtn = modal.querySelector('[data-lightbox-zoom]');
  const LIGHTBOX_ZOOM_SCALE = 2.5;
  let lightboxZoomed = false;
  let lightboxPan = { x: 0, y: 0 };
  let lightboxPanStart = null;

  const getActiveLightboxImage = () => {
    const slide = lightboxSwiper?.slides?.[lightboxSwiper.activeIndex];
    return slide?.querySelector('img') ?? null;
  };

  const applyLightboxZoomTransform = () => {
    const img = getActiveLightboxImage();
    if (!img) {
      return;
    }

    if (!lightboxZoomed) {
      img.style.transform = '';
      lightboxStage?.classList.remove('is-zoomed', 'is-panning');
      return;
    }

    img.style.transform = `translate(${lightboxPan.x}px, ${lightboxPan.y}px) scale(${LIGHTBOX_ZOOM_SCALE})`;
    lightboxStage?.classList.add('is-zoomed');
  };

  const resetLightboxZoom = () => {
    lightboxZoomed = false;
    lightboxPan = { x: 0, y: 0 };
    lightboxPanStart = null;
    lightboxZoomBtn?.classList.remove('is-active');
    lightboxZoomBtn?.setAttribute('aria-pressed', 'false');
    applyLightboxZoomTransform();
    lightboxSwiper?.enable();
  };

  const setLightboxZoom = (enabled) => {
    lightboxZoomed = enabled;
    lightboxPan = { x: 0, y: 0 };
    lightboxZoomBtn?.classList.toggle('is-active', enabled);
    lightboxZoomBtn?.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    applyLightboxZoomTransform();
    if (enabled) {
      lightboxSwiper?.disable();
    } else {
      lightboxSwiper?.enable();
    }
  };

  lightboxZoomBtn?.addEventListener('click', (event) => {
    event.stopPropagation();
    setLightboxZoom(!lightboxZoomed);
  });

  lightboxStage?.addEventListener('mousedown', (event) => {
    if (!lightboxZoomed || event.button !== 0) {
      return;
    }
    event.preventDefault();
    lightboxPanStart = {
      originX: event.clientX - lightboxPan.x,
      originY: event.clientY - lightboxPan.y,
    };
    lightboxStage.classList.add('is-panning');
  });

  document.addEventListener('mousemove', (event) => {
    if (!lightboxPanStart) {
      return;
    }
    lightboxPan.x = event.clientX - lightboxPanStart.originX;
    lightboxPan.y = event.clientY - lightboxPanStart.originY;
    applyLightboxZoomTransform();
  });

  document.addEventListener('mouseup', () => {
    if (!lightboxPanStart) {
      return;
    }
    lightboxPanStart = null;
    lightboxStage?.classList.remove('is-panning');
  });

  lightboxSwiper.on('slideChange', () => {
    resetLightboxZoom();
  });

  let draggedRecently = false;
  let dragStart = 0;
  const zoomStage = document.querySelector('.product-zoom__stage');

  if (zoomStage) {
    zoomStage.addEventListener('mousedown', () => {
      dragStart = Date.now();
      draggedRecently = false;
    });

    zoomStage.addEventListener('mousemove', () => {
      if (dragStart && Date.now() - dragStart > 200) {
        draggedRecently = true;
      }
    });

    zoomStage.addEventListener('mouseup', () => {
      if (Date.now() - dragStart > 250) {
        draggedRecently = true;
      }
      dragStart = 0;
    });
  }

  document.querySelectorAll('[data-open-lightbox]').forEach((node) => {
    node.addEventListener('click', () => {
      if (draggedRecently) {
        draggedRecently = false;
        return;
      }
      openLightbox(productGalleryIndex);
    });

    node.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openLightbox(productGalleryIndex);
      }
    });
  });

  modal.querySelector('.product-lightbox__close')?.addEventListener('click', closeLightbox);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) closeLightbox();
  });

  document.addEventListener('keydown', (event) => {
    if (!modal.classList.contains('is-open')) return;
    if (event.key === 'Escape') closeLightbox();
  });

  window.openProductLightbox = openLightbox;
  window.closeProductLightbox = closeLightbox;
}

function initProductZoom() {
  const zoomRoot = document.querySelector('.product-zoom');
  const stage = document.querySelector('.product-zoom__stage');
  const lens = document.querySelector('.product-zoom__lens');
  const lensImg = document.querySelector('.product-zoom__lens-img');
  const mainImage = document.getElementById('productMainImage');

  if (!zoomRoot || !stage || !lens || !lensImg || !mainImage) {
    return;
  }

  const zoomLevel = 2.75;

  const zoomSrc = () => mainImage.dataset.zoom || mainImage.src;

  const resetZoom = () => {
    zoomRoot.classList.remove('is-active');
    lens.setAttribute('aria-hidden', 'true');
  };

  const updateZoom = (event) => {
    if (window.innerWidth < 1024) {
      return;
    }
    if (document.getElementById('productZoomModal')?.classList.contains('is-open')) {
      return;
    }

    const rect = stage.getBoundingClientRect();
    let x = event.clientX - rect.left;
    let y = event.clientY - rect.top;

    if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
      resetZoom();
      return;
    }

    const lensW = lens.offsetWidth;
    const lensH = lens.offsetHeight;
    x = Math.max(lensW / 2, Math.min(rect.width - lensW / 2, x));
    y = Math.max(lensH / 2, Math.min(rect.height - lensH / 2, y));

    lens.style.left = `${x - lensW / 2}px`;
    lens.style.top = `${y - lensH / 2}px`;

    const zoomW = rect.width * zoomLevel;
    const zoomH = rect.height * zoomLevel;
    const src = zoomSrc();
    if (lensImg.getAttribute('src') !== src) {
      lensImg.src = src;
    }
    lensImg.style.width = `${zoomW}px`;
    lensImg.style.height = `${zoomH}px`;
    lensImg.style.left = `${-(x * zoomLevel - lensW / 2)}px`;
    lensImg.style.top = `${-(y * zoomLevel - lensH / 2)}px`;

    zoomRoot.classList.add('is-active');
    lens.setAttribute('aria-hidden', 'false');
  };

  stage.addEventListener('mousemove', updateZoom);
  stage.addEventListener('mouseleave', resetZoom);
}

function initProductOptions() {
  document.querySelectorAll('[data-color-option]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const colorName = btn.getAttribute('aria-label') || '';
      document.querySelectorAll('[data-color-option]').forEach((item) => {
        item.classList.toggle('is-active', item.getAttribute('aria-label') === colorName);
      });
    });
  });

  document.querySelectorAll('[data-storage-option]').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-storage-option]').forEach((item) => item.classList.remove('is-active'));
      btn.classList.add('is-active');
      const price = btn.dataset.price;
      if (!price) return;
      const cfg = window.__shopPrice || {};
      if (cfg.hidden) {
        const priceHtml = '—';
        const priceEl = document.getElementById('productPrice');
        if (priceEl) priceEl.innerHTML = priceHtml;
        const mobilePriceEl = document.getElementById('productMobilePrice');
        if (mobilePriceEl) mobilePriceEl.innerHTML = priceHtml;
        return;
      }
      const factor = typeof cfg.factor === 'number' ? cfg.factor : 10;
      const rial = Number(String(price).replace(/,/g, '')) * factor;
      const formatted = Number.isFinite(rial) ? rial.toLocaleString('en-US') : price;
      const priceHtml = `${formatted} <span>${cfg.currency || 'ریال'}</span>`;
      const priceEl = document.getElementById('productPrice');
      if (priceEl) priceEl.innerHTML = priceHtml;
      const mobilePriceEl = document.getElementById('productMobilePrice');
      if (mobilePriceEl) mobilePriceEl.innerHTML = priceHtml;
    });
  });

  document.querySelectorAll('[data-warranty-option]').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-warranty-option]').forEach((item) => item.classList.remove('is-active'));
      btn.classList.add('is-active');
      const warrantyLabel = document.getElementById('selectedWarrantyLabel');
      const label = btn.getAttribute('aria-label') || btn.textContent.trim();
      if (warrantyLabel && label) {
        warrantyLabel.textContent = label.replace(/^گارانتی\s*/, '');
      }
    });
  });

  document.querySelectorAll('[data-favorite-btn]').forEach((btn) => {
    btn.addEventListener('click', () => btn.classList.toggle('is-active'));
  });
}

function initProductQty() {
  const input = document.getElementById('productQty');
  if (!input) return;

  document.querySelector('[data-qty-minus]')?.addEventListener('click', () => {
    input.value = Math.max(1, Number(input.value) - 1);
  });

  document.querySelector('[data-qty-plus]')?.addEventListener('click', () => {
    input.value = Number(input.value) + 1;
  });
}

function initProductTabs() {
  const tablist = document.querySelector('.product-tabs[role="tablist"]');
  if (!tablist) return;

  const tabs = tablist.querySelectorAll('[data-product-tab]');
  const panels = document.querySelectorAll('[data-product-panel]');

  const activateTab = (target, { scroll = false } = {}) => {
    if (!target) return;

    tabs.forEach((tab) => {
      const isActive = tab.dataset.productTab === target;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const isActive = panel.dataset.productPanel === target;
      panel.classList.toggle('is-active', isActive);
      panel.toggleAttribute('hidden', !isActive);
    });

    if (scroll) {
      document.getElementById('product-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      activateTab(tab.dataset.productTab);
    });
  });

  document.querySelectorAll('[data-open-product-tab]').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      activateTab(link.dataset.openProductTab, { scroll: true });
    });
  });

  const hashTarget = window.location.hash.replace('#', '');
  if (hashTarget === 'reviews') {
    activateTab('reviews', { scroll: true });
  }

  document.querySelector('.product-info__more')?.addEventListener('click', (event) => {
    event.preventDefault();
    activateTab('specs', { scroll: true });
  });
}

function initRelatedProductsSwiper() {
  const el = document.querySelector('.relatedProductsSwiper');
  if (!el || typeof Swiper === 'undefined') return;

  const section = el.closest('section');
  new Swiper(el, {
    slidesPerView: 'auto',
    spaceBetween: 12,
    grabCursor: true,
    navigation: {
      nextEl: section?.querySelector('[data-related-next]'),
      prevEl: section?.querySelector('[data-related-prev]'),
    },
    breakpoints: {
      768: { spaceBetween: 16 },
    },
  });
}

document.addEventListener('DOMContentLoaded', () => {
	const viewport = document.querySelector('.panorama-viewport');
	const content = document.querySelector('.panorama-viewport .panorama');
	const hotspotsLayer = content ? content.querySelector('.hotspots-layer') : null;
	const btnIn = document.querySelector('.panorama-toolbar .zoom-in');
	const btnOut = document.querySelector('.panorama-toolbar .zoom-out');
	const btnReset = document.querySelector('.panorama-toolbar .zoom-reset');

	if (!viewport || !content) return;

	let scale = 1;
	const minScale = 1;
	const maxScale = 4;

	function applyTransform() {
		content.style.transform = `scale(${scale})`;
	}

	function clamp(v, min, max) {
		return Math.max(min, Math.min(max, v));
	}

	function zoomAt(clientX, clientY, nextScale) {
		nextScale = clamp(nextScale, minScale, maxScale);
		const rect = viewport.getBoundingClientRect();
		const x = clientX - rect.left;
		const y = clientY - rect.top;
		const contentX = (viewport.scrollLeft + x) / scale;
		const contentY = (viewport.scrollTop + y) / scale;
		scale = nextScale;
		applyTransform();
		viewport.scrollLeft = contentX * scale - x;
		viewport.scrollTop = contentY * scale - y;
	}

	viewport.addEventListener('wheel', (e) => {
		if (e.ctrlKey) {
			e.preventDefault();
			const zoomIntensity = 0.0015;
			const factor = Math.exp(-e.deltaY * zoomIntensity);
			zoomAt(e.clientX, e.clientY, scale * factor);
		}
	}, { passive: false });

	let isPanning = false;
	let startX = 0, startY = 0, startSL = 0, startST = 0;
	viewport.addEventListener('mousedown', (e) => {
		isPanning = true;
		viewport.classList.add('grabbing');
		startX = e.clientX;
		startY = e.clientY;
		startSL = viewport.scrollLeft;
		startST = viewport.scrollTop;
		e.preventDefault();
	});
	window.addEventListener('mousemove', (e) => {
		if (!isPanning) return;
		const dx = e.clientX - startX;
		const dy = e.clientY - startY;
		viewport.scrollLeft = startSL - dx;
		viewport.scrollTop = startST - dy;
	});
	window.addEventListener('mouseup', () => {
		isPanning = false;
		viewport.classList.remove('grabbing');
	});

	let touchMode = 'none';
	let startDist = 0;
	let startScale = 1;
	let midStart = { x: 0, y: 0, contentX: 0, contentY: 0 };

	function distance(t1, t2) {
		const dx = t1.clientX - t2.clientX;
		const dy = t1.clientY - t2.clientY;
		return Math.hypot(dx, dy);
	}

	viewport.addEventListener('touchstart', (e) => {
		if (e.touches.length === 2) {
			touchMode = 'pinch';
			startDist = distance(e.touches[0], e.touches[1]);
			startScale = scale;
			const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
			const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
			const rect = viewport.getBoundingClientRect();
			const x = midX - rect.left;
			const y = midY - rect.top;
			midStart = {
				x,
				y,
				contentX: (viewport.scrollLeft + x) / scale,
				contentY: (viewport.scrollTop + y) / scale,
			};
		}
	}, { passive: true });

	viewport.addEventListener('touchmove', (e) => {
		if (touchMode === 'pinch' && e.touches.length === 2) {
			e.preventDefault();
			const dist = distance(e.touches[0], e.touches[1]);
			const factor = dist / startDist;
			scale = clamp(startScale * factor, minScale, maxScale);
			applyTransform();
			viewport.scrollLeft = midStart.contentX * scale - midStart.x;
			viewport.scrollTop = midStart.contentY * scale - midStart.y;
		}
	}, { passive: false });

	viewport.addEventListener('touchend', () => { touchMode = 'none'; });

	function stepZoom(factor) {
		const rect = viewport.getBoundingClientRect();
		const cx = rect.left + rect.width / 2;
		const cy = rect.top + rect.height / 2;
		zoomAt(cx, cy, scale * factor);
	}

	btnIn && btnIn.addEventListener('click', () => stepZoom(1.2));
	btnOut && btnOut.addEventListener('click', () => stepZoom(1/1.2));
	btnReset && btnReset.addEventListener('click', () => {
		scale = 1;
		applyTransform();
		viewport.scrollLeft = 0;
		viewport.scrollTop = 0;
	});

	applyTransform();

	(async function loadHotspots(){
		if (!hotspotsLayer) return;
		try {
			const res = await fetch('hotspots.php');
			const data = await res.json();
			const items = (data && data.hotspots) ? data.hotspots : [];
			// Measure image slice positions (left offsets and widths)
			const imgs = Array.from(content.querySelectorAll('img'));
			const slices = imgs.map(img => ({ left: img.offsetLeft, width: img.clientWidth }));
			const contentWidth = content.scrollWidth || slices.reduce((s, x) => s + x.width, 0);
			hotspotsLayer.style.width = contentWidth + 'px';

			function coordToPercentX(x) {
				let px = Number(x);
				if (!isFinite(px)) px = 0;
				if (px <= 100) {
					return Math.max(0, Math.min(100, px));
				} else {
					const pct = (px / contentWidth) * 100;
					return Math.max(0, Math.min(100, pct));
				}
			}
			function coordToPercentY(y) {
				// Percent [0..100] or pixels
				let px = Number(y);
				const h = content.clientHeight || 500;
				if (!isFinite(px)) px = 0;
				if (px <= 100) return Math.max(0, Math.min(100, px));
				return Math.max(0, Math.min(100, (px / h) * 100));
			}

			items.forEach(h => {
				const xPct = coordToPercentX(h.x_coord);
				const yPct = coordToPercentY(h.y_coord);
				const xPx = (xPct / 100) * contentWidth;
				let sliceIndex = 0;
				for (let i = 0; i < slices.length; i++) {
					const s = slices[i];
					if (xPx >= s.left && xPx < s.left + s.width) { sliceIndex = i + 1; break; }
				}
				const node = document.createElement('div');
				node.className = 'hotspot';
				node.style.left = xPx + 'px';
				const yPx = (yPct / 100) * (content.clientHeight || 500);
				node.style.top = yPx + 'px';
				node.dataset.slice = String(sliceIndex);
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.textContent = h.naam || ('Hotspot ' + (h.id ?? ''));
				btn.addEventListener('click', (e) => {
					e.stopPropagation();
					const existing = node.querySelector('.popup');
					if (existing) { existing.remove(); return; }
					const pop = document.createElement('div');
					pop.className = 'popup';
					const close = document.createElement('button');
					close.className = 'close'; close.innerHTML = '&times;';
					close.addEventListener('click', () => pop.remove());
					const title = document.createElement('h4');
					title.textContent = h.naam || 'Hotspot';
					const adminMode = content.classList.contains('admin');
					let desc;
					if (adminMode) {
						desc = document.createElement('textarea');
						desc.style.width = '100%';
						desc.rows = 4;
						desc.value = h.beschrijving || '';
						const save = document.createElement('button');
						save.textContent = 'Opslaan';
						save.className = 'mode';
						save.style.marginTop = '6px';
						save.addEventListener('click', async () => {
							await fetch('admin_api.php?action=save_hotspot', {
								method: 'POST', headers: {'Content-Type':'application/json'},
								body: JSON.stringify({ id: h.id, naam: h.naam, beschrijving: desc.value, x_coord: (parseFloat(node.style.left)||0), y_coord: (parseFloat(node.style.top)||0) })
							});
							pop.remove();
						});
						pop.appendChild(save);
					} else {
						desc = document.createElement('p');
						desc.textContent = h.beschrijving || '';
					}
					const meta = document.createElement('div');
					meta.className = 'meta';
					meta.textContent = 'Afbeelding ' + sliceIndex;
					pop.appendChild(close);
					pop.appendChild(title);
					pop.appendChild(desc);
					pop.appendChild(meta);
					node.appendChild(pop);
				});
				node.appendChild(btn);
				hotspotsLayer.appendChild(node);

				if (content.classList.contains('admin')) {
					let dragging = false; let sx=0, sy=0, sl=0, st=0;
					node.addEventListener('mousedown', (ev) => {
						if (ev.target.tagName.toLowerCase() === 'textarea') return;
						dragging = true; node.classList.add('dragging');
						sx = ev.clientX; sy = ev.clientY;
						sl = parseFloat(node.style.left)||0; st = parseFloat(node.style.top)||0;
						ev.preventDefault();
					});
					window.addEventListener('mousemove', (ev) => {
						if (!dragging) return;
						const dx = (ev.clientX - sx); const dy = (ev.clientY - sy);
						node.style.left = (sl + dx) + 'px';
						node.style.top = (st + dy) + 'px';
					});
					window.addEventListener('mouseup', async () => {
						if (!dragging) return; dragging = false; node.classList.remove('dragging');
						await fetch('admin_api.php?action=save_hotspot', {
							method: 'POST', headers: {'Content-Type':'application/json'},
							body: JSON.stringify({ id: h.id, naam: h.naam, beschrijving: h.beschrijving, x_coord: parseFloat(node.style.left)||0, y_coord: parseFloat(node.style.top)||0 })
						});
					});
				}
			});
		} catch (e) {
			console.warn('Failed to load hotspots:', e);
		}
	})();

	
	const track = document.querySelector('.minimap-track');
	const miniImgs = track ? Array.from(track.querySelectorAll('img')) : [];

	function layoutMinimap() {
		if (!track || miniImgs.length === 0) return;
		const containerWidth = track.clientWidth || window.innerWidth;
		const aspects = miniImgs.map(img => {
			const nw = img.naturalWidth || img.width;
			const nh = img.naturalHeight || img.height || 1;
			return nw / nh;
		});
		const totalAspect = aspects.reduce((a, b) => a + b, 0);
		const targetHeight = Math.max(40, Math.floor(containerWidth / totalAspect));
		miniImgs.forEach(img => { img.style.height = targetHeight + 'px'; });
	}

	if (miniImgs.length) {
		let remaining = miniImgs.length;
		miniImgs.forEach(img => {
			if (img.complete) {
				if (--remaining === 0) layoutMinimap();
			} else {
				img.addEventListener('load', () => { if (--remaining === 0) layoutMinimap(); });
				img.addEventListener('error', () => { if (--remaining === 0) layoutMinimap(); });
			}
		});
		window.addEventListener('resize', () => layoutMinimap());
	}
	if (track) {
		track.addEventListener('click', (e) => {
			const rect = track.getBoundingClientRect();
			const x = e.clientX - rect.left;
			const proportion = x / rect.width;
			const contentWidth = content.scrollWidth * scale;
			const maxScroll = Math.max(0, contentWidth - viewport.clientWidth);
			viewport.scrollLeft = proportion * maxScroll;
		});
	}
});

(function setupHeaderLogoSwap(){
	const logoImg = document.getElementById('huaLogo');
	if (!logoImg) return;
	const smallSrc = logoImg.getAttribute('data-small') || logoImg.src;
	const largeSrc = logoImg.getAttribute('data-large') || smallSrc;

	function update() {
		const atTop = (window.scrollY || document.documentElement.scrollTop || 0) <= 0;
		const target = atTop ? largeSrc : smallSrc;
		if (logoImg.src !== new URL(target, window.location.href).href) {
			logoImg.src = target;
		}
		logoImg.classList.toggle('logo-large', atTop);
	}
	window.addEventListener('scroll', update, { passive: true });
	window.addEventListener('load', update);
	update();
})();

  // Drag and drop ordering UI
  const list = document.getElementById('reorderList');
  let dragEl = null;
  list.addEventListener('dragstart', (e) => {
    if (e.target.classList.contains('reorder-item')) {
      dragEl = e.target;
      e.dataTransfer.effectAllowed = 'move';
    }
  });
  list.addEventListener('dragover', (e) => {
    e.preventDefault();
    const after = Array.from(list.children).find(ch => {
      const box = ch.getBoundingClientRect();
      return e.clientX < box.left + box.width/2;
    });
    if (after) list.insertBefore(dragEl, after); else list.appendChild(dragEl);
  });
  list.addEventListener('dragend', () => { dragEl = null; });

  document.getElementById('saveOrder').addEventListener('click', async () => {
    const payload = Array.from(list.children).map((el, i) => ({ img: el.dataset.img, position: i+1 }));
    const res = await fetch('admin_api.php?action=save_order', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const out = await res.json();
    alert(out.status || 'Opgeslagen');
    // Redirect to panorama to immediately view the new order
    if (out.status === 'ok') {
      window.location.href = 'index.php';
    }
  });
  // Arrow click handlers: move items left/right and optionally auto-save
  function moveItem(buttonEl, dir) {
    if (!buttonEl) return;
    const item = buttonEl.closest('.reorder-item');
    if (!item) return;
    const parent = item.parentElement;
    if (dir === 'left') {
      const prev = item.previousElementSibling;
      if (prev) parent.insertBefore(item, prev);
    } else if (dir === 'right') {
      const next = item.nextElementSibling;
      if (next) parent.insertBefore(next, item);
    }
  }

  list.querySelectorAll('.arrow-left').forEach(btn => {
    btn.addEventListener('click', () => moveItem(btn, 'left'));
  });
  list.querySelectorAll('.arrow-right').forEach(btn => {
    btn.addEventListener('click', () => moveItem(btn, 'right'));
  });

  // Optional: auto-save after arrow movement (comment out if not desired)
  async function saveCurrentOrder() {
    const payload = Array.from(list.children).map((el, i) => ({ img: el.dataset.img, position: i+1 }));
    const res = await fetch('admin_api.php?action=save_order', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    try { await res.json(); } catch {}
  }
  list.addEventListener('click', async (e) => {
    if (e.target.classList.contains('arrow-left') || e.target.classList.contains('arrow-right')) {
      await saveCurrentOrder();
    }
  });

  // Toggle dragging instructions link to panorama page (button kept for parity)
  document.getElementById('toggleDrag').addEventListener('click', () => {
    window.location.href = 'index.php?admin=1';
  });

  // Save hotspot description inline in admin panel
  document.querySelectorAll('.saveHotspot').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = parseInt(btn.dataset.id, 10);
      const textarea = document.querySelector('textarea[data-id="' + id + '"]');
      const beschrijving = textarea ? textarea.value : '';
      const xInput = document.querySelector('input.coord-x[data-id="' + id + '"]');
      const yInput = document.querySelector('input.coord-y[data-id="' + id + '"]');
      const x = xInput ? parseFloat(xInput.value) : undefined;
      const y = yInput ? parseFloat(yInput.value) : undefined;
      const payload = { id, beschrijving };
      if (!Number.isNaN(x) && !Number.isNaN(y)) { payload.x_coord = x; payload.y_coord = y; }
      const res = await fetch('admin_api.php?action=save_hotspot', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const out = await res.json();
      alert(out.status === 'ok' ? 'Hotspot opgeslagen' : 'Opslaan mislukt');
    });
  });
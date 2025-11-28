document.addEventListener('DOMContentLoaded', () => {
	const viewport = document.querySelector('.panorama-viewport');
	const content = document.querySelector('.panorama-viewport .panorama');
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

	// Wheel: only zoom when Ctrl is held (or pinch gesture)
	viewport.addEventListener('wheel', (e) => {
		if (e.ctrlKey) {
			e.preventDefault();
			const zoomIntensity = 0.0015;
			const factor = Math.exp(-e.deltaY * zoomIntensity);
			zoomAt(e.clientX, e.clientY, scale * factor);
		}
		// otherwise, let native scrolling happen
	}, { passive: false });

	// Drag to pan by scrolling (optional, smooth)
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

	// Touch: use native one-finger scroll; implement pinch to zoom
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
		} else {
			touchMode = 'none';
		}
	}, { passive: true });

	viewport.addEventListener('touchmove', (e) => {
		if (touchMode === 'pinch' && e.touches.length === 2) {
			e.preventDefault();
			const dist = distance(e.touches[0], e.touches[1]);
			const factor = dist / startDist;
			let nextScale = clamp(startScale * factor, minScale, maxScale);
			scale = nextScale;
			applyTransform();
			viewport.scrollLeft = midStart.contentX * scale - midStart.x;
			viewport.scrollTop = midStart.contentY * scale - midStart.y;
		}
	}, { passive: false });

	viewport.addEventListener('touchend', () => {
		if (touchMode === 'pinch') {
			touchMode = 'none';
		}
	});

	// Buttons
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
		// keep scroll within bounds
		viewport.scrollLeft = 0;
		viewport.scrollTop = 0;
	});

	// Initial state
	applyTransform();

	// Minimap click-to-jump
	const minimap = document.querySelector('.minimap');
	const track = document.querySelector('.minimap-track');
	const miniImgs = track ? Array.from(track.querySelectorAll('img')) : [];

	// Compute minimap thumbnail height so all images fit within page width
	function layoutMinimap() {
		if (!track || miniImgs.length === 0) return;
		const containerWidth = track.clientWidth || (minimap ? minimap.clientWidth : window.innerWidth);
		// Ensure natural sizes are available
		const aspects = miniImgs.map(img => {
			const nw = img.naturalWidth || img.width;
			const nh = img.naturalHeight || img.height || 1;
			return nw / nh;
		});
		const totalAspect = aspects.reduce((a, b) => a + b, 0);
		const targetHeight = Math.max(40, Math.floor(containerWidth / totalAspect));
		miniImgs.forEach(img => { img.style.height = targetHeight + 'px'; });
	}

	// Run after images load and on resize
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

	// Click on minimap to jump
	if (track) {
		track.addEventListener('click', (e) => {
			const rect = track.getBoundingClientRect();
			const x = e.clientX - rect.left;
			const proportion = x / rect.width; // 0..1 along minimap
			const contentWidth = content.scrollWidth * scale;
			const maxScroll = Math.max(0, contentWidth - viewport.clientWidth);
			viewport.scrollLeft = proportion * maxScroll;
		});
	}
});

// Header logo swap on scroll: large at very top, small once user scrolls down
(function setupHeaderLogoSwap(){
	const logoImg = document.getElementById('huaLogo');
	if (!logoImg) return;
	const smallSrc = logoImg.getAttribute('data-small') || logoImg.src;
	const largeSrc = logoImg.getAttribute('data-large') || smallSrc;

	function update() {
		const atTop = (window.scrollY || document.documentElement.scrollTop || 0) <= 0;
		// Swap source only if different to avoid flicker
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

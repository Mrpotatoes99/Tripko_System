<?php include_once __DIR__ . '/../includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Route Finder (User)</title>
	<style>
		/* Keep page layout simple: navbar from include sits above, iframe fills remaining space */
		html,body{height:100%;margin:0;font-family:Inter,system-ui,Arial,sans-serif}
		.rf-container{height:calc(100vh - 64px); /* fallback nav height; navbar styles adjust this */;}
		.rf-iframe{width:100%;height:100%;border:0;display:block}
		.rf-toolbar{display:flex;align-items:center;gap:12px;padding:10px 16px;background:#fff;border-bottom:1px solid #eee}
		.rf-open-btn{padding:8px 12px;border-radius:8px;border:1px solid #0d6efd;background:#0d6efd;color:#fff;text-decoration:none}
		@media (max-width:640px){ .rf-container{height:calc(100vh - 88px);} }
	</style>
</head>
<body>
	<main>

		<div class="rf-container" id="rfContainer">
			<div id="rfLoader" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(255,255,255,0.9),rgba(250,250,250,0.8));z-index:40;">
				<div style="text-align:center;">
					<div style="font-size:14px;color:#1f2937;margin-bottom:8px;font-weight:600">Loading Route Finder…</div>
					<div style="width:40px;height:40px;border-radius:50%;border:4px solid #e6eef0;border-top-color:#0d6efd;animation:rfspin 900ms linear infinite;margin:0 auto"></div>
				</div>
			</div>
			<iframe id="routeFinderFrame" class="rf-iframe" title="Route Finder" src="/tripko-system/tripko-frontend/file_html/Super%20Admin_archive/routeFinder.php" sandbox="allow-scripts allow-same-origin allow-forms allow-modals" allow="geolocation; clipboard-write; fullscreen"></iframe>
			<div id="rfError" style="display:none;position:absolute;inset:0;z-index:50;align-items:center;justify-content:center;background:rgba(255,255,255,0.96);">
				<div style="text-align:center;padding:20px;max-width:420px;margin:auto;border-radius:10px;box-shadow:0 6px 22px rgba(0,0,0,0.08)">
					<div style="font-size:16px;font-weight:700;color:#111;margin-bottom:8px">Failed to load map</div>
					<div style="color:#334155;margin-bottom:12px">The embedded route finder couldn't be loaded. You can open it in a new tab instead.</div>
					<a id="rfOpenDirect" class="rf-open-btn" href="/tripko-system/tripko-frontend/file_html/Super%20Admin_archive/routeFinder.php" target="_blank" rel="noopener">Open route finder in a new tab</a>
				</div>
			</div>
		</div>
	</main>

	<script>
		// Polished iframe sizing: use ResizeObserver when available to measure navbar height
		function adjustIframeHeight(navH, tbH){
			try{
				const container = document.getElementById('rfContainer');
				const total = (navH || 0) + (tbH || 0);
				container.style.height = `calc(100vh - ${total}px)`;
			}catch(e){ /* ignore */ }
		}

		(function(){
			const nav = document.querySelector('.tk-navbar');
			// initial measurement (toolbar removed -> tbH = 0)
			adjustIframeHeight(nav ? nav.getBoundingClientRect().height : 0, 0);
			// Use ResizeObserver to react to navbar changes (mobile toggles, scrolled compact state)
			try{
				if (window.ResizeObserver && nav) {
					const ro = new ResizeObserver(entries => {
						for (const ent of entries) {
							adjustIframeHeight(ent.contentRect.height, 0);
						}
					});
					ro.observe(nav);
				}
			}catch(e){ /* ignore */ }
			window.addEventListener('resize', () => adjustIframeHeight(nav ? nav.getBoundingClientRect().height : 0, 0));
		})();

		// Loader and error handling for embedded iframe
		(function(){
			const frame = document.getElementById('routeFinderFrame');
			const loader = document.getElementById('rfLoader');
			const err = document.getElementById('rfError');
			let settled = false;
			const LOADER_TIMEOUT = 10000; // fail after 10s
			const to = setTimeout(() => {
				if (!settled) {
					try { loader.style.display = 'none'; } catch(e){}
					try { err.style.display = 'flex'; } catch(e){}
				}
			}, LOADER_TIMEOUT);

			frame.addEventListener('load', function(){
				settled = true; clearTimeout(to);
				try { loader.style.display = 'none'; } catch(e){}
				try { err.style.display = 'none'; } catch(e){}
				// Remove admin-only UI from the embedded route finder (header, admin edit areas, add-pin button, attribution)
				try {
					const iframeDoc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
					if (iframeDoc) {
						const adminSelectors = ['.header', '#togglePinMode', '.modal-admin-edit', '.map-btn--topright', '.header-sub'];
						adminSelectors.forEach(sel => {
							try { Array.from(iframeDoc.querySelectorAll(sel)).forEach(el => el.remove()); } catch(e) { /* ignore */ }
						});
						// remove footer attribution text if present
						try {
							Array.from(iframeDoc.querySelectorAll('div')).forEach(d => {
								if (d.textContent && /Icons made by/i.test(d.textContent)) d.remove();
							});
						} catch(e){}
						// In case UI is added later by scripts inside iframe, try again shortly
						setTimeout(() => {
							try { adminSelectors.forEach(sel => { Array.from(iframeDoc.querySelectorAll(sel)).forEach(el => el.remove()); }); } catch(e){}
						}, 300);
						setTimeout(() => {
							try { adminSelectors.forEach(sel => { Array.from(iframeDoc.querySelectorAll(sel)).forEach(el => el.remove()); }); } catch(e){}
						}, 1200);
					}
				} catch(e) { /* swallow */ }
			});
			frame.addEventListener('error', function(){
				settled = true; clearTimeout(to);
				try { loader.style.display = 'none'; } catch(e){}
				try { err.style.display = 'flex'; } catch(e){}
			});

			// If iframe is same-origin we could forward focus and keyboard hints (no toolbar present)
		})();

		// small spinner keyframes
		const styleEl = document.createElement('style');
		styleEl.innerHTML = '@keyframes rfspin{to{transform:rotate(360deg)}}';
		document.head.appendChild(styleEl);
	</script>
</body>
</html>

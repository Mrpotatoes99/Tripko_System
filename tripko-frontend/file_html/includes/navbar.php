<?php
// Modern TripAdvisor-inspired navbar (translucent, spacious, active highlighting, accessible).
if (!function_exists('renderNavbar')) {
    function renderNavbar() {
        // Determine active script for highlighting
        $current = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH));

        // Helper to mark active - accepts array of possible filenames
        $isActive = function(array $names) use ($current) {
            return in_array($current, $names) ? 'active' : '';
        };

    // Compute relative prefix so links work from both root-level pages and nested 'user side' directory
    $scriptDirRaw = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = urldecode($scriptDirRaw); // ensure spaces decoded
    $isInUserSide = (stripos($scriptDir, 'user side') !== false);
    // If current page already inside 'user side', internal user pages will not be prefixed
    $userSidePrefix = $isInUserSide ? '' : 'user side/';

                // Output head links (fonts, icons, stylesheet)
                echo <<<HEAD
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/modern_navbar.css?v=20251103">
<link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/responsive.css?v=20251103">
<link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/mobile-userside.css?v=20251108">
<script src="/tripko-system/tripko-frontend/file_js/mobile-viewport-fix.js?v=20251103"></script>
HEAD;

                // Active / dropdown states
                $placesActive = $isActive([
                        'places-to-go.php','islands-to-go.php','waterfalls-to-go.php','caves-to-go.php','churches-to-go.php','festivals-to-go.php'
                ]);

                // Main HTML
                $html = <<<HTML
<nav class="tk-navbar collapse-on-hover" role="navigation" aria-label="Main navigation">
    <div class="tk-nav-inner">
    <a href="{$userSidePrefix}homepage.php" class="tk-logo" aria-label="TripKo Pangasinan Home">
            <span class="logo-icon"><i class="bx bxs-compass"></i></span>
            <span class="logo-text">TripKo <span class="accent">Pangasinan</span></span>
        </a>
        <button class="tk-mobile-toggle" aria-expanded="false" aria-controls="tkPrimaryNav" aria-label="Toggle navigation">
            <i class="bx bx-menu"></i>
        </button>
        <div id="tkPrimaryNav" class="tk-links-wrapper" data-close-on-click="true">
            <ul class="tk-links" role="menubar">
                <li role="none"><a role="menuitem" class="{$isActive(['homepage.php'])}" href="{$userSidePrefix}homepage.php"><i class="bx bxs-home"></i><span>Home</span></a></li>
                <li class="has-dropdown simple {$placesActive}" role="none">
                    <button class="dropdown-trigger" aria-haspopup="true" aria-expanded="false"><i class="bx bxs-map-alt"></i><span>Places to Go</span><i class="bx bx-chevron-down chevron"></i></button>
                    <ul class="dropdown-simple" role="menu">
                        <li><a role="menuitem" href="{$userSidePrefix}places-to-go.php">Beaches</a></li>
                        <li><a role="menuitem" href="{$userSidePrefix}islands-to-go.php">Islands</a></li>
                        <li><a role="menuitem" href="{$userSidePrefix}waterfalls-to-go.php">Waterfalls</a></li>
                        <li><a role="menuitem" href="{$userSidePrefix}caves-to-go.php">Caves</a></li>
                        <li><a role="menuitem" href="{$userSidePrefix}churches-to-go.php">Churches</a></li>
                        <li><a role="menuitem" href="{$userSidePrefix}festivals-to-go.php">Festivals</a></li>
                    </ul>
                </li>
                <li role="none"><a role="menuitem" class="{$isActive(['things-to-do.php'])}" href="{$userSidePrefix}things-to-do.php"><i class="bx bxs-calendar-star"></i><span>Things to Do</span></a></li>
                <li role="none"><a role="menuitem" class="{$isActive(['route-finder.php'])}" href="/tripko-system/tripko-frontend/file_html/route-finder.php"><i class="bx bxs-bus"></i><span>Route Finder</span></a></li>
                <li role="none"><a role="menuitem" href="https://seepangasinan.com/directory/" target="_blank" rel="noopener"><i class="bx bxs-book-content"></i><span>Directory</span><i class="bx bx-link-external ext"></i></a></li>
                <li role="none"><a role="menuitem" class="{$isActive(['tourist_capacity.php'])}" href="{$userSidePrefix}tourist_capacity.php"><i class="bx bxs-check-circle"></i><span>Spot Status</span></a></li>
                <li role="none" class="separator" aria-hidden="true"></li>
                <li class="has-dropdown simple" role="none">
                    <button class="dropdown-trigger" aria-haspopup="true" aria-expanded="false"><i class="bx bxs-user"></i><span>Profile</span><i class="bx bx-chevron-down chevron"></i></button>
                    <ul class="dropdown-simple" role="menu">
                        <li><a role="menuitem" href="{$userSidePrefix}profile.php">View Profile</a></li>
                        <li><a role="menuitem" class="logout-link" href="/tripko-system/tripko-backend/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
HTML;
                echo $html;

                // JavaScript block
                echo <<<JS
<script>
(function(){
    const nav = document.querySelector('.tk-navbar');
    if(!nav) return;
    const mobileToggle = nav.querySelector('.tk-mobile-toggle');
    const linksWrapper = nav.querySelector('#tkPrimaryNav');
    const dropdowns = nav.querySelectorAll('.has-dropdown.simple');
    
    if(mobileToggle){
        mobileToggle.addEventListener('click', () => {
            const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            mobileToggle.setAttribute('aria-expanded', String(!expanded));
            document.documentElement.classList.toggle('nav-open', !expanded);
            linksWrapper.classList.toggle('open', !expanded);
        });
    }

    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const list = dropdown.querySelector('.dropdown-simple');
        if(!trigger || !list) return;
        
        const closeDropdown = () => { trigger.setAttribute('aria-expanded','false'); dropdown.classList.remove('show'); };
        const openDropdown = () => { trigger.setAttribute('aria-expanded','true'); dropdown.classList.add('show'); };
        
        trigger.addEventListener('click', e => { e.preventDefault(); dropdown.classList.contains('show') ? closeDropdown() : openDropdown(); });
        trigger.addEventListener('keydown', e => { if(e.key === 'Escape') { closeDropdown(); trigger.focus(); } });
        list.addEventListener('keydown', e => { if(e.key === 'Escape') { closeDropdown(); trigger.focus(); } });
        document.addEventListener('click', e => { if(!e.target.closest('.has-dropdown.simple')) closeDropdown(); });
        let hoverTimer; const OPEN_DELAY=80, CLOSE_DELAY=260;
        dropdown.addEventListener('mouseenter', () => { if(window.innerWidth > 1024){ clearTimeout(hoverTimer); hoverTimer = setTimeout(openDropdown, OPEN_DELAY); } });
        dropdown.addEventListener('mouseleave', () => { if(window.innerWidth > 1024){ clearTimeout(hoverTimer); hoverTimer = setTimeout(closeDropdown, CLOSE_DELAY); } });
    });
    nav.addEventListener('click', e => { const a = e.target.closest('a'); if(a && linksWrapper.classList.contains('open')) { mobileToggle.click(); } });
    const handleScroll = () => { if(window.scrollY > 10) nav.classList.add('scrolled'); else nav.classList.remove('scrolled'); };
    document.addEventListener('scroll', handleScroll, {passive:true}); handleScroll();
})();
</script>
JS;
    }
}
?>

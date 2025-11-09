// Mobile Viewport Fix & Touch Optimization for TripKo
(function(){
  // Fix for mobile browser dynamic toolbar affecting 100vh
  function setVh(){
    var vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', vh + 'px');
  }
  
  setVh();
  window.addEventListener('resize', setVh);
  window.addEventListener('orientationchange', function(){
    // Delay slightly to allow reflow after orientation change
    setTimeout(setVh, 150);
  });
  
  // Fallback on visibility change (Android address bar hide/show)
  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) setTimeout(setVh, 100);
  });

  // Prevent double-tap zoom on buttons and links
  var lastTouchEnd = 0;
  document.addEventListener('touchend', function(e){
    var now = Date.now();
    if (now - lastTouchEnd <= 300) {
      e.preventDefault();
    }
    lastTouchEnd = now;
  }, false);

  // Add touch-active class for better tap feedback
  document.addEventListener('touchstart', function(e){
    var target = e.target.closest('a, button, [role="button"]');
    if (target) {
      target.classList.add('touch-active');
    }
  }, { passive: true });

  document.addEventListener('touchend', function(e){
    var target = e.target.closest('a, button, [role="button"]');
    if (target) {
      setTimeout(function(){
        target.classList.remove('touch-active');
      }, 150);
    }
  }, { passive: true });

  // Smooth scroll behavior
  if ('scrollBehavior' in document.documentElement.style) {
    document.documentElement.style.scrollBehavior = 'smooth';
  }

  // Detect iOS and add class
  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  if (isIOS) {
    document.documentElement.classList.add('ios');
  }

  // Detect Android and add class
  var isAndroid = /Android/.test(navigator.userAgent);
  if (isAndroid) {
    document.documentElement.classList.add('android');
  }

  // Add mobile class if on mobile device
  var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  if (isMobile) {
    document.documentElement.classList.add('mobile-device');
  }

  // Close mobile menu when clicking overlay
  document.addEventListener('click', function(e){
    if (document.documentElement.classList.contains('nav-open') && 
        !e.target.closest('.tk-links-wrapper') && 
        !e.target.closest('.tk-mobile-toggle')) {
      var toggle = document.querySelector('.tk-mobile-toggle');
      if (toggle) {
        toggle.click();
      }
    }
  });

  // Prevent body scroll on iOS when menu is open
  var scrollPosition = 0;
  var observer = new MutationObserver(function(mutations){
    mutations.forEach(function(mutation){
      if (mutation.attributeName === 'class') {
        var hasNavOpen = document.documentElement.classList.contains('nav-open');
        if (hasNavOpen) {
          scrollPosition = window.pageYOffset;
          document.body.style.overflow = 'hidden';
          document.body.style.position = 'fixed';
          document.body.style.top = '-' + scrollPosition + 'px';
          document.body.style.width = '100%';
        } else {
          document.body.style.removeProperty('overflow');
          document.body.style.removeProperty('position');
          document.body.style.removeProperty('top');
          document.body.style.removeProperty('width');
          window.scrollTo(0, scrollPosition);
        }
      }
    });
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  });

})();

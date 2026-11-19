// Premium scroll reveal and entry animations controller
(function () {
  'use strict';

  // Initialize IntersectionObserver for scroll-reveal animations
  const setupScrollReveal = () => {
    if (!('IntersectionObserver' in window)) {
      // Fallback: immediately activate all anims if browser doesn't support IntersectionObserver
      document.querySelectorAll('.reveal, .reveal-stagger').forEach((el) => {
        el.classList.add('reveal-active');
      });
      return;
    }

    const observerOptions = {
      root: null,
      rootMargin: '0px 0px -10% 0px', // trigger slightly before entering viewport fully
      threshold: 0.05
    };

    const observer = new IntersectionObserver((entries, self) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-active');
          self.unobserve(entry.target); // trigger animation only once
        }
      });
    }, observerOptions);

    // Track all reveal targets
    document.querySelectorAll('.reveal, .reveal-stagger').forEach((el) => {
      observer.observe(el);
    });
  };

  // Add simple page loader fade-in
  const setupPageFade = () => {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.6s ease';
    
    // Ensure we fade in even if DOMContentLoaded already fired
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
      document.body.style.opacity = '1';
    } else {
      window.addEventListener('DOMContentLoaded', () => {
        document.body.style.opacity = '1';
      });
    }
  };

  // Lightweight parallax background elements
  const setupHeroParallax = () => {
    const heroArt = document.querySelector('.hero__art');
    if (!heroArt) return;
    
    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      if (scrolled < 800) {
        // Subtle offset shifts
        heroArt.style.transform = `translateY(${scrolled * 0.08}px)`;
      }
    }, { passive: true });
  };

  // Init
  setupPageFade();
  
  if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', () => {
      setupScrollReveal();
      setupHeroParallax();
    });
  } else {
    // DOMContentLoaded has already fired
    setupScrollReveal();
    setupHeroParallax();
  }
})();

// Header: mobile menu toggle + scroll shadow.
(function () {
  'use strict';
  const header = document.getElementById('ghHeader');
  const toggle = document.querySelector('.gh-nav__toggle');
  const nav = document.getElementById('ghNav');

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    nav.addEventListener('click', (e) => {
      if (e.target.tagName === 'A') {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (header) {
    let isScrolled = false;
    const onScroll = () => {
      const scrollY = window.scrollY;
      if (!isScrolled && scrollY > 60) {
        isScrolled = true;
        header.classList.add('is-scrolled');
      } else if (isScrolled && scrollY < 10) {
        isScrolled = false;
        header.classList.remove('is-scrolled');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
})();

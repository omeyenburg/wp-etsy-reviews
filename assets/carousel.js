(function() {
    'use strict';

    function initEtsyCarousel(widget) {
        const track = widget.querySelector('.etsy-carousel-track');
        const originalCards = Array.from(widget.querySelectorAll('.etsy-review-card'));
        const prevBtn = widget.querySelector('.etsy-prev');
        const nextBtn = widget.querySelector('.etsy-next');
        const autoplayDelay = parseInt(widget.getAttribute('data-autoplay')) || 6;

        if (!track || originalCards.length === 0) {
            return;
        }

        const originalCardsCount = originalCards.length;
        let currentIndex = 0;
        let autoplayTimer = null;
        let isTransitioning = false;
        let cardsPerView = getCardsPerView();

        // Clone original cards to create infinite loop effect
        // Prepend clones in reverse order for smooth backward scrolling
        originalCards.slice().reverse().forEach(card => {
            const clone = card.cloneNode(true);
            track.insertBefore(clone, track.firstChild);
        });

        // Append clones for forward scrolling
        originalCards.forEach(card => {
            const clone = card.cloneNode(true);
            track.appendChild(clone);
        });

        function getCardsPerView() {
            if (window.innerWidth < 768) {
                return 1;
            } else if (window.innerWidth <= 1300) {
                return 2;
            } else {
                return 3;
            }
        }

        function updatePosition(animate = true) {
            if (!animate) {
                track.style.transition = 'none';
            } else {
                track.style.transition = 'transform 300ms ease-out';
            }

            // Each card takes up (100 / cardsPerView)% of the viewport
            // To move by one card, we translate by that percentage
            const cardWidthPercent = 100 / cardsPerView;
            const offset = -(currentIndex * cardWidthPercent);
            track.style.transform = `translateX(${offset}%)`;

            if (!animate) {
                track.offsetHeight;
                track.style.transition = 'transform 300ms ease-out';
            }
        }

        function handleInfiniteLoop() {
            if (currentIndex >= originalCardsCount * 2) {
                currentIndex = originalCardsCount;
                updatePosition(false);
            }
            else if (currentIndex < originalCardsCount) {
                currentIndex = originalCardsCount * 2 - 1;
                updatePosition(false);
            }
        }

        function next() {
            if (isTransitioning) return;
            isTransitioning = true;
            currentIndex++;
            updatePosition(true);
            setTimeout(() => {
                isTransitioning = false;
                handleInfiniteLoop();
            }, 300);
        }

        function prev() {
            if (isTransitioning) return;
            isTransitioning = true;
            currentIndex--;
            updatePosition(true);
            setTimeout(() => {
                isTransitioning = false;
                handleInfiniteLoop();
            }, 300);
        }

        function startAutoplay() {
            stopAutoplay();
            if (autoplayDelay > 0) {
                autoplayTimer = setInterval(next, autoplayDelay * 1000);
            }
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        function resetAutoplay() {
            startAutoplay();
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                next();
                resetAutoplay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prev();
                resetAutoplay();
            });
        }

        let touchStartX = 0;
        let touchEndX = 0;

        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            const swipeThreshold = 50;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    next();
                } else {
                    prev();
                }
            }
            resetAutoplay();
        }, { passive: true });

        widget.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                prev();
                resetAutoplay();
            } else if (e.key === 'ArrowRight') {
                next();
                resetAutoplay();
            }
        });

        widget.addEventListener('mouseenter', stopAutoplay);
        widget.addEventListener('mouseleave', startAutoplay);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        });

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const newCardsPerView = getCardsPerView();
                if (newCardsPerView !== cardsPerView) {
                    cardsPerView = newCardsPerView;
                    updatePosition(false);
                }
            }, 250);
        });

        // Start at the beginning of original cards
        currentIndex = originalCardsCount;
        updatePosition(false);

        startAutoplay();
    }

    function initAllCarousels() {
        const carousels = document.querySelectorAll('.etsy-reviews-carousel');
        carousels.forEach(initEtsyCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllCarousels);
    } else {
        initAllCarousels();
    }

    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        if (node.classList && node.classList.contains('etsy-reviews-carousel')) {
                            initEtsyCarousel(node);
                        } else if (node.querySelectorAll) {
                            const carousels = node.querySelectorAll('.etsy-reviews-carousel');
                            carousels.forEach(initEtsyCarousel);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();

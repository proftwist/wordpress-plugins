/**
 * Scroll Progress Bar - Исправленный фронтенд скрипт
 * Автор: Владимир Бычко
 * Сайт: bychko.ru
 */
(function($) {
    'use strict';

    class ScrollProgressBar {
        constructor() {
            this.progressBar = document.getElementById('scroll-progress-bar');
            this.ticking = false;
            this.init();
        }

        init() {
            console.log('Scroll Progress Bar: Инициализация началась');

            if (!this.progressBar) {
                console.error('Scroll Progress Bar: Элемент #scroll-progress-bar не найден!');
                return;
            }

            console.log('Scroll Progress Bar: Элемент найден', this.progressBar);
            this.bindEvents();
            this.updateProgress(); // Инициализируем при загрузке

            console.log('Scroll Progress Bar: Инициализация завершена');
        }

        bindEvents() {
            // Обработчик скролла с requestAnimationFrame для производительности
            window.addEventListener('scroll', () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => this.updateProgress());
                    this.ticking = true;
                }
            });

            // Обновляем при изменении размера окна
            window.addEventListener('resize', () => this.updateProgress());

            // Инициализируем при полной загрузке страницы
            window.addEventListener('load', () => {
                console.log('Scroll Progress Bar: Страница загружена');
                this.updateProgress();
            });
        }

        updateProgress() {
            if (!this.progressBar) return;

            const windowHeight = window.innerHeight;
            const documentHeight = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            ) - windowHeight;

            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

            // Рассчитываем прогресс (0-100%)
            const progress = documentHeight > 0 ? (scrollPosition / documentHeight) * 100 : 0;

            // Обновляем ширину прогресс-бара
            this.progressBar.style.width = Math.min(Math.max(progress, 0), 100) + '%';

            // Добавляем/убираем класс при достижении 100%
            if (progress >= 99.5) {
                this.progressBar.classList.add('completed');
            } else {
                this.progressBar.classList.remove('completed');
            }

            // Логируем для отладки (можно убрать в продакшене)
            if (scrollPosition % 200 < 10) {
                console.log('Scroll Progress Bar:', {
                    scrollPosition,
                    documentHeight,
                    progress: progress.toFixed(2) + '%',
                    width: this.progressBar.style.width
                });
            }

            this.ticking = false;
        }
    }

    // Инициализация когда DOM готов
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Scroll Progress Bar: DOM готов, запускаем инициализацию');
        new ScrollProgressBar();
    });

})(jQuery);